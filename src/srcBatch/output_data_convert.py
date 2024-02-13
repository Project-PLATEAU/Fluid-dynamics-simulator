import sys
from common import file_path_generator
from common import utils
from common import status_db_connection
from common import webapp_db_connection
from common import file_controller
from common import log_writer
from common import coordinate_converter
from common import temperature_converter
import re
import math
from typing import Tuple, List, Dict
import json

logger = log_writer.getLogger()

#region OpenFOAM出力ファイル（このスクリプトで読み込むファイル）に関する定数
CELL_CENTRES_FILENAME : str = "C"
WIND_VECTORS_FILENAME : str = "U"
TEMPERATURES_FILENAME : str = "T"
SOLAR_IRRADIANCE_FILENAME : str = "qr"
BOUNDARY : str = "boundary" # OpenFOAM内のboundaryファイル
OWNER : str = "owner" # OpenFOAM内のownerファイル
N_FACES = "nFaces" # グループに属する面の数のキー
START_FACE = "startFace" # グループに属する面の最初の面番号のキー
#endregion
#region 可視化種別
RESULT_TYPE_WIND = 1
RESULT_TYPE_TEMPERATURE = 2
RESULT_TYPE_WBGT = 3
#endregion
#region 出力ファイル名、拡張子
FILE_TYPE_VISUALIZATION = "czml"
FILE_TYPE_DOWNLOAD = "geojson"
WBGT_FILENAME = "wbgt"
#endregion
#region 結果の図示における描画用パラメータ
NORMALIZED_VECTOR_LENGTH = 3 # 風のベクトルの長さ
VECTOR_WIDTH = 10 # 風のベクトルの太さ
TRIANGLE_BOTTOM_DIVIDE = 6 # 風の向きを示す三角形で、底辺に対する単位ベクトルの長さ
HIGHT_BUFFER = 0.5 # 地上n mのデータを取得するときの誤差範囲、例えば、地上1.5m だったら、地上1.5 プラスマイナスHIGHT_BUFFERのデータを取得する
WIND_NUM_OF_COLER_FROM_INT = 10 # 中間～最高または最低風速の色の段階数
TEMP_NUM_OF_COLER_FROM_INT = 10 # 中間～最高または最低温度の色の段階数
WIND_1_STEP = 0.2 # 表示される色の1段階分の風速(m/s)
TEMP_1_STEP = 0.35 # 表示される色の1段階分の温度(度)
HIGHTEST_COLOR = (255, 0, 0, 255) # Red
INTERMEDIATE_COLOR = (0, 255, 0, 255) # Green
LOWEST_COLOR = (0, 0, 255, 255) # Blue
POINT_PIXEL_SIZE = 10 # 点のピクセルサイズ
WBGT_COLOR_DEFINITION = [
    {
        "threshold" : 25, "rgba" : [0, 192, 255, 255] # 25未満：水色
    },
    {
        "threshold" : 28, "rgba" : [255, 255, 0, 255] # 25 - 28 : 黄色
    },
    {
        "threshold" : 31, "rgba" : [255, 165, 0, 255] # 28 - 31 : オレンジ色
    },
    {
        "threshold" : None, "rgba" : [255, 0, 0, 255] # 31以上 : 赤色
    }
]
#endregion
#region ジオイド高、楕円体高計算パラメータ
GEOID_HEIGHT = 36.7071
#endregion
#region 暑さ指数(WBGT)計算パラメータ
HUMIDITY = 50 # WBGT計算時の湿度（％）
def get_wbgt(ta : float, rh : float, sr : float, ws : float) -> float:
    wbgt = 0.735 * ta + 0.0374 * rh + 0.00292 * ta * rh + 7.619 * sr - 4.557 * sr ** 2 - 0.0572 * ws - 4.064
    return wbgt
#endregion

def create_folder(file_path : str):
    if file_controller.exist_folder_fs(file_path):
        file_controller.delete_folder_fs(file_path)
    file_controller.create_folder_fs(file_path)
    return

def create_converted_output_model_id_folder(model_id : str) -> str:
    file_path = file_path_generator.get_converted_output_model_id_folder_fs(model_id)
    create_folder(file_path)
    return file_path

def create_folder_by_type(model_id_folder : str, file_type : str, result_type : int) -> str:
    file_path = file_path_generator.combine(file_path_generator.combine(model_id_folder, file_type), str(result_type))
    create_folder(file_path)
    return file_path

def get_result_folder(model_id : str) -> str:
    parentResultFilePath = file_path_generator.get_simulation_output_model_id_folder_fs(model_id)
    if not file_controller.exist_folder_fs(parentResultFilePath):
        logger.error(log_writer.format_str(model_id,"結果フォルダが取得できませんでした"))
        raise Exception
         
    resultFolderNames = file_controller.get_subfolder_name_list(parentResultFilePath)
    numberFolderNameList = [s for s in resultFolderNames if s.isdigit()]
    if (len(numberFolderNameList) == 0):
        logger.error(log_writer.format_str(model_id,"結果フォルダが取得できませんでした"))
        raise Exception
    # 結果フォルダのうち、最大の数値のフォルダ名を取得する
    resultFolderName = max(numberFolderNameList, key=lambda x: int(x))
    return file_path_generator.combine(parentResultFilePath, resultFolderName)
    
