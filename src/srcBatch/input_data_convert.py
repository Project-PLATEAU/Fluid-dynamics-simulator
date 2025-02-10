from common import log_writer
from common import utils
from common import file_controller
from common import file_path_generator
from common import status_db_connection
from common import static
from common import webapp_db_connection
from common import coordinate_converter
from common import temperature_converter
from datetime import date
import math
import numpy as np
from common.utils import get_settings

logger = log_writer.getLogger()

#region 0.orig

# inc/userAlphat
def create_userAlphat(stl_filenames:list)->str:
    s = get_header()
    for stl_filename in stl_filenames:
        s += '''    %s                                    // building or ground surface
    {
        type            compressible::alphatWallFunction;
        Prt             0.85;
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(stl_filename))
    return s

# inc/userEpsilon
def create_userEpsilon(inlet_wall:int,wind_velocity:float,groud_alt:float,stl_filenames:list)->str:
    s = get_header()
    s +='''    %s                                   // inlet wall
    {
        type            exprFixedValue;
        value           $internalField;
        U0              %f;              // User Input Velocity (POSITIVE)
        MINZ            %f;              // User Input minZ
        Z0              10.0;              // (m) CONSTAT Value
        N0              0.27;              // Power CONSTAT Value
        ZG             550.0;              // (m) CONSTAT Value
        CM               0.3;              // CONSTAT Value
        valueExpr       "$CM*$U0/$Z0*$N0*pow((pos().z()-$MINZ)/$Z0,$N0-1.0) \\
                               * pow(0.1*pow((pos().z()-$MINZ)/$ZG,-$N0-0.05) \\
                                   * $U0*pow((pos().z()-$MINZ)/$Z0,$N0) ,2.0)";
    }

    '''%(convert_to_in_wall_name(inlet_wall),abs(wind_velocity),groud_alt)

    for stl_filename in stl_filenames:
        s +='''%s                                    // building or ground surface
    {
        type            epsilonWallFunction;
        value           $internalField;
    }

    '''%(file_path_generator.get_filename_without_extention(stl_filename))
    return s

# inc/userK
def create_userK(inlet_wall:int,wind_velocity:float,groud_alt:float,stl_filenames:list)->str:
    s = get_header()
    s +='''    %s                                   // inlet wall
    {
        type            exprFixedValue;
        value           $internalField;
        U0              %f;              // User Input Velocity
        MINZ            %f;              // User Input minZ
        Z0              10.0;              // (m) CONSTAT Value
        N0              0.27;              // Power CONSTAT Value
        ZG             550.0;              // (m) CONSTAT Value
        valueExpr       "pow(  0.1*pow((pos().z()-$MINZ)/$ZG,-$N0-0.05) \\
                          *    $U0*pow((pos().z()-$MINZ)/$Z0,$N0) ,2.0)";
    }

    '''%( convert_to_in_wall_name(inlet_wall),abs(wind_velocity),groud_alt)

    for stl_filename in stl_filenames:
        s += '''%s                                    // building or ground surface
    {
        type            kqRWallFunction;
        value           $internalField;
    }

    '''%(file_path_generator.get_filename_without_extention(stl_filename))
    return s

# inc/userNut
def create_userNut(stl_filenames:list)->str:
    s = get_header()
    for stl_filename in stl_filenames:
        s += '''    %s                                    // building or ground surface
    {
        type            atmNutkWallFunction;
        z0              uniform 0.1;              // Roughness Height
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(stl_filename))
    return s

# inc/userP
def create_userP(inlet_wall:int)->str:
    s = get_header()
    s +='''    %s                                  // inlet wall
    {
        type            zeroGradient;
    }

    '''%( convert_to_in_wall_name(inlet_wall))

    s +='''%s                                  // outlet wall
    {
        type            prghPressure;
        p               $internalField;
        value           $internalField;
    }
    '''%( convert_to_out_wall_name(inlet_wall))
    return s

# inc/userPrgh
def create_userPrgh(inlet_wall:int)->str:
    s = get_header()
    s +='''    %s                                  // outlet wall
    {
        type            fixedValue;
        value           $internalField;
    }
    '''%( convert_to_out_wall_name(inlet_wall))
    return s

