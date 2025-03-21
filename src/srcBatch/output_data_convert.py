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
from typing import Type
import json
import numpy as np
from numpy import ndarray

logger = log_writer.getLogger()

#region OpenFOAM出力ファイルに関する定数
FNAME_CELL_CENTRES : str = "C"
FNAME_WIND_VECTORS : str = "U"
FNAME_TEMPERATURES : str = "T"
FNAME_HUMIDITIES : str = "s"
FNAME_SOLAR_IRRADIANCE : str = "qr"
BOUNDARY : str = "boundary"             # OpenFOAM内のboundaryファイル
OWNER : str = "owner"                   # OpenFOAM内のownerファイル
N_FACES = "nFaces"                      # グループに属する面の数のキー
START_FACE = "startFace"                # グループに属する面の最初の面番号のキー
#endregion
#region 出力フォルダ
## 可変性 [variability]
VARIABLE = 1        # 変動凡例値
FIXED = 2           # 固定凡例値
FIXED_MAX_WIND = 10 # 固定最大風速
FIXED_MIN_WIND = 0  # 固定最小風速
FIXED_MAX_TEMP = 36 # 固定最大温度
FIXED_MIN_TEMP = 29 # 固定最低温度
## 出力ファイル名、拡張子
FTYPE_VISUALIZATION = "czml"
FTYPE_DOWNLOAD = "geojson"
FNAME_WBGT = "wbgt"
## 可視化種別 [RESULT_TYPE]
WIND = 1
TEMPERATURE = 2
WBGT = 3
# endregion
#region 結果の図示における描画用パラメータ
NORMALIZED_VECTOR_LENGTH = 3            # 風のベクトルの長さ
VECTOR_WIDTH = 10                       # 風のベクトルの太さ
TRIANGLE_BOTTOM_DIVIDE = 6              # 風の向きを示す三角形で、底辺に対する単位ベクトルの長さ
HIGHT_BUFFER = 0.5                      # 地上n mのデータを取得するときの誤差範囲、例えば、地上1.5m だったら、地上1.5 プラスマイナスHIGHT_BUFFERのデータを取得する
WIND_NUM_OF_COLER_FROM_INT = 10         # 中間～最高または最低風速の色の段階数
TEMP_NUM_OF_COLER_FROM_INT = 10         # 中間～最高または最低温度の色の段階数
WIND_1_STEP = 0.2                       # 表示される色の1段階分の風速(m/s)
TEMP_1_STEP = 0.35                      # 表示される色の1段階分の温度(度)
HIGHTEST_COLOR = (255, 0, 0, 255)       # Red
INTERMEDIATE_COLOR = (0, 255, 0, 255)   # Green
LOWEST_COLOR = (0, 0, 255, 255)         # Blue
POINT_PIXEL_SIZE = 10                   # 点のピクセルサイズ
WBGT_COLOR_DEFINITION = [
    { "threshold" : 25, "rgba" : [0, 192, 255, 255] },  # 25未満：水色
    { "threshold" : 28, "rgba" : [255, 255, 0, 255] },  # 25 - 28 : 黄色
    { "threshold" : 31, "rgba" : [255, 165, 0, 255] },  # 28 - 31 : オレンジ色
    { "threshold" : None, "rgba" : [255, 0, 0, 255]}   # 31以上 : 赤色
]
#endregion
#region ジオイド高、楕円体高計算パラメータ
GEOID_HEIGHT = 36.7071
#endregion
#region 暑さ指数(WBGT)計算パラメータ
HUMIDITY = 50 # WBGT計算時の湿度（％）
def get_wbgt(ta : float, hd : float, sr : float, ws : float) -> float:
    wbgt = 0.735 * ta + 0.0374 * hd + 0.00292 * ta * hd + 7.619 * sr - 4.557 * sr ** 2 - 0.0572 * ws - 4.064
    return wbgt
#endregion

# <model_id>フォルダ作成
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

# 中心座標の一覧取得
def get_cell_centres(resultFolderName : str) -> []:
    try:
        with open(file_path_generator.combine(resultFolderName, FNAME_CELL_CENTRES), "r") as file:
            text = file.read()
            pattern = re.compile(r"internalField\s+nonuniform\s+List<vector>\s+\d+\s*\(\s*(\([^)]+\)\s)*\)", re.DOTALL)
            match = pattern.search(text)
            cell_centre_list = []
            if match:
                matched_all_str = match.group(0)
                coordinate_str_list = re.findall("\([0-9\.\- e]+\)", matched_all_str)
                cell_centre_list = np.array([tuple(map(float, s.strip("()").split())) for s in coordinate_str_list]) # 文字列を座標のタプルのリストに変換
            return cell_centre_list
    except Exception as e:
        logger.error(f"セル中心取得時にエラーが発生しました: {e}")

