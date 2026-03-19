import os
from pathlib import Path

from common import log_writer
from common import utils
from common import file_controller
from common import file_path_generator
from common import status_db_connection
from common import static
from common import webapp_db_connection
from common import coordinate_converter
from common import temperature_converter
from common import shell_controller
from datetime import date
import math
import json
import numpy as np
from common.utils import get_settings

logger = log_writer.getLogger()

#region 0.orig

# inc/userAlphat
def create_userAlphat(stl_filenames:list,pcu2_stl_filename:str)->str:
    s = get_header()
    for stl_filename in stl_filenames:
        s += '''    %s                                    // building or ground surface
    {
        type            compressible::alphatWallFunction;
        Prt             0.85;
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(stl_filename))

    if pcu2_stl_filename:
        s += '''    %s
    {
        type            compressible::alphatWallFunction;
        Prt             0.85;
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))
    return s

# inc/userEpsilon
def create_userEpsilon(inlet_wall:int,wind_velocity:float,groud_alt:float,stl_filenames:list,pcu2_stl_filename:str)->str:
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
    
    if pcu2_stl_filename:
        s += '''    %s
    {
        type            epsilonWallFunction;
        value           $internalField;
    }

    '''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))
    return s

# inc/userK
def create_userK(inlet_wall:int,wind_velocity:float,groud_alt:float,stl_filenames:list,pcu2_stl_filename:str)->str:
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

    if pcu2_stl_filename:
        s += '''    %s
    {
        type            kqRWallFunction;
        value           $internalField;
    }

    '''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))
    return s

# inc/userNut
def create_userNut(stl_filenames:list,pcu2_stl_filename:str)->str:
    s = get_header()
    for stl_filename in stl_filenames:
        s += '''    %s                                    // building or ground surface
    {
        type            atmNutkWallFunction;
        z0              uniform 0.1;              // Roughness Height
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(stl_filename))

    if pcu2_stl_filename:
        s += '''    %s
    {
        type            atmNutkWallFunction;
        z0              uniform 0.1;
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))
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
def create_userS_2(inlet_wall:int, stl_filenames:list,stl_type_ids:list,pcu2_stl_filename:str)->str:
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

    if pcu2_stl_filename:
        id = float(get_settings("StlType","green"))
        s += '''%s                                 // Green
    {
        type            fixedGradient;
        gradient        uniform %f;    // Set Green Value or Water Value
    }

    '''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename), id)
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
def create_userT_2(inlet_wall:int,stl_filenames:list,heat_removal_q:list[float],pcu2_stl_filename:str)->str:
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

    if pcu2_stl_filename:
        s += '''    %s                                    // plantcover_under2m
    {
        type            externalWallHeatFluxTemperature;
        mode            coefficient;
        kappaMethod     fluidThermo;
        h               10;                 // W/m2 K
        Ta              $refT;              // Ref. Temperature
        qr              qr;
        q               -100.000;          // User input Heat Flux W/m2
        value           $internalField;
    }

'''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))
    return s

# inc/userU_1
def create_userU_1()->str:
    s = get_header()
    s +='''internalField   uniform (0 0 0);'''
    return s

# inc/userU_2
def create_userU_2(inlet_wall:int,wind_velocity:float,groud_alt:float,stl_filenames:list,pcu2_stl_filename:str)->str:
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

    if pcu2_stl_filename:
        s += '''    %s
    {
        type            noSlip;
    }

'''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))

    return s
#endregion

#region constant

# userRadiationProperties
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

## constant/inc/userBoundaryRadiationProperties
def create_userBoundaryRadiationProperties(stl_filenames:list,solar_absorption_rates:list[float],pcu2_stl_filename:str)->str:
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

'''%(file_path_generator.get_filename_without_extention(stl_filename),solar_absorption_rate,solar_absorption_rate)

    if pcu2_stl_filename:
        s += '''%s
{
    type    opaqueDiffusive;
    wallAbsorptionEmissionModel
    {
        type            multiBandAbsorption;
        absorptivity    (0.3 0.7);
        emissivity      (0.3 0.7);
    };
}

