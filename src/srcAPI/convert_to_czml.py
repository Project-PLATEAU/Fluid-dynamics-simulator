import sys
import os
from city_model.three_d_model import *
from city_model.building import *
from city_model.tree import ThreeDModelTreeList
from city_model.plant_cover import ThreeDModelPlantCoverList
from common.coordinate_converter import *
from common.file_path_generator import *
from common.utils import *
from common.webapp_db_connection import *

EPSG_CODE_LATLON = 4326 #wgs84

class ConvertToCZML:
    def __init__(self, region_id: str, filepath: str, stl_type_id: int, coordinate_id: int) -> None:
        self.region_id = region_id
        self.filepath = filepath
        # configから共有フォルダを取得してフルパス作成
        self.fullpath = os.path.join(get_shared_folder(), filepath)
        self.stl_type_id = stl_type_id
        self.coordinate_id = coordinate_id
        self.is_tree = True if webapp_db_connection.fetch_stl_type_tree() == stl_type_id else False
        self.is_plant_cover = True if webapp_db_connection.fetch_stl_type_plant_cover() == stl_type_id else False
    
    def convert(self):
        logger.info(f"convert is called")
        three_d_model = None
        if is_obj_file(self.fullpath):
            three_d_model = ObjFile(self.fullpath)
        else:
            three_d_model = StlFile(self.fullpath)
        three_d_model.load()
        if self.is_tree:
            object_list = ThreeDModelTreeList(three_d_model, self.stl_type_id)
        elif self.is_plant_cover:
            object_list = ThreeDModelPlantCoverList(three_d_model, self.stl_type_id)
        else:
            object_list = ThreeDModelBuildingList(three_d_model, self.stl_type_id)
        object_list.set_objects_details()
        # 緯度経度に変換用のConverterを作成
        converter = ConverterToLatLon(self.coordinate_id)
        # 中間ファイルを出力する
        object_list.export_to_objects_file()
        # 植被の場合、object_listを高さによって分割する。
        if self.is_plant_cover:
            object_list.split_by_height()

        # CZMLとして出力する
        object_list.export_to_czml(converter)
        # STLテーブルにファイルパスを設定
        webapp_db_connection.update_czml_file(self.region_id, self.stl_type_id, 
                                              os.path.splitext(self.filepath)[0] + ".czml")

if __name__ == "__main__":
    logger.info(f"{__file__} is called")
    region_id = sys.argv[1]
    filepath = sys.argv[2]
    stl_type_id = sys.argv[3]
    coordinate_id = sys.argv[4]
    c = ConvertToCZML(region_id, filepath, int(stl_type_id), int(coordinate_id))
    c.convert()