def get_boundary_cell_centres(cell_centres : List[Tuple[float, float, float]],
                              solar_irradiances_and_cell_nums : List[Dict[str, int | float]]) -> List[Tuple[float, float, float]]:
    boundary_cell_centres = []
    for solar_irradiance_and_cell_num in solar_irradiances_and_cell_nums:
        boundary_cell_centres.append(cell_centres[solar_irradiance_and_cell_num["cell_num"]])
    return np.array(boundary_cell_centres)

# region データ取得
def get_wind_vectors(resultFolderName : str): # 風況
    try:
        with open(file_path_generator.combine(resultFolderName, FNAME_WIND_VECTORS), "r") as file:               # 1.ファイルからテキストを読み込む
            text = file.read()
        pattern = re.compile(r"internalField\s+nonuniform\s+List<vector>\s+\d+\s*\(\s*(\([^)]+\)\s)*\)", re.DOTALL) # 2.正規表現パターンを定義
        match = pattern.search(text)                                                                                # 3.パターンに一致する部分を検索
        wind_vector_list = None
        if match:                                                                                                   # 4.マッチした部分があれば取得
            matched_all_str = match.group(0)
            coordinate_str_list = re.findall("\([0-9\.\- e]+\)", matched_all_str)
            wind_vector_list = np.array([tuple(map(float, s.strip("()").split())) for s in coordinate_str_list])    # 文字列を各座標の風向・風速の二次元配列に変換
        return wind_vector_list
    except Exception as e:
        logger.error(f"風況データ取得時にエラーが発生しました: {e}")

def get_temperatures(resultFolderName : str): # 温度
    try:
        with open(file_path_generator.combine(resultFolderName, FNAME_TEMPERATURES), "r") as file:
            text = file.read()
        pattern = re.compile(r"internalField\s+nonuniform\s+List<scalar>\s+\d+\s*\(\s*([0-9\.e]+\s)*\)", re.DOTALL)
        match = pattern.search(text)
        temperature_list = None
        if match:
            matched_all_str = match.group(0)
            matched_temp_str = re.search(r"\(\s([0-9\.e\s]*?)\)", matched_all_str)
            temperature_list = np.array([float(s) for s in matched_temp_str.group(1).split()])
        return temperature_list
    except Exception as e:
        logger.error(f"温度データ取得時にエラーが発生しました: {e}")

def get_humidities(resultFolderName : str): # 湿度
    try:
        with open(file_path_generator.combine(resultFolderName, FNAME_HUMIDITIES), "r") as file:
            text = file.read()
        pattern = re.compile(r"internalField\s+nonuniform\s+List<scalar>\s+\d+\s*\(\s*([0-9\.e]+\s)*\)", re.DOTALL)
        match = pattern.search(text)
        humidity_list = None
        if match:
            matched_all_str = match.group(0)
            matched_temp_str = re.search(r"\(\s([0-9\.e\s]*?)\)", matched_all_str)
            humidity_list = np.array([float(s) for s in matched_temp_str.group(1).split()])
        return humidity_list
    except Exception as e:
        logger.error(f"湿度データ取得時にエラーが発生しました: {e}")

def get_nFaces_and_startFace(file_type_id : str, model_id : str) -> Dict[str, int]: # 境界ファイルからnFacesとstartFaceを取得
    try:
        with open(file_path_generator.combine(
            file_path_generator.get_simulation_output_model_id_poly_mesh_folder_fs(model_id),BOUNDARY), "r") as file:
            text = file.read()
        pattern = re.compile(fr"\s+{file_type_id}\s+{{.*?{N_FACES}\s+([\d]+);\s+{START_FACE}\s+([\d]+);\s+}}", re.DOTALL)
        #pattern = re.compile(fr"\s+{file_type_id}\s+{{", re.DOTALL)
        match = pattern.search(text)
        nFaces_and_startFace = None
        if match:
            nFaces = match.group(1)
            startFace = match.group(2)
            nFaces_and_startFace = {N_FACES : int(nFaces), START_FACE : int(startFace)}
        return nFaces_and_startFace
    except Exception as e:
        logger.error(f"boundaryデータ取得時にエラーが発生しました: {e}")

def get_solar_irradiances(file_type_id : str, result_folder_name : str)  -> ndarray: # 日射量
    try:
        with open(file_path_generator.combine(result_folder_name, FNAME_SOLAR_IRRADIANCE), "r") as file:
            text = file.read()
        pattern = re.compile(fr"\s+{file_type_id}\s+{{[^}}]*?[\d]+\s*\(\s*([0-9\.e\s]*?)\s*\)\s*;\s+}}", re.DOTALL)
        match = pattern.search(text)
        solar_irradiances = None
        if match:
            solar_irradiances_str = match.group(1)
            solar_irradiances = np.array([float(s) for s in solar_irradiances_str.split()])
        return solar_irradiances
    except Exception as e:
        logger.error(f"qrデータ取得時にエラーが発生しました: {e}")