def get_cell_centres(resultFolderName : str) -> []:
    try:
        # ファイルからテキストを読み込む
        with open(file_path_generator.combine(resultFolderName, CELL_CENTRES_FILENAME), "r") as file:
            text = file.read()
            # 正規表現パターンを定義
            pattern = re.compile(r"internalField\s+nonuniform\s+List<vector>\s+\d+\s*\(\s*(\([^)]+\)\s)*\)", re.DOTALL)
            # パターンに一致する部分を検索
            match = pattern.search(text)
            # マッチした部分があれば取得
            cell_centre_list = []
            if match:
                matched_all_str = match.group(0)
                coordinate_str_list = re.findall("\([0-9\.\- e]+\)", matched_all_str)
                cell_centre_list = [tuple(map(float, s.strip("()").split())) for s in coordinate_str_list]  # 文字列を座標のタプルのリストに変換
            return cell_centre_list
    except Exception as e:
        logger.error(f"セル中心取得時にエラーが発生しました: {e}")

def convert_lonlat_3d(coordinate_id : int, xyz : tuple) -> tuple:
    # x, yはそれぞれ基準点からの東西方向の距離（東が正）、南北方向の距離（北が正）になる。
    x, y, z = xyz
    # Transformerのtransformでは、平面直角座標系のx, yを渡す必要がある
    # 平面直角座標系だとxとyが逆転する。南北方向の距離（北が正）を先、東西方向の距離（東が正）を後に渡す
    lat, lon = coordinate_converter.convert_to_LatLon(coordinate_id, y, x)
    # czml, geojson形式は経度、緯度、高さの順に記述するので、その順で格納する
    return (lon, lat, z)

def convert_coordinates_to_lonlat(coordinate_id : int, coordinates : List[tuple]) -> []:
    converted_list = [convert_lonlat_3d(coordinate_id, tuple_xyz) for tuple_xyz in coordinates]
    return converted_list

def get_wind_vectors (resultFolderName : str) -> []:
    try:
        # ファイルからテキストを読み込む
        with open(file_path_generator.combine(resultFolderName, WIND_VECTORS_FILENAME), "r") as file:
            text = file.read()
        # 正規表現パターンを定義
        pattern = re.compile(r"internalField\s+nonuniform\s+List<vector>\s+\d+\s*\(\s*(\([^)]+\)\s)*\)", re.DOTALL)
        # パターンに一致する部分を検索
        match = pattern.search(text)
        # マッチした部分があれば取得
        wind_vector_list = []
        if match:
            matched_all_str = match.group(0)
            coordinate_str_list = re.findall("\([0-9\.\- e]+\)", matched_all_str)
            wind_vector_list = [tuple(map(float, s.strip("()").split())) for s in coordinate_str_list]  # 文字列を各座標の風向・風速のタプルのリストに変換
        return wind_vector_list
    except Exception as e:
        logger.error(f"風況データ取得時にエラーが発生しました: {e}")

def get_temperatures(resultFolderName : str)  -> List[float]:
    try:
        # ファイルからテキストを読み込む
        with open(file_path_generator.combine(resultFolderName, TEMPERATURES_FILENAME), "r") as file:
            text = file.read()
        # 正規表現パターンを定義
        pattern = re.compile(r"internalField\s+nonuniform\s+List<scalar>\s+\d+\s*\(\s*([0-9\.e]+\s)*\)", re.DOTALL)
        # パターンに一致する部分を検索
        match = pattern.search(text)
        # マッチした部分があれば取得
        temperature_list = []
        if match:
            matched_all_str = match.group(0)
            matched_temp_str = re.search(r"\(\s([0-9\.e\s]*?)\)", matched_all_str)
            temperature_list = [float(s) for s in matched_temp_str.group(1).split()] 
        return temperature_list
    except Exception as e:
        logger.error(f"温度データ取得時にエラーが発生しました: {e}")

def get_nFaces_and_startFace(file_type_id : str, model_id : str) -> Dict[str, int]:
    try:
        # boundaryファイルを読み込む
        with open(file_path_generator.combine(
            file_path_generator.get_simulation_output_model_id_poly_mesh_folder_fs(model_id),
            BOUNDARY), "r") as file:
            text = file.read()
        # 正規表現パターンを定義
        pattern = re.compile(fr"\s+{file_type_id}\s+{{.*{N_FACES}\s+([\d]+);\s+{START_FACE}\s+([\d]+);\s+}}", re.DOTALL)
        #pattern = re.compile(fr"\s+{file_type_id}\s+{{", re.DOTALL)
        # パターンに一致する部分を検索
        match = pattern.search(text)
        # マッチした部分があれば取得
        nFaces_and_startFace = None
        if match:
            nFaces = match.group(1)
            startFace = match.group(2)
            nFaces_and_startFace = {N_FACES : int(nFaces), START_FACE : int(startFace)}
        return nFaces_and_startFace
    except Exception as e:
        logger.error(f"boundaryデータ取得時にエラーが発生しました: {e}")