FaceZoneTrees
{
    type    opaqueDiffusive;
    wallAbsorptionEmissionModel
    {
        type            multiBandAbsorption;
        absorptivity    (0.3 0.7);
        emissivity      (0.3 0.7);
    };
}

'''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))
    
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

# inc/userSurfaceFeatureExtract
def create_userSurfaceFeatureExtract(stl_filenames:list, pcu2_stl_filename:str)->str:
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

    if pcu2_stl_filename:
        s += '''%s                            // Set User stl or obj
{
    extractionMethod    extractFromSurface;
    includedAngle       150;

    subsetFeatures
    {
        nonManifoldEdges       no;
        openEdges       yes;
    }
}

'''%(file_path_generator.get_filename_with_extention(pcu2_stl_filename))
    return s

# inc/userSurfaceFeatureExtractSnappyHexMesh_1
def create_userSnappyHexMesh_1(mesh_level:int,stl_filenames:list,pcu2_stl_filename:str,pco2_stl_filename:str,tree_data:list,
                               system_id:int,south_lat:float,north_lat:float,west_long:float,east_long:float,sky_alt:float)->tuple:

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

    if pcu2_stl_filename:
        s += '''    %s
    {
        type triSurfaceMesh;
        name %s;
    }
''' % (file_path_generator.get_filename_with_extention(pcu2_stl_filename),file_path_generator.get_filename_without_extention(pcu2_stl_filename))

    if pco2_stl_filename:
        s += '''    %s
    {
        type triSurfaceMesh;
        name %s;
    }
''' % (file_path_generator.get_filename_with_extention(pco2_stl_filename),file_path_generator.get_filename_without_extention(pco2_stl_filename))

    min_y, min_x  = coordinate_converter.convert_from_LatLon(system_id,south_lat, west_long)
    max_y, max_x  = coordinate_converter.convert_from_LatLon(system_id,north_lat, east_long)
    maxz = sky_alt

    s += '''
    // 植生乱流モデル用
    // dummy
    dummy
    {
        type searchableCylinder;
        point1          (%f %f %f);
        point2          (%f %f %f);
        radius      3.0;
    }

''' %((min_x+max_x)/2, (min_y+max_y)/2, (maxz-10),
      (min_x+max_x)/2, (min_y+max_y)/2, maxz)

    if tree_data:
        for tree in tree_data:
            tree_id = 't' + tree["id"]
            btm_centre = tuple(tree["bottom_centre"])
            top_centre = tuple(tree["top_centre"])
            radius = tree["radius"]

            s += '''
    %s
    {
        type searchableCylinder;
        point1          (%.6f %.6f %.6f);
        point2          (%.6f %.6f %.6f);
        radius          %.1f;
    }

''' % (tree_id, btm_centre[0], btm_centre[1], btm_centre[2], top_centre[0], top_centre[1], top_centre[2], radius)

    s += '''
}
'''
    return s

# inc/userSnappyHexMesh_2
def create_userSnappyHexMesh_2(stl_filenames:list, pcu2_stl_filename:str,pco2_stl_filename:str,tree_data:list)->str:
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
 
    if pcu2_stl_filename:
        s += '''
        {
            file "%s.eMesh";
            level $mlevel;
        }
'''%(file_path_generator.get_filename_without_extention(pcu2_stl_filename))

    s +='''
    );
    refinementSurfaces
    {
'''
 
    for stl_filename in stl_filenames:
        s += '''
        %s
        {
            level (1 $mlevel);
        }
'''%(file_path_generator.get_filename_without_extention(stl_filename))

    if pcu2_stl_filename:
        s += '''
        %s
        {
            level (1 $mlevel);
        }
''' % (file_path_generator.get_filename_without_extention(pcu2_stl_filename))
    s += '''
    }