def get_boundary_cell_numbers_and_solar_irradiances( # 境界セル番号に対応する日射量を取得
        boundary : Dict[str, int], result_folder_name : str, model_id : str, file_type_id : str) -> List[Dict[str, int | float]]:
    try:
        with open(file_path_generator.combine(
            file_path_generator.get_simulation_output_model_id_poly_mesh_folder_fs(model_id), OWNER), "r") as file:
            text = file.read()
        pattern = re.compile(r"[\d]+\s+\(\s([\d\s]+?)\)", re.DOTALL)
        match = pattern.search(text)
        face_owners = np.array([int(s) for s in match.group(1).split()])
        boundary_cell_numbers = face_owners[boundary[START_FACE]:boundary[START_FACE] + boundary[N_FACES]]
        solar_irradiances = get_solar_irradiances(file_type_id, result_folder_name)
        cell_numbers_and_solar_irradiances = []
        for i in range(len(boundary_cell_numbers)):
            boundary_cell_number_and_solar_irradiance = {"cell_num" : boundary_cell_numbers[i], "solar_irradiance" : solar_irradiances[i]}
            cell_numbers_and_solar_irradiances.append(boundary_cell_number_and_solar_irradiance)
        return cell_numbers_and_solar_irradiances
    except Exception as e:
        logger.error(f"ownerデータ取得時にエラーが発生しました: {e}")

def get_solar_irradiance_and_cell_num(model_id : str, result_folder_name : str)  -> List[List[Dict[str, int | float]]]: # # 地面接地面と日射量
    ground_cell_numbers_and_solar_irradiances = []
    records = webapp_db_connection.get_ground_stl_type_ids()

    for record in records:
        stl_type_id = file_path_generator.get_copied_stl_filename_without_extention(record.stl_type_id)
        ground_nFace_startFace = get_nFaces_and_startFace(stl_type_id, model_id)
        if ground_nFace_startFace is not None:
            ground_cell_numbers_and_solar_irradiances += get_boundary_cell_numbers_and_solar_irradiances(
                ground_nFace_startFace, result_folder_name, model_id, stl_type_id)

    return ground_cell_numbers_and_solar_irradiances
# endregion

## CZML/GeoJson作成処理 ##
# converted_outputフォルダ作成
def create_converted_output_model_id_folder(model_id : str) -> str:
    file_path = file_path_generator.get_converted_output_model_id_folder_fs(model_id)
    file_controller.create_or_recreate_folder_fs(file_path)
    return file_path
# タイプ別 可視化フォルダ作成
def create_vi_folder(model_id_folder:str, variability:int, result_type:int) -> str:
    file_path = file_path_generator.combine(file_path_generator.combine(file_path_generator.combine(model_id_folder, str(variability)), FTYPE_VISUALIZATION), str(result_type))
    file_controller.create_or_recreate_folder_fs(file_path)
    return file_path
# タイプ別 DLフォルダ作成
def create_dl_folder(model_id_folder:str, variability:int, result_type:int) -> str:
    file_path = file_path_generator.combine(file_path_generator.combine(file_path_generator.combine(model_id_folder, str(variability)), FTYPE_DOWNLOAD), str(result_type))
    file_controller.create_or_recreate_folder_fs(file_path)
    return file_path

# 凡例範囲リスト作成
def create_gradient_colors(num_steps: int) -> List[Tuple[int, int, int, int]]:
    """
    赤～緑、緑～青で指定されたステップ数に基づいて色のグラデーションを生成します。
    """
    def get_interpolate_color(start_color, end_color, fraction) -> Tuple[int, int, int, int]:
        return tuple(int(start + fraction * (end - start)) for start, end in zip(start_color, end_color))

    front_part_colors = [get_interpolate_color(LOWEST_COLOR, INTERMEDIATE_COLOR, fraction / num_steps)
                         for fraction in range(num_steps + 1)]
    later_part_colors = [get_interpolate_color(INTERMEDIATE_COLOR, HIGHTEST_COLOR, fraction / num_steps)
                         for fraction in range(num_steps + 1)]
    gradient_colors = front_part_colors + later_part_colors[1:]

    return gradient_colors