def get_solar_irradiances(file_type_id : str, result_folder_name : str)  -> List[float]:
    try:
        # ファイルからテキストを読み込む
        with open(file_path_generator.combine(result_folder_name, SOLAR_IRRADIANCE_FILENAME), "r") as file:
            text = file.read()
        # 正規表現パターンを定義
        pattern = re.compile(fr"\s+{file_type_id}\s+{{.*?[\d]+\s+\(\s+([0-9\.e\s]*?)\s+\)\s+;\s+}}", re.DOTALL)
        # パターンに一致する部分を検索
        match = pattern.search(text)
        # マッチした部分があれば取得
        solar_irradiances = []
        if match:
            solar_irradiances_str = match.group(1)
            solar_irradiances = [float(s) for s in solar_irradiances_str.split()]
        return solar_irradiances
    except Exception as e:
        logger.error(f"qrデータ取得時にエラーが発生しました: {e}")

def get_boundary_cell_numbers_and_solar_irradiances(
        boundary : Dict[str, int], result_folder_name : str, model_id : str, file_type_id : str) -> List[Dict[str, int | float]]:
    try:
        # ownerファイルを読み込む
        with open(file_path_generator.combine(
            file_path_generator.get_simulation_output_model_id_poly_mesh_folder_fs(model_id),
            OWNER), "r") as file:
            text = file.read()
        # 正規表現パターンを定義
        pattern = re.compile(r"[\d]+\s+\(\s([\d\s]+?)\)", re.DOTALL)
        # パターンに一致する部分を検索
        match = pattern.search(text)
        face_owners = [int(s) for s in match.group(1).split()] 
        boundary_cell_numbers = face_owners[boundary[START_FACE]:boundary[START_FACE] + boundary[N_FACES]]
        solar_irradiances = get_solar_irradiances(file_type_id, result_folder_name)
        cell_numbers_and_solar_irradiances = []
        for i in range(len(boundary_cell_numbers)):
            boundary_cell_number_and_solar_irradiance = {"cell_num" : boundary_cell_numbers[i], "solar_irradiance" : solar_irradiances[i]}
            cell_numbers_and_solar_irradiances.append(boundary_cell_number_and_solar_irradiance)
        return cell_numbers_and_solar_irradiances
    except Exception as e:
        logger.error(f"ownerデータ取得時にエラーが発生しました: {e}")

def get_solar_irradiance_and_cell_num(model_id : str, result_folder_name : str)  -> List[List[Dict[str, int | float]]]:
    # boundaryファイルからnFacesとstartFaceの値を取得する
    ground_cell_numbers_and_solar_irradiances = []
    records = webapp_db_connection.get_ground_stl_type_ids()

    for record in records:
        stl_type_id = file_path_generator.get_copied_stl_filename_without_extention(record.stl_type_id)
        ground_nFace_startFace = get_nFaces_and_startFace(stl_type_id, model_id)
        if ground_nFace_startFace is not None:
            ground_cell_numbers_and_solar_irradiances += get_boundary_cell_numbers_and_solar_irradiances(
                ground_nFace_startFace, result_folder_name, model_id, stl_type_id)

    return ground_cell_numbers_and_solar_irradiances

def get_wbgts(solar_irradiances_and_cell_nums : List[Dict[str, int | float]],
              wind_strengths : List[float], temperatures : List[float]) -> List[float]:
    wbgts = []
    for i in range(len(solar_irradiances_and_cell_nums)):
        wbgts.append(get_wbgt(temperatures[i], HUMIDITY, solar_irradiances_and_cell_nums[i]["solar_irradiance"] * 0.001, wind_strengths[i]))
    return wbgts

def get_wind_strength(wind_vectors : List[Tuple[float, float, float]]) -> List[float]:
    return [math.sqrt(x**2 + y**2 + z**2) for x, y, z in wind_vectors]

def get_interpolate_color(start_color, end_color, fraction) -> Tuple[int, int, int, int]:
    # start_color から end_color までのRGBをfractionに基づいて補間
    interpolated_color = [
        int(start + fraction * (end - start)) for start, end in zip(start_color, end_color)
    ]
    return tuple(interpolated_color)

