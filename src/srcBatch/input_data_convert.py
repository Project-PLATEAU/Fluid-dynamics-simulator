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

logger = log_writer.getLogger()
#region 0.orig

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

def create_userEpsilon(wind_direction_id:int,wind_velocity:float,groud_alt:float,stl_filenames:list)->str:
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
    
'''%( convert_to_in_wall_name(wind_direction_id),abs(wind_velocity),groud_alt)

    for stl_filename in stl_filenames:
        s += '''    %s                                    // building or ground surface
    {
        type            epsilonWallFunction;
        value           $internalField;
    }
    
'''%(file_path_generator.get_filename_without_extention(stl_filename))
    return s

def create_userK(wind_direction_id:int,wind_velocity:float,groud_alt:float,stl_filenames:list)->str:
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
    
'''%( convert_to_in_wall_name(wind_direction_id),abs(wind_velocity),groud_alt)
    for stl_filename in stl_filenames:
        s += '''    %s                                    // building or ground surface
    {
        type            kqRWallFunction;
        value           $internalField;
    }
    
'''%(file_path_generator.get_filename_without_extention(stl_filename))
    return s

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

def create_userP(wind_direction_id:int)->str:
    s = get_header()
    s +='''    %s                                  // inlet wall
    {
        type            zeroGradient;
    }
    
'''%( convert_to_in_wall_name(wind_direction_id))
    s +='''    %s                                  // outlet wall
    {
        type            prghPressure;
        p               $internalField;
        value           $internalField;
    }
    
'''%( convert_to_out_wall_name(wind_direction_id))
    return s

def create_userPrgh(wind_direction_id:int)->str:
    s = get_header()
    s +='''    %s                                  // outlet wall
    {
        type            fixedValue;
        value           $internalField;
    }
    
'''%( convert_to_out_wall_name(wind_direction_id))
    return s

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

def create_userT_1(temperature:float)->str:
    s = get_header()
    kelvin = temperature_converter.convert_to_kelvin(temperature)
    s +='''//                    Set Temparature (K)

internalField   uniform %f;'''%(kelvin)
    return s

def create_userT_2(wind_direction_id:int,stl_filenames:list,heat_removal_q:list[float])->str:
    s = get_header()
    s +='''    %s                                  // inlet wall
    {
        type            fixedValue;
        value           $internalField;
    }
    
'''%( convert_to_in_wall_name(wind_direction_id))
    s +='''    %s                                  // outlet wall
    {
        type            inletOutlet;
        inletValue      $internalField;
        value           $internalField;
    }
    
'''%( convert_to_out_wall_name(wind_direction_id))
    for i in range(len(stl_filenames)):
        stl_filename = stl_filenames[i]
        q = heat_removal_q[i]
        s += '''    %s                                    // building or ground surface
    {
        type            externalWallHeatFluxTemperature;
        mode            coefficient;
        kappaMethod     fluidThermo;
        h               10;                     // W/m2 K
        Ta              $internalField;         // Ref. Temperature
        qr              qr;
        q               %f;                    // User input Heat Flux W/m2
        value           $internalField;
    }
    
'''%(file_path_generator.get_filename_without_extention(stl_filename),q)
    return s

def create_userU_1(wind_direction_id:int,wind_velocity:float)->str:
    s = get_header()
    s +='internalField   uniform (%s);    // initial Velocity'%(convert_to_velocity_args(wind_direction_id,wind_velocity))
    return s

def create_userU_2(wind_direction_id:int,wind_velocity:float,groud_alt:float,stl_filenames:list)->str:
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
    
'''%( convert_to_in_wall_name(wind_direction_id),abs(wind_velocity),groud_alt)
    s +='''    %s                                  // outlet wall
    {
        type            inletOutlet;
        inletValue      uniform (0 0 0);
        value           $internalField;
    }
    