# 風速：変動
def create_wind_range_list(mid_val : float) -> List[Dict[str, float | Tuple[int, int, int, int]]]:
    """
    基準風速を中間値として、風速の凡例リストを作成します。
    """
    min_val = mid_val - (WIND_NUM_OF_COLER_FROM_INT * WIND_1_STEP)
    gradient_colors = create_gradient_colors(WIND_NUM_OF_COLER_FROM_INT)

    range_list = []
    for i in range(len(gradient_colors)):
        range_list.append({"threshold": min_val + (WIND_1_STEP * i), "rgba" : gradient_colors[i]})
    return range_list

# 風速：固定
def create_fixed_wind_range_list() -> List[Dict[str, float | Tuple[int, int, int, int]]]:
    """
    固定最小風速を基に、風速の凡例リストを作成します。
    """
    gradient_colors = create_gradient_colors(WIND_NUM_OF_COLER_FROM_INT)

    range_list = []
    for i in range(len(gradient_colors)):
        range_list.append({"threshold": FIXED_MIN_WIND + (WIND_1_STEP * i), "rgba" : gradient_colors[i]})
    return range_list

# 温度：変動
def create_temp_range_list(mid_val: float) -> List[Dict[str, float | Tuple[int, int, int, int]]]:
    """
    基準温度を最低値として、温度の凡例リストを作成します。
    """
    gradient_colors = create_gradient_colors(TEMP_NUM_OF_COLER_FROM_INT)

    range_list = []
    for i in range(len(gradient_colors)):
        range_list.append({"threshold": mid_val + (TEMP_1_STEP * i), "rgba" : gradient_colors[i]})
    return range_list

# 温度：固定
def create_fixed_temp_range_list() -> List[Dict[str, float | Tuple[int, int, int, int]]]:
    """
    固定最小温度を基に、温度の凡例リストを作成します。
    """
    gradient_colors = create_gradient_colors(TEMP_NUM_OF_COLER_FROM_INT)

    range_list = []
    for i in range(len(gradient_colors)):
        range_list.append({"threshold": FIXED_MIN_TEMP + (TEMP_1_STEP * i), "rgba" : gradient_colors[i]})
    return range_list

# 指定高さのセルとインデックスを取得
def get_filterd_cell_centres_and_indexes(cell_centres : List[Tuple[float, float, float]], hight : float):
    min_hight = get_min_hight(cell_centres)
    cells_in_hight = np.array([t for t in cell_centres if min_hight + hight - HIGHT_BUFFER <= t[2] <= min_hight + hight + HIGHT_BUFFER])
    indexes_in_hight = np.array([i for i, t in enumerate(cell_centres) if min_hight + hight - HIGHT_BUFFER <= t[2] <= min_hight + hight + HIGHT_BUFFER])
    return cells_in_hight, indexes_in_hight

def get_min_hight(cell_centres : ndarray) -> float:
    return min(cell_centre[2] for cell_centre in cell_centres)

def get_filterd_list(original_list : List[Tuple[float, float, float]], indexes : ndarray):
    filtered_list = []
    for i in indexes:
        filtered_list.append(original_list[i])
    return np.array(filtered_list)

# 風況 #
def get_wind_strength(wind_vectors : List[Tuple[float, float, float]]) -> ndarray:
    return np.array([math.sqrt(x**2 + y**2 + z**2) for x, y, z in wind_vectors])

# 色設定#
def get_color_from_range_list(
        range_list : List[Dict[str, float | Tuple[int, int, int, int]]], val : float) -> Tuple[int, int, int, int]:
    range_num = len(range_list)
    if val <= range_list[0]["threshold"]:
        return range_list[0]["rgba"]
    for i in range(0, range_num - 1):
        if range_list[i]["threshold"] < val <= range_list[i + 1]["threshold"]:
            return range_list[i]["rgba"]
    return range_list[range_num - 1]["rgba"]

def get_wind_colors(range_list : List[Dict[str, float | Tuple[int, int, int, int]]], wind_strengths : List[float]):
    wind_colors = [get_color_from_range_list(range_list, wind_strength) for wind_strength in wind_strengths]
    return wind_colors

def get_temp_colors(range_list : List[Dict[str, float | Tuple[int, int, int, int]]], temperatures : List[float]) -> List[Tuple[int, int, int, int]]:
    temp_colors = [get_color_from_range_list(range_list, temperature) for temperature in temperatures]
    return temp_colors

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

def get_wbgts(solar_irradiances_and_cell_nums:List[Dict[str, int | float]],  wind_strengths:List[float], temperatures:List[float], humidities:List[float]) -> ndarray:
    wbgts = []
    for i in range(len(solar_irradiances_and_cell_nums)):
        wbgts.append(get_wbgt(temperatures[i], humidities[i], solar_irradiances_and_cell_nums[i]["solar_irradiance"] * 0.001, wind_strengths[i])) #HUMIDITY→humidity
    return np.array(wbgts)