def create_wind_range_list(mid_val : float) -> List[Dict[str, float | Tuple[int, int, int, int]]]:
    # 風速の場合、基準風速を中間とする
    min_val = mid_val - (WIND_NUM_OF_COLER_FROM_INT * WIND_1_STEP)
    if min_val < 0:
        min_val = 0
    low_to_int_gradient_colors = [get_interpolate_color(
        LOWEST_COLOR, INTERMEDIATE_COLOR, i / (WIND_NUM_OF_COLER_FROM_INT - 1)) for i in range(WIND_NUM_OF_COLER_FROM_INT)]
    int_to_high_gradient_colors = [get_interpolate_color(
        INTERMEDIATE_COLOR, HIGHTEST_COLOR, i / (WIND_NUM_OF_COLER_FROM_INT - 1)) for i in range(WIND_NUM_OF_COLER_FROM_INT)]
    gradient_colors = low_to_int_gradient_colors + int_to_high_gradient_colors[1:]
    range_list = []
    for i in range(len(gradient_colors)):
        range_list.append({"threshold": min_val + (WIND_1_STEP * i), "rgba" : gradient_colors[i]})
    return range_list

def create_temp_range_list(mid_val : float) -> List[Dict[str, float | Tuple[int, int, int, int]]]:
    # 温度の場合、基準温度を最低とする
    low_to_int_gradient_colors = [get_interpolate_color(
        LOWEST_COLOR, INTERMEDIATE_COLOR, i / (TEMP_NUM_OF_COLER_FROM_INT - 1)) for i in range(TEMP_NUM_OF_COLER_FROM_INT)]
    int_to_high_gradient_colors = [get_interpolate_color(
        INTERMEDIATE_COLOR, HIGHTEST_COLOR, i / (TEMP_NUM_OF_COLER_FROM_INT - 1)) for i in range(TEMP_NUM_OF_COLER_FROM_INT)]
    gradient_colors = low_to_int_gradient_colors + int_to_high_gradient_colors[1:]
    range_list = []
    for i in range(len(gradient_colors)):
        range_list.append({"threshold": mid_val + (TEMP_1_STEP * i), "rgba" : gradient_colors[i]})
    return range_list


def get_color_from_range_list(
        range_list : List[Dict[str, float | Tuple[int, int, int, int]]], val : float) -> Tuple[int, int, int, int]:
    range_num = len(range_list)
    if val <= range_list[0]["threshold"]:
        return range_list[0]["rgba"]
    for i in range(0, range_num - 1):
        if range_list[i]["threshold"] < val <= range_list[i + 1]["threshold"]:
            return range_list[i]["rgba"]
    return range_list[range_num - 1]["rgba"]

def get_wind_colors_and_legend_label(wind_strengths : List[float], initial_wind_speed : float) -> Tuple[
    List[Tuple[int, int, int, int]], Dict[str, int]]:
    range_list = create_wind_range_list(initial_wind_speed)
    wind_colors = [get_color_from_range_list(range_list, wind_strength) for wind_strength in wind_strengths]
    label_min_max = {"min" : range_list[0]["threshold"], "max" : range_list[-1]["threshold"] + WIND_1_STEP}
    return wind_colors, label_min_max

def get_temp_colors_and_legend_label(temperatures : List[float], initial_temp : float) -> Tuple[
    List[Tuple[int, int, int, int]], Dict[str, int]]:
    range_list = create_temp_range_list(initial_temp)
    temp_colors = [get_color_from_range_list(range_list, temperature) for temperature in temperatures]
    label_min_max = {"min" : range_list[0]["threshold"], "max" : range_list[-1]["threshold"] + TEMP_1_STEP}
    return temp_colors, label_min_max

def get_wbgt_color(wbgt : float) -> List[int]:
    wbgt_color = []
    if wbgt >= WBGT_COLOR_DEFINITION[-2]["threshold"]:
        wbgt_color = WBGT_COLOR_DEFINITION[-1]["rgba"]
    else:
        for i in range(len(WBGT_COLOR_DEFINITION) - 1):
            if WBGT_COLOR_DEFINITION[i]["threshold"] > wbgt:
                wbgt_color = WBGT_COLOR_DEFINITION[i]["rgba"]
                return wbgt_color
    return wbgt_color

def get_wbgt_colors(wbgts : List[float]):
    return [get_wbgt_color(wbgt) for wbgt in wbgts]

def normalize_vector(xyz : tuple) -> Tuple[float, float, float]:
    x, y, z = xyz
    magnitude = math.sqrt(x**2 + y**2 + z**2)
    if magnitude == 0:
        return None
    return NORMALIZED_VECTOR_LENGTH * x/magnitude, NORMALIZED_VECTOR_LENGTH * y/magnitude, NORMALIZED_VECTOR_LENGTH * z/magnitude

def get_normalized_vectors(vectors : List[Tuple[float, float, float]]) -> List[Tuple[float, float, float]]:
    return [normalize_vector(xyz) for xyz in vectors]

# ベクトルを三角形で表示する場合の底辺の頂点を求める
def get_vector_triangle_bottom(
        wind_normalized_vectors : List[Tuple[float, float, float]]) -> Tuple[List[Tuple[float, float, float]], List[Tuple[float, float, float]]]:
    vector_triangle_bottom1 = []
    vector_triangle_bottom2 = []
    for wind_normalized_vector in wind_normalized_vectors:
        x, y, z = wind_normalized_vector
        vector_triangle_bottom1.append((y * -1 / TRIANGLE_BOTTOM_DIVIDE, x / TRIANGLE_BOTTOM_DIVIDE, z / TRIANGLE_BOTTOM_DIVIDE))
        vector_triangle_bottom2.append((y / TRIANGLE_BOTTOM_DIVIDE, x * -1 / TRIANGLE_BOTTOM_DIVIDE, z / TRIANGLE_BOTTOM_DIVIDE))
    return vector_triangle_bottom1, vector_triangle_bottom2