# inc/userS_1
def create_userS_1(re_humidity:float, temperature:float)->str:
    s = get_header()
    abs_humidity = temperature_converter.convert_to_absolute_humidity(re_humidity,temperature)
    s +='''   internalField   uniform %f;       // (kg/kg)'''%(abs_humidity)
    return s

# inc/userS_2
def create_userS_2(inlet_wall:int, stl_filenames:list,stl_type_ids:list)->str:
    s = get_header()
    s +='''    %s                                  // inlet wall
    {
        type            fixedValue;
        value           $internalField;
    }

    '''%( convert_to_in_wall_name(inlet_wall))

    s +='''%s                                  // outlet wall
    {
        type            inletOutlet;
        inletValue      $internalField;
        value           $internalField;
    }

    '''%( convert_to_out_wall_name(inlet_wall))

    for stl_filename, stl_type_id in zip(stl_filenames, stl_type_ids):
        if stl_type_id == 12:
            id = float(get_settings("StlType","water"))
        elif stl_type_id == 14:
            id = float(get_settings("StlType","green"))
        else:
            continue
        s += '''%s                                 // Water or Green Area
    {
        type            fixedGradient;
        gradient        uniform %f;    // Set Green Value or Water Value
    }

    ''' % (file_path_generator.get_filename_without_extention(stl_filename), id)
    return s


# inc/userT
def create_userT(temperature:float,stl_filenames:list,solar_absorption_rates:list[float])->str:
    s = '' #userTは、OpenFOAMで直接読み込むのではなく、別プログラムでデータ加工用に置いているものであり、FoamFileの記述は不要
    kelvin = temperature_converter.convert_to_kelvin(temperature)
    s +='''%f                                  // Inlet Temperature (K)
'''%(kelvin)

    for i in range(len(stl_filenames)):
        stl_filename = stl_filenames[i]
        solar_absorption_rate = solar_absorption_rates[i]
        s += '''%s        %f                   // user input STL & emissivity
'''%(file_path_generator.get_filename_without_extention(stl_filename),
     solar_absorption_rate)
    return s

# inc/userT_0
def create_userT_0()->str:
    # 地盤や建物の内部温度を26度で設定
    s = '''refT   299.;'''
    return s

# inc/userT_1
def create_userT_1(temperature:float)->str:
    s = get_header()
    kelvin = temperature_converter.convert_to_kelvin(temperature)
    s +='''//   Set Temparature (K)

internalField   uniform %f;'''%(kelvin)
    return s

# inc/userT_2
def create_userT_2(inlet_wall:int,stl_filenames:list,heat_removal_q:list[float])->str:
    s = get_header()
    s +='''    %s                                  // inlet wall
    {
        type            fixedValue;
        value           $internalField;
    }

'''%( convert_to_in_wall_name(inlet_wall))

    s +='''    %s                                  // outlet wall
    {
        type            inletOutlet;
        inletValue      $internalField;
        value           $internalField;
    }

'''%( convert_to_out_wall_name(inlet_wall))

    for i in range(len(stl_filenames)):
        stl_filename = stl_filenames[i]
        q = heat_removal_q[i]
        s += '''    %s                                    // building or ground surface
    {
        type            externalWallHeatFluxTemperature;
        mode            coefficient;
        kappaMethod     fluidThermo;
        h               10;                 // W/m2 K
        Ta              $refT;              // Ref. Temperature
        qr              qr;
        q               %f;                 // User input Heat Flux W/m2
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(stl_filename),q)
    return s

# inc/userU_1
def create_userU_1()->str:
    s = get_header()
    s +='''internalField   uniform (0 0 0);'''
    return s

# inc/userU_2
def create_userU_2(inlet_wall:int,wind_velocity:float,groud_alt:float,stl_filenames:list)->str:
    s = get_header()
    s +='''    %s                                  // inlet wall
    {
        type            exprFixedValue;
        value           $internalField;
        U0              -%f;              // User Input Velocity (NEGATIVE)
        MINZ            %f;              // User Input MINZ
        N0              0.27;              // Power CONSTAT Value
        Z0              10.0;              // (m)   CONSTAT Value
        valueExpr       "$U0*pow((pos().z()-$MINZ)/$Z0,$N0)*face()/area()";
    }

'''%( convert_to_in_wall_name(inlet_wall),abs(wind_velocity),groud_alt)
    s +='''    %s                                  // outlet wall
    {
        type            inletOutlet;
        inletValue      uniform (0 0 0);
        value           $internalField;
    }