# region ベクトル&座標
def convert_lonlat_3d(convert_to_latlon : Type[coordinate_converter.Convert_To_LatLon], xyz : tuple) -> tuple:
    x, y, z = xyz                                   # x, yは基準点からの東西方向の距離（東が正）南北方向の距離（北が正）
    lat, lon = convert_to_latlon.convert(y, x)      # 平面直角座標系ではx,yが逆転。南北を先、東西を後に渡す
    return (lon, lat, z)                            # czml, geojson形式では経度、緯度、高さの順に記述するため、その順で格納

def convert_coordinates_to_lonlat(coordinate_id : int, coordinates : ndarray):
    convert_to_latlon = coordinate_converter.Convert_To_LatLon(coordinate_id)
    converted_list = np.array([convert_lonlat_3d(convert_to_latlon, tuple_xyz) for tuple_xyz in coordinates])
    return converted_list

def normalize_vector(xyz : tuple) -> Tuple[float, float, float]:
    x, y, z = xyz
    magnitude = math.sqrt(x**2 + y**2 + z**2)
    if magnitude == 0:
        return None
    return NORMALIZED_VECTOR_LENGTH * x/magnitude, NORMALIZED_VECTOR_LENGTH * y/magnitude, NORMALIZED_VECTOR_LENGTH * z/magnitude

def get_normalized_vectors(vectors : List[Tuple[float, float, float]]) -> ndarray:
    return np.array([normalize_vector(xyz) for xyz in vectors])

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
    return np.array(endpoints)

# 未使用
def get_filterd_colors_list(original_list : List[Tuple[int, int, int, int]], indexes : ndarray):
    filtered_list = []
    for i in indexes:
        filtered_list.append(original_list[i])
    return filtered_list