def get_endpoints(start_points, normalized_vectors) -> List[Tuple[float, float, float]]:
    endpoints = []
    for start_point, normalized_vector in zip(start_points, normalized_vectors):
        endpoint = (start_point[0] + normalized_vector[0],
                          start_point[1] + normalized_vector[1],
                          start_point[2] + normalized_vector[2])
        endpoints.append(endpoint)
    return endpoints

def get_filterd_list(original_list : List[Tuple[float, float, float]], indexes : []):
    filtered_list = []
    for i in indexes:
        filtered_list.append(original_list[i])
    return filtered_list

def get_min_hight(cell_centres : []) -> float:
    return min(cell_centre[2] for cell_centre in cell_centres)

def get_filterd_cell_centres_and_indexes(cell_centres : List[Tuple[float, float, float]], hight : float):
    min_hight = get_min_hight(cell_centres)
    cells_in_hight = [t for t in cell_centres if min_hight + hight - HIGHT_BUFFER <= t[2] <= min_hight + hight + HIGHT_BUFFER]
    indexes_in_hight = [i for i, t in enumerate(cell_centres) if min_hight + hight - HIGHT_BUFFER <= t[2] <= min_hight + hight + HIGHT_BUFFER]
    return cells_in_hight, indexes_in_hight

def get_boundary_cell_centres(cell_centres : List[Tuple[float, float, float]],
                              solar_irradiances_and_cell_nums : List[Dict[str, int | float]]) -> List[Tuple[float, float, float]]:
    boundary_cell_centres = []
    for solar_irradiance_and_cell_num in solar_irradiances_and_cell_nums:
        boundary_cell_centres.append(cell_centres[solar_irradiance_and_cell_num["cell_num"]])
    return boundary_cell_centres