'''%( convert_to_out_wall_name(inlet_wall))

    for stl_filename in stl_filenames:
        s += '''    %s                                    // building or ground surface
    {
        type            noSlip;
    }

'''%(file_path_generator.get_filename_without_extention(stl_filename))

    return s
#endregion

#region constant
def create_userBoundaryRadiationProperties(stl_filenames:list,solar_absorption_rates:list[float])->str:
    s = get_header()
    for i in range(len(stl_filenames)):
        stl_filename = stl_filenames[i]
        solar_absorption_rate = solar_absorption_rates[i]
        s += '''%s                                    // building or ground surface
{
    type    opaqueDiffusive;
    wallAbsorptionEmissionModel
    {
        type            multiBandAbsorption;
        absorptivity    (0.30 %.2f);                   // 可視光線 赤外線
        emissivity      (0.30 %.2f);
    };
}

'''%(file_path_generator.get_filename_without_extention(stl_filename),
     solar_absorption_rate,solar_absorption_rate)
    return s

def create_userRadiationProperties(solar_date:date,solar_time:int,south_lat:float,north_lat:float,west_long:float,east_long:float)->str:
    d0 = date(solar_date.year-1, 12, 31)
    delta = solar_date - d0
    longitude = (west_long+east_long)/2
    latitude = (south_lat+north_lat)/2

    s = get_header()
    s +='''        localStandardMeridian   +9;    // GMT offset (hours)
        startDay                %i;    // day of the year
        startTime               %i;    // time of the day (hours decimal)
        longitude               %f;  // longitude (degrees)
        latitude                %f;   // latitude (degrees)
'''%(delta.days,solar_time,longitude,latitude)
    return s


#endregion

#region system

def convert_to_angle(wind_direction_id:int)->float:
    match wind_direction_id:
        case 3: # 西
            return 0
        case 7: # 西南西
            return 22.5
        case 6: # 南西
            return 45
        case 5: # 南南西
            return 67.5
        case 1: # 南
            return 90.0
        case 16: # 南南東
            return 112.5
        case 15: # 南東
            return 135
        case 14: # 東南東
            return 157.5
        case 4: # 東
            return 180.0
        case 8: # 西北西
            return -22.5
        case 9: # 北西
            return -45.0
        case 10: # 北北西
            return -67.5
        case 2: # 北
            return -90.0
        case 11: # 北北東
            return -112.5
        case 12: # 北東
            return -135.0
        case 13: # 東北東
            return -157.5
    raise IndexError

def create_userBlockMesh_userSnappyHexMesh_3(system_id:int,south_lat:float,north_lat:float,west_long:float,east_long:float,groud_alt:float,sky_alt:float,wind_direction_id:int)->tuple:
    #国土地理院の平面直角座標の定義は、南→北が X で西→東がＹ
    #計算は南→北をy、 西→東を x として行う
    min_y, min_x  = coordinate_converter.convert_from_LatLon(system_id,south_lat, west_long)
    max_y, max_x  = coordinate_converter.convert_from_LatLon(system_id,north_lat, east_long)
    minz = groud_alt
    maxz = sky_alt
    nx = round(( max_x - min_x ) /5)
    ny = round(( max_y - min_y ) /5)
    nz = round(( maxz - minz ) /5)

    # 中心点を計算し、各頂点を中心点に対して平行移動
    center_x = (min_x + max_x) / 2
    center_y = (min_y + max_y) / 2
    # pointsの定義（Y, X順）
    points = np.array([
        [min_y - center_y, min_x - center_x],  # 南西 (SW)
        [max_y - center_y, min_x - center_x],  # 北西 (NW)
        [max_y - center_y, max_x - center_x],  # 北東 (NE)
        [min_y - center_y, max_x - center_x]   # 南東 (SE)
    ])
    # 回転行列
    angle = convert_to_angle(wind_direction_id)
    theta = np.radians(angle)
    rotation_matrix = np.array([
        [np.cos(theta), -np.sin(theta)],
        [np.sin(theta), np.cos(theta)]
    ])
    rotated_points = np.dot(points, rotation_matrix)
    # 回転後の座標を中心点に戻す
    rotated_points[:, 0] += center_y
    rotated_points[:, 1] += center_x
    # 結果の表示
    sw_rot, nw_rot, ne_rot, se_rot = rotated_points
    s_userBlockMesh = '''minx %f;