# region データ変換
def export_wind_visualization_file(
        file_path : str, height : float, cell_centres : ndarray, 
        endpoints : ndarray, colors : List[Tuple[int, int, int, int]], wind_strengths : ndarray) -> str:
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
    visualization_file = file_path_generator.combine(file_path, f"{str(height)}.{FTYPE_VISUALIZATION}")
    with open(visualization_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(visualization_file)

def export_wind_download_file(
        file_path : str,  height : float, cell_centres : ndarray, 
        endpoints : ndarray, triangle_bottoms1 : ndarray,
        triangle_bottoms2 : ndarray, wind_vector : ndarray,
        colors : List[Tuple[int, int, int, int]], wind_strengths : ndarray) -> str:
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
                    "wind_vector" : tuple(wind_vector[i]),
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
    download_file = file_path_generator.combine(file_path, f"{str(height)}.{FTYPE_DOWNLOAD}")
    with open(download_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(download_file)

def export_temp_visualization_file(file_path : str,
                                    height : float, cell_centres : ndarray, 
                                    colors : List[Tuple[int, int, int, int]], temperatures : ndarray) -> str:
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
    visualization_file = file_path_generator.combine(file_path, f"{str(height)}.{FTYPE_VISUALIZATION}")
    with open(visualization_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(visualization_file)

def export_temp_download_file(
        file_path : str, height : float, cell_centres : ndarray,
        temperatures : ndarray, colors : List[Tuple[int, int, int, int]]) -> str:
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
    download_file = file_path_generator.combine(file_path, f"{str(height)}.{FTYPE_DOWNLOAD}")
    with open(download_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(download_file)

def export_wbgt_visualization_file(file_path : str, cell_centres : ndarray, 
                                    colors : List[Tuple[int, int, int, int]], wbgts : ndarray) -> str:
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
    visualization_file = file_path_generator.combine(file_path, f"{FNAME_WBGT}.{FTYPE_VISUALIZATION}")
    with open(visualization_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(visualization_file)

def export_wbgt_download_file(
        file_path : str, cell_centres : ndarray,
        wbgts : ndarray, colors : List[Tuple[int, int, int, int]]) -> str:
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
    download_file = file_path_generator.combine(file_path, f"{FNAME_WBGT}.{FTYPE_DOWNLOAD}")
    with open(download_file, "w") as f:
        json.dump(doc, f)
    return file_path_generator.get_folder_name_without_shared_folder_fs(download_file)
# endregion

# CZML/GeoJson作成処理
def create_output_data(
        model_id : str, coordinate_id : int, initial_wind_speed : float, initial_temp : float,
        height_list : List[float], wind_vectors : ndarray, temperatures : ndarray, humidities : ndarray,
        cell_centres : ndarray, solar_irradiances_and_cell_nums : List[Dict[str, int | float]]) -> List[Tuple[str, int, str, str, str]]:
    visualizations = []

    # converted_outputフォルダ作成
    co_model_id_folder = create_converted_output_model_id_folder(model_id)
    # タイプ別 可視化フォルダ作成
    wind_va_vi_folder = create_vi_folder(co_model_id_folder, VARIABLE, WIND)          # 1/czml/1 (変動、可視化、風況)
    temp_va_vi_folder = create_vi_folder(co_model_id_folder, VARIABLE, TEMPERATURE)   # 1/czml/2 (変動、可視化、温度)
    wbgt_va_vi_folder = create_vi_folder(co_model_id_folder, VARIABLE, WBGT)          # 1/czml/3 (変動、可視化、暑さ指数)
    wind_fi_vi_folder = create_vi_folder(co_model_id_folder, FIXED, WIND)             # 2/czml/1 (固定、可視化、風況)
    temp_fi_vi_folder = create_vi_folder(co_model_id_folder, FIXED, TEMPERATURE)      # 2/czml/2 (固定、可視化、温度)
    # タイプ別 DLフォルダ作成
    wind_va_dl_folder = create_dl_folder(co_model_id_folder, VARIABLE, WIND)          # 1/download/1 (変動、DL、風況)
    temp_va_dl_folder = create_dl_folder(co_model_id_folder, VARIABLE, TEMPERATURE)   # 1/download/2 (変動、DL、温度)
    wbgt_va_dl_folder = create_dl_folder(co_model_id_folder, VARIABLE, WBGT)          # 1/download/3 (変動、DL、暑さ指数)
    wind_fi_dl_folder = create_dl_folder(co_model_id_folder, FIXED, WIND)             # 2/download/1 (固定、DL、風況)
    temp_fi_dl_folder = create_dl_folder(co_model_id_folder, FIXED, TEMPERATURE)      # 2/download/2 (固定、DL、温度)

    # 凡例範囲リスト作成 #
     # 風況
    wind_va_legend_range_list = create_wind_range_list(initial_wind_speed)
    wind_va_legend_label_min_max = {"min" : wind_va_legend_range_list[0]["threshold"], "max" : wind_va_legend_range_list[-1]["threshold"] + WIND_1_STEP}
    wind_fi_legend_range_list = create_fixed_wind_range_list()
    wind_fi_legend_label_min_max = {"min": wind_fi_legend_range_list[0]["threshold"], "max": wind_fi_legend_range_list[-1]["threshold"] + WIND_1_STEP}
     # 温度
    temp_va_legend_range_list = create_temp_range_list(initial_temp)
    temp_va_legend_label_min_max = {"min" : temp_va_legend_range_list[0]["threshold"], "max" : temp_va_legend_range_list[-1]["threshold"] + TEMP_1_STEP}
    temp_fi_legend_range_list = create_fixed_temp_range_list()
    temp_fi_legend_label_min_max = {"min": temp_fi_legend_range_list[0]["threshold"], "max": temp_fi_legend_range_list[-1]["threshold"] + TEMP_1_STEP}

    # 出力ファイルを作成 #
    for h in height_list:
        height = h.height
        # 指定高さのセルとインデックスを取得
        flt_cell_centres, indexes = get_filterd_cell_centres_and_indexes(cell_centres, height)

        ## 風況　##
        flt_wind_vectors = get_filterd_list(wind_vectors, indexes)
        flt_wind_strengths = get_wind_strength(flt_wind_vectors)
        # 風のベクトルの色を設定
        flt_wind_va_colors = get_wind_colors(wind_va_legend_range_list, flt_wind_strengths)     # 変動
        flt_wind_fi_colors = get_wind_colors(wind_fi_legend_range_list, flt_wind_strengths)     # 凡例
        # 風の単位ベクトルの終点座標
        flt_wind_normalized_vectors = get_normalized_vectors(flt_wind_vectors)
        flt_wind_vector_endpoints = get_endpoints(flt_cell_centres, flt_wind_normalized_vectors)
        # 三角形の底辺の二つの頂点座標
        flt_wind_vec_triangle_bottom1, flt_wind_vec_triangle_bottom2  = get_vector_triangle_bottom(flt_wind_normalized_vectors)
        flt_wind_vec_endpoint_triangle_bottom1 = get_endpoints(flt_cell_centres, flt_wind_vec_triangle_bottom1)
        flt_wind_vec_endpoint_triangle_bottom2 = get_endpoints(flt_cell_centres, flt_wind_vec_triangle_bottom2)

        # セル中心の緯度経度
        flt_cell_centres_lonlat = convert_coordinates_to_lonlat(coordinate_id, flt_cell_centres)
        # 風のベクトルの終点緯度経度
        flt_endpoints_lonlat = convert_coordinates_to_lonlat(coordinate_id, flt_wind_vector_endpoints)
        # 風のベクトルの三角形の二つの頂点の緯度経度
        flt_triangle_bot1_lonlat = convert_coordinates_to_lonlat(coordinate_id, flt_wind_vec_endpoint_triangle_bottom1)
        flt_triangle_bot2_lonlat = convert_coordinates_to_lonlat(coordinate_id, flt_wind_vec_endpoint_triangle_bottom2)

        # 出力ファイルパス&結果追加　-変動va-
        wind_va_vi_filepath = export_wind_visualization_file(wind_va_vi_folder, height, flt_cell_centres_lonlat, flt_endpoints_lonlat, flt_wind_va_colors, flt_wind_strengths)
        wind_va_dl_filepath = export_wind_download_file(wind_va_dl_folder, height, flt_cell_centres_lonlat, flt_endpoints_lonlat,
            flt_triangle_bot1_lonlat, flt_triangle_bot2_lonlat, flt_wind_vectors, flt_wind_va_colors, flt_wind_strengths)
        visualizations.append( webapp_db_connection.Visualization(
                simulation_model_id = model_id, visualization_type = WIND, height_id = h.height_id, visualization_file = wind_va_vi_filepath, geojson_file = wind_va_dl_filepath,
                legend_label_higher = str(round(wind_va_legend_label_min_max["max"],1)), legend_label_lower = str(round(wind_va_legend_label_min_max["min"],1)), legend_type = VARIABLE
        ))
        # 出力ファイルパス&結果追加　-固定fi-
        wind_fi_vi_filepath = export_wind_visualization_file(wind_fi_vi_folder, height, flt_cell_centres_lonlat, flt_endpoints_lonlat, flt_wind_fi_colors, flt_wind_strengths)
        wind_fi_dl_filepath = export_wind_download_file(wind_fi_dl_folder, height, flt_cell_centres_lonlat, flt_endpoints_lonlat,
            flt_triangle_bot1_lonlat, flt_triangle_bot2_lonlat, flt_wind_vectors, flt_wind_fi_colors, flt_wind_strengths)
        visualizations.append( webapp_db_connection.Visualization(
                simulation_model_id = model_id, visualization_type = WIND, height_id = h.height_id, visualization_file = wind_fi_vi_filepath, geojson_file = wind_fi_dl_filepath,
                legend_label_higher = str(round(wind_fi_legend_label_min_max["max"],1)), legend_label_lower = str(round(wind_fi_legend_label_min_max["min"],1)), legend_type = FIXED
        ))

        ## 温度 ##
        # 温度の色を設定する
        flt_temperatures = get_filterd_list(temperatures, indexes)
        flt_temp_va_colors = get_temp_colors(temp_va_legend_range_list, flt_temperatures)     # 変動
        flt_temp_fi_colors = get_temp_colors(temp_fi_legend_range_list, flt_temperatures)     # 凡例

        # 出力ファイルパス&結果追加　-変動va-
        temp_va_vi_filepath = export_temp_visualization_file(temp_va_vi_folder, height, flt_cell_centres_lonlat, flt_temp_va_colors, flt_temperatures)
        temp_va_dl_filepath = export_temp_download_file(temp_va_dl_folder, height, flt_cell_centres_lonlat, flt_temperatures, flt_temp_va_colors)
        visualizations.append( webapp_db_connection.Visualization(
                simulation_model_id = model_id, visualization_type = TEMPERATURE, height_id = h.height_id,
                visualization_file = temp_va_vi_filepath, geojson_file = temp_va_dl_filepath,
                legend_label_higher = str(round(temp_va_legend_label_min_max["max"],1)), legend_label_lower = str(round(temp_va_legend_label_min_max["min"],1,)), legend_type = VARIABLE
        ))
        # 出力ファイルパス&結果追加　-固定fi-
        temp_fi_vi_filepath = export_temp_visualization_file(temp_fi_vi_folder, height, flt_cell_centres_lonlat, flt_temp_fi_colors, flt_temperatures)
        temp_fi_dl_filepath = export_temp_download_file(temp_fi_dl_folder, height, flt_cell_centres_lonlat, flt_temperatures, flt_temp_fi_colors)
        visualizations.append( webapp_db_connection.Visualization(
                simulation_model_id = model_id, visualization_type = TEMPERATURE, height_id = h.height_id,
                visualization_file = temp_fi_vi_filepath, geojson_file = temp_fi_dl_filepath,
                legend_label_higher = str(round(temp_fi_legend_label_min_max["max"],1)), legend_label_lower = str(round(temp_fi_legend_label_min_max["min"],1)), legend_type = FIXED
        ))

    ## WBGT ##
    # 境界面に接するセルの中心座標を取得
    boundary_cell_centres = get_boundary_cell_centres(cell_centres, solar_irradiances_and_cell_nums)
    boundary_cell_centres_lonlat = convert_coordinates_to_lonlat(coordinate_id, boundary_cell_centres)
    # WBGTを計算
    wbgts = get_wbgts(solar_irradiances_and_cell_nums, get_wind_strength(wind_vectors), temperatures, humidities)
    wbgt_colors = get_wbgt_colors(wbgts)
    wbgt_visualization_filepath = export_wbgt_visualization_file(wbgt_va_vi_folder, boundary_cell_centres_lonlat, wbgt_colors, wbgts)
    wbgt_download_filepath = export_wbgt_download_file(wbgt_va_dl_folder, boundary_cell_centres_lonlat, wbgts, wbgt_colors)
    # WBGTでは高さを表示しないが、height_idをNullにできないのでheightが最小のheight_idを代わりに代入する
    min_height = min(height_list, key=lambda x: x.height)
    # CZML追加
    visualizations.append(
        webapp_db_connection.Visualization(
            simulation_model_id = model_id, visualization_type = WBGT, height_id = min_height.height_id,
            visualization_file = wbgt_visualization_filepath, geojson_file = wbgt_download_filepath,
            legend_label_higher = "", legend_label_lower = "" # WBGTは凡例画像内に最大、最小のが表示されるため、値は空白
        )
    )
    return visualizations

# メイン変換処理
def convert(model_id : str):
    logger.info('[%s] Start output data convert.'%model_id)
    result_folder_name = get_result_folder(model_id)        # <model_id>フォルダ作成
    cell_centres = get_cell_centres(result_folder_name)     # 中心座標の一覧取得
    if (cell_centres is None or len(cell_centres) == 0):
        logger.error(log_writer.format_str(model_id,"セルの中心座標が取得できませんでした"))
        raise Exception

    model = webapp_db_connection.fetch_model(model_id)      # <model_id>よりデータ取得
    coordinate_id = model.region.coordinate_id              # 平面直角座標系ID
    initial_wind_speed =  model.simulation_model.wind_speed # 初期風速
    initial_temp = model.simulation_model.temperature       # 初期温度

    # データ取得
    wind_vectors = get_wind_vectors(result_folder_name)     # 風況取得
    if (wind_vectors is None or len(wind_vectors) == 0):
        logger.error(log_writer.format_str(model_id,"風況データが取得できませんでした"))
        raise Exception
    logger.info('[%s] Complete to get wind data.'%model_id)

    absolute_temperetures = get_temperatures(result_folder_name)    # 温度取得
    if (absolute_temperetures is None or len(absolute_temperetures) == 0):
        logger.error(log_writer.format_str(model_id,"温度データが取得できませんでした"))
        raise Exception
    temperetures = np.array([temperature_converter.convert_to_celsius(t) for t in absolute_temperetures])
    logger.info('[%s] Complete to get temperature data.'%model_id)

    absolute_humidities = get_humidities(result_folder_name)         # 湿度取得
    if (absolute_humidities is None or len(absolute_humidities) == 0):
        logger.error(log_writer.format_str(model_id,"湿度データが取得できませんでした"))
        raise Exception
    humidities = np.array([temperature_converter.convert_to_relative_humidity(h, t) for h,t in zip(absolute_humidities, absolute_temperetures)])
    logger.info('[%s] Complete to get humidity data.'%model_id)

    solar_irradiance_and_cell_num = get_solar_irradiance_and_cell_num(model_id, result_folder_name) # 地面接地面と日射量を取得
    if (solar_irradiance_and_cell_num is None or len(solar_irradiance_and_cell_num) == 0):
        logger.error(log_writer.format_str(model_id,"日射量データが取得できませんでした"))
        raise Exception
    logger.info('[%s] Complete to get solar irriadiance data.'%model_id)

    # CZML/GeoJson作成処理
    visualizations = create_output_data(model_id, coordinate_id, initial_wind_speed, initial_temp,
                                            webapp_db_connection.fetch_height(), wind_vectors, temperetures, humidities, cell_centres, solar_irradiance_and_cell_num)
    # レコード削除
    webapp_db_connection.fetch_and_delete_visualization(model_id)
    # リスト挿入
    webapp_db_connection.insert_visualization(visualizations)
    logger.info('[%s] Complete output data convert.'%model_id)

def main(model_id : str):
    log_writer.fileConfig()
    task_id = status_db_connection.TASK_OUTPUT_DATA_CONVERT
    # 対象レコード確認
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
    # model_id = sys.argv[1]
    #model_id = "e67696cb-a690-4a90-befe-4265611a9cd2"
    main(model_id)