'''

    s +='''
    refinementRegions
    {
        dummy
        {
            mode distance;
            levels ((3 2));  
        }
'''

    if tree_data:
        for tree in tree_data:
            tree_id = 't' + tree["id"]
            s += '''
        %s
        {
            mode distance;
            levels ((3 2));  
        }

''' % (tree_id)

    if pco2_stl_filename:
        s += '''
        %s
        {
            mode distance;
            levels ((3 2));  
        }
''' % (file_path_generator.get_filename_without_extention(pco2_stl_filename))

    s += '''
    }
'''
    return s

# inc/userSnappyHexMesh_3
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

# inc/userTopoSetCDict
def create_userTopoSetDict(system_id:int,south_lat:float,north_lat:float,west_long:float,east_long:float,groud_alt:float,sky_alt:float,
                           tree_data:list,tree_stl_filename:str,pco2_stl_filename:str)->tuple:
    #国土地理院の平面直角座標の定義は、南→北が X で西→東がＹ
    #計算は南→北をy、 西→東を x として行う
    min_y, min_x  = coordinate_converter.convert_from_LatLon(system_id,south_lat, west_long)
    max_y, max_x  = coordinate_converter.convert_from_LatLon(system_id,north_lat, east_long)
    minz = groud_alt
    maxz = sky_alt

    s = '''
minx %f;
miny %f;
minz %f;
maxx %f;
maxy %f;
maxz %f;

xc #eval{ ($minx+$maxx)*0.5 };
yc #eval{ ($miny+$maxy)*0.5 };
zz #eval{ $maxz-10 };

'''%(min_x,min_y,minz,max_x,max_y,maxz)

    s += '''