miny %f;
minz %f;
maxx %f;
maxy %f;
maxz %f;
ang  %.2f;

x1 %f;
y1 %f;
x2 %f;
y2 %f;
x3 %f;
y3 %f;
x4 %f;
y4 %f;

nx %d;
ny %d;
nz %d;
''' %(min_x,min_y,minz,max_x,max_y,maxz,angle, sw_rot[1],sw_rot[0],se_rot[1],se_rot[0],ne_rot[1],ne_rot[0],nw_rot[1],nw_rot[0], nx,ny,nz)

    s_userSnappyHexMesh_3 ='    locationInMesh (%f %f %f);'%(
        (min_x+max_x)/2,
        (min_y+max_y)/2,
        maxz-(maxz-minz)*0.1
    )
    return s_userBlockMesh, s_userSnappyHexMesh_3

def create_userSnappyHexMesh_1(mesh_level:int,stl_filenames:list)->str:
    s ='''mlevel  %s;            // Mesh Refine Level 1 - 3

// Add User STL File
geometry
{
'''%( mesh_level)

    for stl_filename in stl_filenames:

        s += '''    %s
    {
        type triSurfaceMesh;
        name %s;
    }

'''%(file_path_generator.get_filename_with_extention(stl_filename),file_path_generator.get_filename_without_extention(stl_filename))
    s += '}'
    return s

def create_userSnappyHexMesh_2(stl_filenames:list)->str:
    s ='''    features
    (
'''
    for stl_filename in stl_filenames:
        s += '''
        {
            file "%s.eMesh";
            level $mlevel;
        }

'''%(file_path_generator.get_filename_without_extention(stl_filename))
    s += '    );'
    return s

def create_userSurfaceFeatureExtract(stl_filenames:list)->str:
    s = ''
    for stl_filename in stl_filenames:
        s += '''%s                            // Set User stl
{
    extractionMethod    extractFromSurface;
    includedAngle       150;

    subsetFeatures
    {
        nonManifoldEdges       no;
        openEdges       yes;
    }
}

'''%(file_path_generator.get_filename_with_extention(stl_filename))
    return s

#endregion

#region 共通関数
def get_header()->str:
    return '''FoamFile
{
    version     2.0;
    format      ascii;
    class       IOobject;
}

'''

def convert_to_in_wall_name(wind_direction_id:int)->str:
    match wind_direction_id:
        case 1:
            return 'Swall'
        case 2:
            return 'Nwall'
        case 3:
            return 'Wwall'
        case 4:
            return 'Ewall'
    raise IndexError

def convert_to_out_wall_name( wind_direction_id:int)->str:
    match wind_direction_id:
        case 1:
            return convert_to_in_wall_name(2)
        case 2:
            return convert_to_in_wall_name(1)
        case 3:
            return convert_to_in_wall_name(4)
        case 4:
            return convert_to_in_wall_name(3)
    raise IndexError

#endregion

def export_user_files(path_folder:str,stl_filenames:list,stl_type_ids:list,
                      temperature:float,wind_direction_id:int,inlet_wall:int,wind_velocity:float,system_id:int,
                      south_lat:float,north_lat:float,west_long:float,east_long:float,groud_alt:float,sky_alt:float,
                      mesh_level:int,humidity:float,
                      solar_date:date,solar_time:int,solar_absorption_rates:list[float],heat_removal_q:list[float]):

    ## 0.orig/incフォルダに書き出し
    path_zero = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_INPUT)
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userAlphat'),
        create_userAlphat(stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userEpsilon'),
        create_userEpsilon(inlet_wall,wind_velocity,groud_alt,stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userK'),
        create_userK(inlet_wall,wind_velocity,groud_alt,stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userNut'),
        create_userNut(stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userP'),
        create_userP(inlet_wall))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userPrgh'),
        create_userPrgh(inlet_wall))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userS_1'),
        create_userS_1(humidity, temperature))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userS_2'),
        create_userS_2(inlet_wall,stl_filenames,stl_type_ids))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userT'),
        create_userT(temperature,stl_filenames,solar_absorption_rates))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userT_0'),
        create_userT_0())
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userT_1'),
        create_userT_1(temperature))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userT_2'),
        create_userT_2(inlet_wall,stl_filenames,heat_removal_q))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userU_1'),
        create_userU_1())
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userU_2'),
        create_userU_2(inlet_wall,wind_velocity,groud_alt,stl_filenames))

    ## constant/incフォルダに書き出し
    path_constant = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_CONSTANT)
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_constant,'userBoundaryRadiationProperties'),
        create_userBoundaryRadiationProperties(stl_filenames,solar_absorption_rates))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_constant,'userRadiationProperties'),
        create_userRadiationProperties(solar_date,solar_time,south_lat,north_lat,west_long,east_long))

    ## system/incフォルダに書き出し
    path_system = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_SYSTEM)
    s_userBlockMesh, s_userSnappyHexMesh_3 = create_userBlockMesh_userSnappyHexMesh_3(system_id,south_lat,north_lat,west_long,east_long,groud_alt,sky_alt,wind_direction_id)
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_system,'userBlockMesh'),
        s_userBlockMesh)
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_system,'userSnappyHexMesh_1'),
        create_userSnappyHexMesh_1(mesh_level,stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_system,'userSnappyHexMesh_2'),
        create_userSnappyHexMesh_2(stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_system,'userSnappyHexMesh_3'),
        s_userSnappyHexMesh_3)
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_system,'userSurfaceFeatureExtract'),
        create_userSurfaceFeatureExtract(stl_filenames))
    return

def convert(model_id:str):
    # 対象レコードの取得：WEBアプリDBのシミュレーションモデルテーブルと解析対象地域テーブルをJoinし、引数で取得したシミュレーションモデルIDのレコードを取得
    logger.info('[%s] Start fetching the simulation model from the database.'%model_id)
    model = webapp_db_connection.fetch_model(model_id)
    region_id = model.simulation_model.region_id

    path_fs_top_folder = file_path_generator.get_shared_folder()

    stl_files =webapp_db_connection.select_stls(region_id,model_id)
    logger.info('[%s] The region own %i stl files. region_id: %s'%(model_id,len(stl_files),region_id))
    if(len(stl_files)==0):
        logger.error('[%s] Failed to get stl files and solar absorptivity.'%model_id)
        raise ValueError

    stl_filenames =[]
    stl_filepaths =[]
    stl_type_ids = []
    solar_absorption_rates = []
    heat_removal_q = []
    for stl_file in stl_files:
        stl_filenames.append('%s%s'% (file_path_generator.get_copied_stl_filename_without_extention(stl_file.solar_absorptivity.stl_type_id),file_path_generator.get_file_extension(stl_file.stl_model.stl_file)))
        stl_filepaths.append(file_path_generator.combine(path_fs_top_folder,stl_file.stl_model.stl_file))
        sar = stl_file.solar_absorptivity.solar_absorptivity
        hrq = stl_file.solar_absorptivity.heat_removal
        debug_init_sar = sar
        debug_init_hrq = hrq
        stl_type_id = stl_file.solar_absorptivity.stl_type_id
        stl_type_ids.append(stl_type_id)
        policies = webapp_db_connection.select_policies(model_id,stl_type_id)
        logger.info('[%s] The model own %i policies for stl type id %i.'%(model_id,len(policies),stl_type_id))
        for policy in policies:
            sar += policy.policy.solar_absorptivity
            hrq += policy.policy.heat_removal
        if(sar < 0):
            sar = 0
        if(sar > 1):
            sar = 1
        solar_absorption_rates.append(sar)
        heat_removal_q.append(hrq)
        if(len(policies)>0):
            logger.info('[%s] The Parameter <solar_absorptivity> for stl type id %i is changed by policies: %f -> %f'%(model_id,stl_type_id,debug_init_sar,sar))
            logger.info('[%s] The Parameter <heat_removal> for stl type id %i is changed by policies: %f -> %f'%(model_id,stl_type_id,debug_init_hrq,hrq))

    #simulation_inputフォルダ配下に引数で指定された番号のフォルダを作成する
    # すでに存在する場合は中にあるファイルを削除する
    logger.info('[%s] Start creating the simulation model folder.'%model_id)
    path_folder = file_path_generator.get_simulation_input_model_id_folder_fs(model_id)
    if file_controller.exist_folder_fs(path_folder):
        file_controller.delete_folder_fs(path_folder)

    #compressed_solverのsolver_idフォルダ内のtarファイルを解凍し、作成した番号のフォルダ以下にコピーする
    logger.info('[%s] Start extracting the simulation model folder.'%model_id)
    solver_id = model.simulation_model.solver_id
    solver_info = webapp_db_connection.fetch_solver(solver_id)
    solver_file=file_path_generator.combine(path_fs_top_folder, solver_info.solver_compressed_file)
    file_controller.extract_tar_file_fs(solver_file, path_folder)
    path_folder_template = file_path_generator.combine(path_folder,file_path_generator.TEMPLATE)
    if(not (file_controller.exist_folder_fs(path_folder_template))):
        logger.error('A directory named "%s" is not created by extracting the tar file.'%(file_path_generator.TEMPLATE))
        logger.info('Source tar file : %s'%(solver_file))
        logger.info('Extract target directory : %s'%(path_folder))
        logger.info('Expected directory : %s'%(path_folder_template))
        raise FileNotFoundError('Template directory not found: %s'%(path_folder_template))

    #AllrunをキックするシェルはモデルIDのフォルダ（すなわちtemplateフォルダと同じレベル）にコピーする
    logger.info('[%s] Start copying the launcher shell file.'%model_id)
    launcher_file_source =  file_path_generator.combine(file_path_generator.combine(file_path_generator.get_execute_folder_wrapper(),static.FOLDER_NAME_RESOURCES), static.FILE_NAME_OPENFOAM_LAUNCH)
    launcher_file_destination =  file_path_generator.combine(path_folder, static.FILE_NAME_OPENFOAM_LAUNCH)
    file_controller.copy_file_fs(launcher_file_source, launcher_file_destination)

    #必要なパラメータをコピーされたファイル内でセットする
    logger.info('[%s] Start creating OpenFOAM user files.'%model_id)
    system_id = model.region.coordinate_id
    temperature = model.simulation_model.temperature #外気温
    wind_velocity = model.simulation_model.wind_speed #風速
    wind_direction_id = model.simulation_model.wind_direction #風向き
    inlet_wall = 3 #流入面:Wwall
    humidity = model.simulation_model.humidity #湿度
    solar_date = model.simulation_model.solar_altitude_date #日付
    solar_time = model.simulation_model.solar_altitude_time #時間帯
    south_lat = model.simulation_model.south_latitude #南端緯度
    north_lat = model.simulation_model.north_latitude #北端緯度
    west_long = model.simulation_model.west_longitude #西端経度
    east_long = model.simulation_model.east_longitude #東端経度
    ground_alt = model.simulation_model.ground_altitude #地面高度
    sky_alt = model.simulation_model.sky_altitude #上空高度
    mesh_level = model.simulation_model.mesh_level #メッシュ粒度

    export_user_files(path_folder_template,stl_filenames,stl_type_ids,
                      temperature,wind_direction_id,inlet_wall,wind_velocity,system_id,
                      south_lat,north_lat,west_long,east_long,ground_alt,sky_alt,
                      mesh_level,humidity,
                       solar_date,solar_time,solar_absorption_rates, heat_removal_q)

    #city_model/<city_model_id>/region/<region_id>配下のSTLファイルをsimulation_input/<simulation_model_id>/template/constant/triInterface以下にコピーする
    logger.info('[%s] Start copying stl files.'%model_id)
    path_destination = file_path_generator.get_triInterface_folder_fs(model_id)
    file_controller.create_folder_fs(path_destination)
    for i in range(len(stl_filenames)):
        stl_filename = stl_filenames[i]
        sourcePath = stl_filepaths[i]
        destinatinoPath = file_path_generator.combine(path_destination, stl_filename)
        file_controller.copy_file_fs(sourcePath,destinatinoPath)

    logger.info('[%s] The input files are now complete.'%model_id)

def main(model_id:str):
    task_id = status_db_connection.TASK_INPUT_DATA_CONVERT
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_INPUT_DATA_CONVERT、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)

    try:
        convert(model_id)
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをNORMAL_ENDに更新する。
        status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_NORMAL_END)
    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"インプットデータ変換サービス実行時エラー", e)

if __name__ == "__main__":

    model_id=utils.get_args(1)[0]
    main(model_id)