def export_wind_visualization_file(
        file_path : str, height : float, cell_centres : List[Tuple[float, float, float]], 
        endpoints : List[Tuple[float, float, float]], colors : List[Tuple[int, int, int, int]], wind_strengths : List[float]) -> str:
    # jsonオブジェクトの作成
    doc = []
    # idオブジェクトの作成
    id_obj = {"id":"document", "name":"CZML Geometries: Polyline", "version":"1.0"}
    doc.append(id_obj)
    # 各点のオブジェクト作成
    for i in range(len(cell_centres)):
        cc_list = list(cell_centres[i])
        endpoint_list = list(endpoints[i])
        # 建物データはCesiumで表示するとジオイド高だけ浮くのでそれに合わせて調整
        cc_list[2] = cc_list[2] + GEOID_HEIGHT
        endpoint_list[2] = endpoint_list[2] + GEOID_HEIGHT
        doc.append(
            {
                "id":f"arrow{str(i)}",
                "name": f"風速:{str(round(wind_strengths[i], 3))} 経緯度:[{str(round(cc_list[0],6))},{str(round(cc_list[1],6))}]",
                "polyline" : {
                    "positions":{
                        "cartographicDegrees" : cc_list + endpoint_list
                    },
                    "material" : {
                        "polylineArrow":{
                            "color":{
                                "rgba":list(colors[i])
                            }
                        }
                    },
                    "arcType":"NONE",
                    "width":VECTOR_WIDTH
                }
            }
        )
    # ファイル出力
    visualization_file = file_path_generator.combine(file_path, f"{str(height)}.{FILE_TYPE_VISUALIZATION}")
    with open(visualization_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(visualization_file)

def export_wind_download_file(
        file_path : str,  height : float, cell_centres : List[Tuple[float, float, float]], 
        endpoints : List[Tuple[float, float, float]], triangle_bottoms1 : List[Tuple[float, float, float]],
        triangle_bottoms2 : List[Tuple[float, float, float]], wind_vector : List[Tuple[float, float, float]],
        colors : List[Tuple[int, int, int, int]], wind_strengths : List[float]) -> str:
    features = []
    # 各点のオブジェクト作成
    for i in range(len(cell_centres)):
        features.append(
            {
                "type":"Feature",
                "geometry": {
                    "type":"Polygon",
                    "coordinates":[
                        [
                            list(endpoints[i]),
                            list(triangle_bottoms1[i]),
                            list(triangle_bottoms2[i])
                        ]
                    ]
                },
                "properties" : {
                    "id":str(i),
                    "wind_vector" : wind_vector[i],
                    "wind_strength" : wind_strengths[i],
                    "color":"#{:02X}{:02X}{:02X}".format(*colors[i])
                }
            }
        )
    doc = {
        "type":"FeatureCollection",
        "features":features
    }
    # ファイル出力
    download_file = file_path_generator.combine(file_path, f"{str(height)}.{FILE_TYPE_DOWNLOAD}")
    with open(download_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(download_file)

def export_temp_visualization_file(file_path : str,
                                    height : float, cell_centres : [Tuple[float, float, float]], 
                                    colors : List[Tuple[int, int, int, int]], temperatures : List[float]) -> str:
    # jsonオブジェクトの作成
    doc = []
    # idオブジェクトの作成
    id_obj = {"id":"document", "name":"CZML Geometries: Point", "version":"1.0"}
    doc.append(id_obj)
    # 各点のオブジェクト作成
    for i in range(len(cell_centres)):
        cc_list = list(cell_centres[i])
        # 建物データはCesiumで表示するとジオイド高だけ浮くのでそれに合わせて調整
        cc_list[2] = cc_list[2] + GEOID_HEIGHT
        doc.append(
            {
                "id":f"dot{str(i)}",
                "name": f"気温:{str(round(temperatures[i], 2))} 経緯度:[{str(round(cc_list[0],6))},{str(round(cc_list[1],6))}]",
                "position": {"cartographicDegrees" : cc_list},
                "point": {
                    "color":{
                                "rgba":list(colors[i])
                    },
                    "pixelSize": POINT_PIXEL_SIZE
                }
            }
        )
    # ファイル出力
    visualization_file = file_path_generator.combine(file_path, f"{str(height)}.{FILE_TYPE_VISUALIZATION}")
    with open(visualization_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(visualization_file)

def export_temp_download_file(
        file_path : str, height : float, cell_centres : List[Tuple[float, float, float]],
        temperatures : List[float], colors : List[Tuple[int, int, int, int]]) -> str:
    features = []
    # 各点のオブジェクト作成
    for i in range(len(cell_centres)):
        features.append(
            {
                "type":"Feature",
                "geometry": {
                    "type":"Point",
                    "coordinates":list(cell_centres[i])
                },
                "properties" : {
                    "id":str(i),
                    "temperature" : temperatures[i],
                    "color":"#{:02X}{:02X}{:02X}".format(*colors[i])
                }
            }
        )
    doc = {
        "type":"FeatureCollection",
        "features":features
    }
    # ファイル出力
    download_file = file_path_generator.combine(file_path, f"{str(height)}.{FILE_TYPE_DOWNLOAD}")
    with open(download_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(download_file)

def export_wbgt_visualization_file(file_path : str, cell_centres : [Tuple[float, float, float]], 
                                    colors : List[Tuple[int, int, int, int]], wbgts : List[float]) -> str:
    # jsonオブジェクトの作成
    doc = []
    # idオブジェクトの作成
    id_obj = {"id":"document", "name":"CZML Geometries: Point", "version":"1.0"}
    doc.append(id_obj)
    # 各点のオブジェクト作成
    for i in range(len(cell_centres)):
        cc_list = list(cell_centres[i])
        # 建物データはCesiumで表示するとジオイド高だけ浮くのでそれに合わせて調整
        cc_list[2] = cc_list[2] + GEOID_HEIGHT
        doc.append(
            {
                "id":f"dot{str(i)}",
                "name": f"WBGT:{str(round(wbgts[i], 2))} 経緯度:[{str(round(cc_list[0],6))},{str(round(cc_list[1],6))}]",
                "position": {"cartographicDegrees" : cc_list},
                "point": {
                    "color":{
                                "rgba":list(colors[i])
                    },
                    "pixelSize": POINT_PIXEL_SIZE
                }
            }
        )
    # ファイル出力
    visualization_file = file_path_generator.combine(file_path, f"{WBGT_FILENAME}.{FILE_TYPE_VISUALIZATION}")
    with open(visualization_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(visualization_file)

def export_wbgt_download_file(
        file_path : str, cell_centres : List[Tuple[float, float, float]],
        wbgts : List[float], colors : List[Tuple[int, int, int, int]]) -> str:
    features = []
    # 各点のオブジェクト作成
    for i in range(len(cell_centres)):
        features.append(
            {
                "type":"Feature",
                "geometry": {
                    "type":"Point",
                    "coordinates":list(cell_centres[i])
                },
                "properties" : {
                    "id":str(i),
                    "WBGT" : wbgts[i],
                    "color":"#{:02X}{:02X}{:02X}".format(*colors[i])
                }
            }
        )
    doc = {
        "type":"FeatureCollection",
        "features":features
    }
    # ファイル出力
    download_file = file_path_generator.combine(file_path, f"{WBGT_FILENAME}.{FILE_TYPE_DOWNLOAD}")
    with open(download_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(download_file)

def create_output_data(
        model_id : str, coordinate_id : int, initial_wind_speed : float, initial_temp : float,
        height_list : List[Tuple[int, float]], wind_vectors : List[Tuple[float, float, float]],
        temperatures : List[float], cell_centres : List[Tuple[float, float, float]],
        solar_irradiances_and_cell_nums : List[Dict[str, int | float]]) -> List[Tuple[str, int, str, str, str]]:
    # converted_outputフォルダを作成する
    converted_output_model_id_folder = create_converted_output_model_id_folder(model_id)
    wind_visualization_folder = create_folder_by_type(converted_output_model_id_folder, FILE_TYPE_VISUALIZATION, RESULT_TYPE_WIND)
    wind_download_folder = create_folder_by_type(converted_output_model_id_folder, FILE_TYPE_DOWNLOAD, RESULT_TYPE_WIND)
    temp_visualization_folder = create_folder_by_type(converted_output_model_id_folder, FILE_TYPE_VISUALIZATION, RESULT_TYPE_TEMPERATURE)
    temp_download_folder = create_folder_by_type(converted_output_model_id_folder, FILE_TYPE_DOWNLOAD, RESULT_TYPE_TEMPERATURE)
    wbgt_visualization_folder = create_folder_by_type(converted_output_model_id_folder, FILE_TYPE_VISUALIZATION, RESULT_TYPE_WBGT)
    wbgt_download_folder = create_folder_by_type(converted_output_model_id_folder, FILE_TYPE_DOWNLOAD, RESULT_TYPE_WBGT)

    # 風の強さを求める
    wind_strengths = get_wind_strength(wind_vectors)
    # 風のベクトルの色を設定する
    wind_colors, wind_legend_label = get_wind_colors_and_legend_label(wind_strengths, initial_wind_speed)
    # 風の単位ベクトルの終点座標を求める
    wind_normalized_vectors = get_normalized_vectors(wind_vectors)
    wind_vector_endpoints = get_endpoints(cell_centres, wind_normalized_vectors)
    # 三角形の底辺の二つの頂点座標を取得する
    wind_vector_triangle_bottom1, wind_vector_triangle_bottom2  = get_vector_triangle_bottom(wind_normalized_vectors)
    wind_vector_endpoint_triangle_bottom1 = get_endpoints(cell_centres, wind_vector_triangle_bottom1)
    wind_vector_endpoint_triangle_bottom2 = get_endpoints(cell_centres, wind_vector_triangle_bottom2)

    # 温度の色を設定する
    temp_colors, temp_legend_label = get_temp_colors_and_legend_label(temperatures, initial_temp)

    visualizations = []

    for h in height_list:
        height = h.height
        # 指定の高さのセルとそのインデックスを取得する
        filtered_cell_centres, indexes = get_filterd_cell_centres_and_indexes(cell_centres, height)
        filtered_endpoints = get_filterd_list(wind_vector_endpoints, indexes)
        filtered_triangle_bottom1 = get_filterd_list(wind_vector_endpoint_triangle_bottom1, indexes)
        filtered_triangle_bottom2 = get_filterd_list(wind_vector_endpoint_triangle_bottom2, indexes)
        filtered_wind_vectors = get_filterd_list(wind_vectors, indexes)
        filtered_wind_colors = get_filterd_list(wind_colors, indexes)
        filtered_wind_strengths = get_filterd_list(wind_strengths, indexes)
        filtered_temperatures = get_filterd_list(temperatures, indexes)
        filtered_temp_colors = get_filterd_list(temp_colors, indexes)

        # セル中心の緯度経度を求める
        filtered_cell_centres_lonlat = convert_coordinates_to_lonlat(coordinate_id, filtered_cell_centres)
        # 風のベクトルの終点緯度経度を求める
        filtered_endpoints_lonlat = convert_coordinates_to_lonlat(coordinate_id, filtered_endpoints)
        # 風のベクトルの三角形の二つの頂点の緯度経度を取得する
        filtered_triangle_bottom1_lonlat = convert_coordinates_to_lonlat(coordinate_id, filtered_triangle_bottom1)
        filtered_triangle_bottom2_lonlat = convert_coordinates_to_lonlat(coordinate_id, filtered_triangle_bottom2)
        
        # 指定の高さのセルとそのインデックスを取得する            
        wind_visualization_filepath = export_wind_visualization_file(
            wind_visualization_folder, height, filtered_cell_centres_lonlat, filtered_endpoints_lonlat, 
            filtered_wind_colors, filtered_wind_strengths)
        wind_download_filepath = export_wind_download_file(
            wind_download_folder, height, filtered_cell_centres_lonlat, filtered_endpoints_lonlat, 
            filtered_triangle_bottom1_lonlat, filtered_triangle_bottom2_lonlat, filtered_wind_vectors,
            filtered_wind_colors, filtered_wind_strengths)
        visualizations.append(
            webapp_db_connection.Visualization(
                simulation_model_id = model_id,
                visualization_type = RESULT_TYPE_WIND,
                height_id = h.height_id,
                visualization_file = wind_visualization_filepath,
                geojson_file = wind_download_filepath,
                legend_label_higher = str(round(wind_legend_label["max"],1)),
                legend_label_lower = str(round(wind_legend_label["min"],1))
            )
        )
        
        temp_visualization_filepath = export_temp_visualization_file(temp_visualization_folder,
            height, filtered_cell_centres_lonlat, filtered_temp_colors, filtered_temperatures)
        temp_download_filepath = export_temp_download_file(temp_download_folder,
            height, filtered_cell_centres_lonlat, filtered_temperatures, filtered_temp_colors)
        visualizations.append(
            webapp_db_connection.Visualization(
                simulation_model_id = model_id,
                visualization_type = RESULT_TYPE_TEMPERATURE,
                height_id = h.height_id,
                visualization_file = temp_visualization_filepath,
                geojson_file = temp_download_filepath,
                legend_label_higher = str(round(temp_legend_label["max"],1)),
                legend_label_lower = str(round(temp_legend_label["min"],1))
            )
        )

    # WBGTを計算
    # 境界面に接するセルの中心座標を取得する
    boundary_cell_centres = get_boundary_cell_centres(cell_centres, solar_irradiances_and_cell_nums)
    boundary_cell_centres_lonlat = convert_coordinates_to_lonlat(coordinate_id, boundary_cell_centres)
    wbgts = get_wbgts(solar_irradiances_and_cell_nums, wind_strengths, temperatures)
    wbgt_colors = get_wbgt_colors(wbgts)
    wbgt_visualization_filepath = export_wbgt_visualization_file(wbgt_visualization_folder, boundary_cell_centres_lonlat, wbgt_colors, wbgts)
    wbgt_download_filepath = export_wbgt_download_file(wbgt_download_folder, boundary_cell_centres_lonlat, wbgts, wbgt_colors)
    # WBGTでは高さを表示しないが、height_idをNullにできないのでheightが最小のheight_idを代わりに代入する
    min_height = min(height_list, key=lambda x: x.height)
    visualizations.append(
        webapp_db_connection.Visualization(
            simulation_model_id = model_id,
            visualization_type = RESULT_TYPE_WBGT,
            height_id = min_height.height_id,
            visualization_file = wbgt_visualization_filepath,
            geojson_file = wbgt_download_filepath,
            # WBGTは凡例画像内に最大、最小のが表示されるため、値は空白とする
            legend_label_higher = "",
            legend_label_lower = ""
        )
    )
    return visualizations

def convert(model_id : str):
    logger.info('[%s] Start output data convert.'%model_id)
    result_folder_name = get_result_folder(model_id)
    cell_centres = get_cell_centres(result_folder_name)

    if (cell_centres is None or len(cell_centres) == 0):
        logger.error(log_writer.format_str(model_id,"セルの中心座標が取得できませんでした"))
        raise Exception
    model = webapp_db_connection.fetch_model(model_id)
    #initial_wind_speed/initial_temp/coordinate_idをmodelから取得
    initial_wind_speed =  model.simulation_model.wind_speed
    initial_temp = model.simulation_model.temperature
    coordinate_id = model.region.coordinate_id

    # 風況を取得する
    wind_vectors = get_wind_vectors(result_folder_name)
    if (wind_vectors is None or len(wind_vectors) == 0):
        logger.error(log_writer.format_str(model_id,"風況データが取得できませんでした"))
        raise Exception
    
    # 温度を取得する
    absolute_temperetures = get_temperatures(result_folder_name)
    if (absolute_temperetures is None or len(absolute_temperetures) == 0):
        logger.error(log_writer.format_str(model_id,"温度データが取得できませんでした"))
        raise Exception
    temperetures = [temperature_converter.convert_to_celsius(t) for t in absolute_temperetures]
    
    # 地面接地面のセル番号と日射量を取得する
    solar_irradiance_and_cell_num = get_solar_irradiance_and_cell_num(model_id, result_folder_name)
    if (solar_irradiance_and_cell_num is None or len(solar_irradiance_and_cell_num) == 0):
        logger.error(log_writer.format_str(model_id,"日射量データが取得できませんでした"))
        raise Exception
    visualizations = create_output_data(model_id, coordinate_id, initial_wind_speed, 
                                            initial_temp, webapp_db_connection.fetch_height(), wind_vectors, temperetures, cell_centres, solar_irradiance_and_cell_num)
    #VISUALIZATIONテーブルに挿入
    webapp_db_connection.fetch_and_delete_visualization(model_id)
    webapp_db_connection.insert_visualization(visualizations)   
    logger.info('[%s] Complete output data convert.'%model_id)
 

def main(model_id : str):
    log_writer.fileConfig()
    task_id = status_db_connection.TASK_OUTPUT_DATA_CONVERT
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_OUTPUT_DATA_TRANSFER、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)
    try:
        convert(model_id)
        #STATUS DBのレコードを取得Update
        status_db_connection.set_progress(
            model_id, status_db_connection.TASK_OUTPUT_DATA_CONVERT, status_db_connection.STATUS_NORMAL_END)

    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"シミュレーション結果変換サービス実行時エラー", e)
    


if __name__ == "__main__":
    model_id=utils.get_args(1)[0] 
    #model_id = sys.argv[1]
    main(model_id)