'''%( convert_to_out_wall_name(wind_direction_id))

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
        absorptivity    (%f %f);                        // 可視光線 赤外線
        emissivity      (%f %f);
    };
}
    
'''%(file_path_generator.get_filename_without_extention(stl_filename),
     solar_absorption_rate,solar_absorption_rate,
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
def create_userBlockMesh_userSnappyHexMesh_3(system_id:int,south_lat:float,north_lat:float,west_long:float,east_long:float,groud_alt:float,sky_alt:float)->tuple:
    #国土地理院の平面直角座標の定義は、南→北が X で西→東がＹ
    #計算は南→北をy、 西→東を x として行う
    miny, minx  = coordinate_converter.convert_from_LatLon(system_id,south_lat, west_long)
    maxy, maxx  = coordinate_converter.convert_from_LatLon(system_id,north_lat, east_long)
    minz = groud_alt
    maxz = sky_alt
    nx = round(( maxx-minx ) /5)
    ny = round(( maxy-miny ) /5)
    nz = round(( maxz-minz ) /5)
    s_userBlockMesh = '''minx %f;
miny %f;
minz %f;
maxx %f;
maxy %f;
maxz %f;

nx %d;
ny %d;
nz %d;
''' %( minx, miny,minz,maxx, maxy,maxz,nx,ny,nz)
    s_userSnappyHexMesh_3 ='    locationInMesh (%f %f %f);'%(
        (minx+maxx)/2,
        (miny+maxy)/2,
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

def convert_to_velocity_args( wind_direction_id:int, velocity:float)->str:
    match wind_direction_id:
        case 1:
            return '0 %f 0'%(velocity)
        case 2:
            return '0 %f 0'%(-velocity)
        case 3:
            return '%f 0 0'%(velocity)
        case 4:
            return '%f 0 0'%(-velocity)
    raise IndexError
def convert_to_uniform_args( wind_direction_id:int)->str:
    match wind_direction_id:
        case 1:
            return '0 2 0'
        case 2:
            return '0 -2 0'
        case 3:
            return '2 0 0'
        case 4:
            return '-2 0 0'
    raise IndexError
def convert_to_in_wall_name( wind_direction_id:int)->str:
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

def export_user_files(path_folder:str,stl_filenames:list,
                      temperature:float,wind_direction_id:int,wind_velocity:float,system_id:int,
                      south_lat:float,north_lat:float,west_long:float,east_long:float,groud_alt:float,sky_alt:float,
                      mesh_level:int,
                      solar_date:date,solar_time:int,solar_absorption_rates:list[float],heat_removal_q:list[float]):

    ## 0.origフォルダにuserファイルを書き出し    
    path_zero = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_INPUT)  
    
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userAlphat'),
        create_userAlphat(stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userEpsilon'),
        create_userEpsilon(wind_direction_id,wind_velocity,groud_alt,stl_filenames))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userK'),
        create_userK(wind_direction_id,wind_velocity,groud_alt,stl_filenames))    
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userNut'),
        create_userNut(stl_filenames))    
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userP'),
        create_userP(wind_direction_id))    
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userPrgh'),
        create_userPrgh(wind_direction_id))  
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userT'),
        create_userT(temperature,stl_filenames,solar_absorption_rates))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userT_1'),
        create_userT_1(temperature))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userT_2'),
        create_userT_2(wind_direction_id,stl_filenames,heat_removal_q))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userU_1'),
        create_userU_1(wind_direction_id,wind_velocity))
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_zero,'userU_2'),
        create_userU_2(wind_direction_id,wind_velocity,groud_alt,stl_filenames))

    ## constantフォルダにuserファイルを書き出し    
    path_constant = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_CONSTANT)
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_constant,'userBoundaryRadiationProperties'),
        create_userBoundaryRadiationProperties(stl_filenames,solar_absorption_rates))   
    file_controller.write_text_file_fs(
        file_path_generator.combine(path_constant,'userRadiationProperties'),
        create_userRadiationProperties(solar_date,solar_time,south_lat,north_lat,west_long,east_long)) 

    ## systemフォルダにuserファイルを書き出し    
    path_system = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_SYSTEM)
    s_userBlockMesh, s_userSnappyHexMesh_3 = create_userBlockMesh_userSnappyHexMesh_3(system_id,south_lat,north_lat,west_long,east_long,groud_alt,sky_alt)
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
    solar_date = model.simulation_model.solar_altitude_date #日付
    solar_time = model.simulation_model.solar_altitude_time #時間帯
    south_lat = model.simulation_model.south_latitude #南端緯度
    north_lat = model.simulation_model.north_latitude #北端緯度
    west_long = model.simulation_model.west_longitude #西端経度
    east_long = model.simulation_model.east_longitude #東端経度
    ground_alt = model.simulation_model.ground_altitude #地面高度
    sky_alt = model.simulation_model.sky_altitude #上空高度
    mesh_level = model.simulation_model.mesh_level #メッシュ粒度

    export_user_files(path_folder_template,stl_filenames,
                      temperature,wind_direction_id,wind_velocity,system_id,
                      south_lat,north_lat,west_long,east_long,ground_alt,sky_alt,
                      mesh_level,
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
    
    # export_user_files(r'G:\共有ドライブ\4gsd-bridge-cfd\社内検討資料\ファイルインターフェース\生成した入力ファイル',
    #                 ['building.stl','grounds.stl'],
    #                 26.85, #外気温300K
    #                 1, #南→北
    #                 2.0, #風速
    #                 5, #兵庫県はV系
    #                 35.33918373486737,
    #                 35.33991298963914,
    #                 134.84927817801656,
    #                 134.85159301437136,
    #                 65.0,
    #                 125.0,
    #                 1, #メッシュレベル
    #                 date(2023,8,1),
    #                 15,
    #                 [0.7,0.7],
    #                 [100,0])

    model_id=utils.get_args(1)[0]
    main(model_id)