actions
(
    //dummy to make newZone
    {
        name    dummyCell;
        type    cellSet;
        action  new;
        source  cylinderToCell;
        p1      ($xc $yc $zz);
        p2      ($xc $yc $maxz);
        radius  2.5;
    }
    {
        name    zoneTrees;
        type    cellZoneSet;
        action  new;
        source  setToCellZone;
        set     dummyCell;
    }
    {
        name    dummyFace;
        type    faceSet;
        action  new;
        source  cylinderToFace;
        p1      (0.0 0.0 0.0);
        p2      (0.0 0.0 0.001);
        radius  0.001;
    }
    {
        name    FaceZoneTrees;
        type    faceZoneSet;
        action  new;
        source  setToFaceZone;
        faceSet dummyFace;
    }
'''
    if tree_data:
        for tree in tree_data:
            tree_id = 't' + tree["id"]
            btm_centre = tuple(tree["bottom_centre"])
            top_centre = tuple(tree["top_centre"])
            radius = tree["radius"]
            tree_face =  't' + tree["id"] + 'F'

            s += '''
    //tree cell
    {
        name    %s;
        type    cellSet;
        action  new;
        source  cylinderToCell;
        p1      (%.6f %.6f %.6f);
        p2      (%.6f %.6f %.6f);
        radius  %.1f;
    }
    {
        name    zoneTrees;
        type    cellZoneSet;
        action  add;
        source  setToCellZone;
        set     %s;
    }
    //tree face
    {
        name    %s;
        type    faceSet;
        action  new;
        source  cylinderToFace;
        p1      (%.6f %.6f %.6f);
        p2      (%.6f %.6f %.6f);
        radius  %.1f;
    }
    {
        name    FaceZoneTrees;
        type    faceZoneSet;
        action  add;
        source  setToFaceZone;
        faceSet %s;
    }

''' % (tree_id, btm_centre[0], btm_centre[1], btm_centre[2], top_centre[0], top_centre[1], top_centre[2], radius, tree_id,
       tree_face, btm_centre[0], btm_centre[1], btm_centre[2], top_centre[0], top_centre[1], top_centre[2], radius, tree_face)

    if pco2_stl_filename is None:
        s += "\n);"
        return s

    file = file_path_generator.get_filename_with_extention(pco2_stl_filename)
    file_name = file_path_generator.get_filename_without_extention(pco2_stl_filename)
    file_Fname = file_name + 'F'

    s += '''
    //plantcover cell
    {
        name        %s;
        type        cellSet;
        action      new;
        source      searchableSurfaceToCell;
        surfaceType triSurfaceMesh;
        surfaceName  %s;
    }
    {
        name         zoneTrees;
        type         cellZoneSet;
        action       add;
        source       setToCellZone;
        set          %s;
    }
    //plantcover face
    {
        name         %s;
        type         faceSet;
        action       new;
        source       searchableSurfaceToFace;
        surfaceType  triSurfaceMesh;
        surfaceName  %s;
    }
    {
        name         FaceZoneTrees;
        type         faceZoneSet;
        action       add;
        source       setToFaceZone;
        faceSet      %s;
    }
);
''' % (file_name, file, file_name, file_Fname, file, file_Fname)
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

def export_user_files(path_folder:str,stl_filenames:list,stl_type_ids:list,temperature:float,wind_direction_id:int,inlet_wall:int,wind_velocity:float,system_id:int,
                      south_lat:float,north_lat:float,west_long:float,east_long:float,groud_alt:float,sky_alt:float,
                      mesh_level:int,humidity:float,solar_date:date,solar_time:int,solar_absorption_rates:list[float],heat_removal_q:list[float],
                      tree_stl_filename:str,tree_data:list,pcu2_stl_filename:str,pco2_stl_filename:str):

    ## 0.orig/incフォルダに書き出し
    path_zero = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_INPUT)
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userAlphat'),create_userAlphat(stl_filenames,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userEpsilon'),create_userEpsilon(inlet_wall,wind_velocity,groud_alt,stl_filenames,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userK'),create_userK(inlet_wall,wind_velocity,groud_alt,stl_filenames,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userNut'),create_userNut(stl_filenames,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userP'),create_userP(inlet_wall))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userPrgh'),create_userPrgh(inlet_wall))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userS_1'),create_userS_1(humidity, temperature))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userS_2'),create_userS_2(inlet_wall,stl_filenames,stl_type_ids,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userT'),create_userT(temperature,stl_filenames,solar_absorption_rates))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userT_0'),create_userT_0())
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userT_1'),create_userT_1(temperature))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userT_2'),create_userT_2(inlet_wall,stl_filenames,heat_removal_q,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userU_1'),create_userU_1())
    file_controller.write_text_file_fs(file_path_generator.combine(path_zero,'userU_2'),create_userU_2(inlet_wall,wind_velocity,groud_alt,stl_filenames,pcu2_stl_filename))

    ## constant/incフォルダに書き出し
    path_constant = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_CONSTANT)
    file_controller.write_text_file_fs(file_path_generator.combine(path_constant,'userBoundaryRadiationProperties'),create_userBoundaryRadiationProperties(stl_filenames,solar_absorption_rates,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_constant,'userRadiationProperties'),create_userRadiationProperties(solar_date,solar_time,south_lat,north_lat,west_long,east_long))

    ## system/incフォルダに書き出し
    path_system = file_path_generator.combine(path_folder,static.FOLDER_NAME_SIMULATION_SYSTEM)
    s_userBlockMesh, s_userSnappyHexMesh_3 = create_userBlockMesh_userSnappyHexMesh_3(system_id,south_lat,north_lat,west_long,east_long,groud_alt,sky_alt,wind_direction_id)
    file_controller.write_text_file_fs(file_path_generator.combine(path_system,'userBlockMesh'),s_userBlockMesh)
    file_controller.write_text_file_fs(file_path_generator.combine(path_system,'userSnappyHexMesh_1'),
                                       create_userSnappyHexMesh_1(mesh_level,stl_filenames,pcu2_stl_filename,pco2_stl_filename,tree_data,system_id,south_lat,north_lat,west_long,east_long,sky_alt))
    file_controller.write_text_file_fs(file_path_generator.combine(path_system,'userSnappyHexMesh_2'),
                                       create_userSnappyHexMesh_2(stl_filenames,pcu2_stl_filename,pco2_stl_filename,tree_data))
    file_controller.write_text_file_fs(file_path_generator.combine(path_system,'userSnappyHexMesh_3'),s_userSnappyHexMesh_3)
    file_controller.write_text_file_fs(file_path_generator.combine(path_system,'userSurfaceFeatureExtract'),create_userSurfaceFeatureExtract(stl_filenames,pcu2_stl_filename))
    file_controller.write_text_file_fs(file_path_generator.combine(path_system,'userTopoSetDict'),
                                       create_userTopoSetDict(system_id,south_lat,north_lat,west_long,east_long,groud_alt,sky_alt,tree_data,tree_stl_filename,pco2_stl_filename))

    return

def parse_tree_json(file_path):
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            tree_data = json.load(f)

        tree_info_list = []

        for tree in tree_data["trees"]:
            tree_id = tree.get("id")
            p1 = tree.get("p1")
            p2 = tree.get("p2")
            radius = tree.get("radius")
            # 必要なフィールドが存在しない場合はスキップ
            if not all([tree_id, p1, p2, radius]):
                continue
            # 各木のデータを辞書にまとめる
            tree_info = {
                "id": tree_id,
                "bottom_centre": tuple(p1),
                "top_centre": tuple(p2),
                "radius": radius,
            }
            # リストに追加
            tree_info_list.append(tree_info)
        return tree_info_list

    except FileNotFoundError:
        print(f"Error: File not found at {file_path}")
    except json.JSONDecodeError:
        print(f"Error: Failed to decode JSON in file {file_path}")
    except Exception as e:
        print(f"An unexpected error occurred: {e}")

def _normalize_to_existing_mstl_dir(mstl_file, file_path_generator):
    """
    mstl_file: 入力パス（文字列）。末尾スラッシュがある想定だが無くても可。
    file_path_generator: get_shared_folder() と combine(base, rel) を提供するオブジェクト。
    戻り値: 実在するディレクトリ（mstl_file がファイルの場合はそのままの文字列）、存在しなければ空文字列。
    """
    path = mstl_file.strip("/")
    parts = path.split("/")

    # minimal expected length: ['city_model', <id>, 'region', <id>, <stl_type_id>] -> 5 パート
    min_len = 5
    if len(parts) < min_len:
        try_paths = [path]
    else:
        try_paths = []
        for cut in range(0, len(parts) - (min_len - 1)):
            candidate_parts = parts[:len(parts) - cut]
            candidate = "/".join(candidate_parts) + "/"
            try_paths.append(candidate)

    shared = ""
    try:
        shared = file_path_generator.get_shared_folder()
    except Exception:
        return ""

    for candidate in try_paths:
        fs_candidate = file_path_generator.combine(shared, candidate)
        if os.path.isdir(fs_candidate):
            return candidate
    return ""

def update_stl_file(region_id,model_id):
    stl_files =webapp_db_connection.select_stls(region_id,model_id)
    logger.info('[%s] The region own %i stl files. region_id: %s'%(model_id,len(stl_files),region_id))
    if(len(stl_files)==0):
        logger.error('[%s] Failed to get stl files and solar absorptivity.'%model_id)
        raise ValueError

    updates = []
    path_fs_top_folder = file_path_generator.get_shared_folder()
    for stl_file in stl_files:
        stl_model = stl_file.solar_absorptivity
        stl_type_id = stl_model.stl_type_id
        mstl_file = stl_file.stl_model.stl_file
        logger.info("mstl_file is %s" % mstl_file)

        def _is_obj_or_stl(name):
            if not name:
                return False
            if "." not in name:
                return False
            return name.rsplit(".", 1)[1].lower() in ("obj", "stl")

        normalized = _normalize_to_existing_mstl_dir(mstl_file, file_path_generator)

        if not normalized:
            files = []
        else:
            if _is_obj_or_stl(normalized):
                try:
                    full = file_path_generator.combine(path_fs_top_folder, normalized)
                    if os.path.exists(full):
                        try:
                            mtime = os.path.getmtime(full)
                        except Exception:
                            mtime = 0
                        files = [(os.path.basename(normalized), mtime)]
                    else:
                        files = []
                except Exception:
                    files = []
            else:
                files = []
                try:
                    file_path = file_path_generator.combine(path_fs_top_folder, normalized)
                    for name in os.listdir(file_path):
                        lname = name.lower()
                        if lname.endswith(".obj") or lname.endswith(".stl"):
                            full = os.path.join(file_path, name)
                            try:
                                mtime = os.path.getmtime(full)
                            except Exception:
                                mtime = 0
                            files.append((name, mtime))
                except FileNotFoundError:
                    files = []

                if not files:
                    fallback_name = f"{stl_type_id}.obj"
                    # combine の引数は (base, rel)
                    mstl_file = file_path_generator.combine(normalized, fallback_name)
                    logger.info("Set mstl_file to folder path: %s" % mstl_file)
                    updates.append((mstl_file,region_id,stl_type_id))
                else:
                    files.sort(key=lambda x: x[1], reverse=True)
                    latest_name, _ = files[0]
                    if latest_name.startswith("H_"):
                        actual_name = latest_name[len("H_"):]
                    else:
                        actual_name = latest_name
                    mstl_file = file_path_generator.combine(normalized, actual_name)
                    logger.info("Selected latest file %s -> %s" % (latest_name, mstl_file))
                    updates.append((mstl_file,region_id,stl_type_id))
    if updates:
        webapp_db_connection.update_stl_file(updates)

    return

def convert(model_id:str):
    # 対象レコードの取得：WEBアプリDBのシミュレーションモデルテーブルと解析対象地域テーブルをJoinし、引数で取得したシミュレーションモデルIDのレコードを取得
    logger.info('[%s] Start fetching the simulation model from the database.'%model_id)
    model = webapp_db_connection.fetch_model(model_id)

    #必要なパラメータをコピーされたファイル内でセットする
    logger.info('[%s] Set param.'%model_id)
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

    # simulation_inputフォルダ配下に引数で指定された番号のフォルダを作成する
    # すでに存在する場合は中にあるファイルを削除する
    logger.info('[%s] Start creating the simulation model folder.'%model_id)
    path_folder = file_path_generator.get_simulation_input_model_id_folder_fs(model_id)
    if file_controller.exist_folder_fs(path_folder):
        file_controller.delete_folder_fs(path_folder)
    
    #AllrunをキックするシェルはモデルIDのフォルダ（すなわちtemplateフォルダと同じレベル）にコピーする
    logger.info('[%s] Start copying the launcher shell file.'%model_id)
    launcher_file_source =  file_path_generator.combine(file_path_generator.combine(file_path_generator.get_execute_folder_wrapper(),static.FOLDER_NAME_RESOURCES), static.FILE_NAME_OPENFOAM_LAUNCH)
    launcher_file_destination =  file_path_generator.combine(path_folder, static.FILE_NAME_OPENFOAM_LAUNCH)
    file_controller.copy_file_fs(launcher_file_source, launcher_file_destination)

    #compressed_solverのsolver_idフォルダ内のtarファイルを解凍し、作成した番号のフォルダ以下にコピーする
    logger.info('[%s] Start extracting the simulation model folder.'%model_id)
    solver_id = model.simulation_model.solver_id
    solver_info = webapp_db_connection.fetch_solver(solver_id)
    path_fs_top_folder = file_path_generator.get_shared_folder()
    solver_file=file_path_generator.combine(path_fs_top_folder, solver_info.solver_compressed_file)
    file_controller.extract_tar_file_fs(solver_file, path_folder)
    path_folder_template = file_path_generator.combine(path_folder,file_path_generator.TEMPLATE)
    if(not (file_controller.exist_folder_fs(path_folder_template))):
        logger.error('A directory named "%s" is not created by extracting the tar file.'%(file_path_generator.TEMPLATE))
        logger.info('Source tar file : %s'%(solver_file))
        logger.info('Extract target directory : %s'%(path_folder))
        logger.info('Expected directory : %s'%(path_folder_template))
        raise FileNotFoundError('Template directory not found: %s'%(path_folder_template))

    # シミュレーションモデルテーブルから取得したregion_idをキーにSTLファイルテーブルとSTLファイル種別テーブルからstl_type_id、tree_flag、plant_cover_flag、stl_fileを取得する
    region_id = model.simulation_model.region_id
    update_stl_file(region_id, model_id)
    veg_stl_files =webapp_db_connection.select_veg_stls(region_id)
    logger.info('[%s] The region own %i stl files og veg. region_id: %s'%(model_id,len(veg_stl_files),region_id))
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
    tree_data = []
    tree_stl_filename = ''
    pcu2_stl_filename = ''
    pco2_stl_filename = ''
    TREE_TYPE = 20
    PLANT_COVER_TYPE = 21
    veg_stl_type_ids = []
    veg_stl_fileinfo = []
    
    for veg_stl_file in veg_stl_files:
        veg_stl_type_id = veg_stl_file.stl_model.stl_type_id
        # `tree_flag` と `plant_cover_flag` の条件処理
        if veg_stl_file.stl_type.tree_flag == True:
            veg_stl_type_ids.append({"type_id": veg_stl_type_id, "category": TREE_TYPE})
            logger.info("The stl type id %i (TREE_TYPE)." % veg_stl_type_id)
        elif veg_stl_file.stl_type.plant_cover_flag == True:
            veg_stl_type_ids.append({"type_id": veg_stl_type_id, "category": PLANT_COVER_TYPE})
            logger.info("The stl type id %i (PLANT_COVER_TYPE)." % veg_stl_type_id)
        else:
            logger.error("[%s] Failed to get stl files and solar absorptivity." % model_id)
            raise ValueError
         
    for stl_file in stl_files:
        stl_model = stl_file.solar_absorptivity
        mstl_file = stl_file.stl_model.stl_file
        logger.info("mstl_file is %s" % mstl_file)
        stl_type_id = stl_model.stl_type_id

        matched_veg = next(
            (veg for veg in veg_stl_type_ids if veg["type_id"] == stl_type_id), None
        )
        if matched_veg:
            filename = ('%s%s'% (file_path_generator.get_copied_stl_filename_without_extention(stl_type_id),file_path_generator.get_file_extension(mstl_file)))
            file_path = (file_path_generator.combine(path_fs_top_folder,mstl_file))
            logger.info('file_path : [%s] '%file_path)
            if matched_veg["category"] == TREE_TYPE:
                logger.info("Processing TREE_TYPE id %i." % stl_type_id)
                tree_path = (file_path_generator.generate_tree_file_path(path_fs_top_folder, mstl_file))
                logger.info("tree_path : %s" % tree_path)
                tree_data = parse_tree_json(tree_path)
                tree_stl_filename = filename
            elif matched_veg["category"] == PLANT_COVER_TYPE:
                logger.info("Processing PLANT_COVER_TYPE id %i." % stl_type_id)
                H_file_path = (file_path_generator.generate_modified_file_path(path_fs_top_folder, mstl_file))
                if file_controller.exist_folder_fs(file_path):
                    pcu2_stl_filename = filename
                    veg_stl_fileinfo.append((pcu2_stl_filename, file_path))
                if file_controller.exist_folder_fs(H_file_path):
                    pco2_stl_filename = ('H_%s' % filename)
                    veg_stl_fileinfo.append((pco2_stl_filename, H_file_path))
            else:
                logger.error("[%s] Invalid category for stl file id %i." % (model_id, stl_type_id))
                raise ValueError
        else:
            file_path = file_path_generator.combine(path_fs_top_folder,mstl_file)
            if file_controller.exist_folder_fs(file_path):
                stl_filepaths.append(file_path)
                stl_filenames.append('%s%s'% (file_path_generator.get_copied_stl_filename_without_extention(stl_file.solar_absorptivity.stl_type_id),file_path_generator.get_file_extension(mstl_file)))
                stl_type_ids.append(stl_type_id)
                policies = webapp_db_connection.select_policies(model_id,stl_type_id)
                logger.info('[%s] The model own %i policies for stl type id %i.'%(model_id,len(policies),stl_type_id))
                sar = stl_file.solar_absorptivity.solar_absorptivity
                hrq = stl_file.solar_absorptivity.heat_removal
                debug_init_sar = sar
                debug_init_hrq = hrq
                for policy in policies:
                    sar = policy.policy.solar_absorptivity
                    hrq = policy.policy.heat_removal
                if(sar < 0):
                    sar = 0
                if(sar > 1):
                    sar = 1
                solar_absorption_rates.append(sar)
                heat_removal_q.append(hrq)
                if(len(policies)>0):
                    logger.info('[%s] The Parameter <solar_absorptivity> for stl type id %i is changed by policies: %f -> %f'%(model_id,stl_type_id,debug_init_sar,sar))
                    logger.info('[%s] The Parameter <heat_removal> for stl type id %i is changed by policies: %f -> %f'%(model_id,stl_type_id,debug_init_hrq,hrq))
            else:
                logger.error("not found stl_file id: %i. path: %s" % (stl_type_id,file_path))
                raise ValueError

    # ファイル作成
    logger.info('[%s] Start creating OpenFOAM user files.'%model_id)
    export_user_files(path_folder_template,stl_filenames,stl_type_ids,temperature,wind_direction_id,inlet_wall,wind_velocity,system_id,
                      south_lat,north_lat,west_long,east_long,ground_alt,sky_alt,mesh_level,humidity,solar_date,solar_time,
                      solar_absorption_rates, heat_removal_q,tree_stl_filename,tree_data,pcu2_stl_filename,pco2_stl_filename)

    #city_model/<city_model_id>/region/<region_id>配下のSTLファイルをsimulation_input/<simulation_model_id>/template/constant/triInterface以下にコピーする
    logger.info('[%s] Start copying stl files.'%model_id)
    path_destination = file_path_generator.get_triInterface_folder_fs(model_id)
    file_controller.create_folder_fs(path_destination)

    for i in range(len(stl_filenames)):
        stl_filename = stl_filenames[i]
        sourcePath = stl_filepaths[i]
        destinatinoPath = file_path_generator.combine(path_destination, stl_filename)
        logger.info('destinatinoPath : [%s] '%destinatinoPath)
        file_controller.copy_file_fs(sourcePath,destinatinoPath)
    logger.info('[%s] The input files are now complete.'%model_id)

    for veg_stl_filename, sourcePath in veg_stl_fileinfo:
        destinationPath = file_path_generator.combine(path_destination, veg_stl_filename)
        logger.info('destinatinoPath : [%s] '%destinatinoPath)
        file_controller.copy_file_fs(sourcePath, destinationPath)
    logger.info('[%s] The input veg_files are now complete.'%model_id)


def main(model_id:str):
    task_id = status_db_connection.TASK_INPUT_DATA_CONVERT
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_INPUT_DATA_CONVERT、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)

    try:
        convert(model_id)
        # Webアプリ側からフォルダを削除する際に、www-dataからの削除権限が必要なので、権限を設定。
        shell_controller.change_folder_permission(file_path_generator.get_simulation_input_model_id_folder_fs(model_id))
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをNORMAL_ENDに更新する。
        status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_NORMAL_END)
    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"インプットデータ変換サービス実行時エラー", e)

if __name__ == "__main__":

    model_id=utils.get_args(1)[0]
    main(model_id)
