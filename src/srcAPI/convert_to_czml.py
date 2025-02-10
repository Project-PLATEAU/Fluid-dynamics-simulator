import sys
import os
from city_model.three_d_model import *
from city_model.building import *
from common.coordinate_converter import *
from common.utils import *
from common.webapp_db_connection import *

EPSG_CODE_LATLON = 4326 #wgs84

OBJ_EXTENSION = "obj"
class ConvertToCZML:
    def __init__(self, region_id: str, filepath: str, stl_type_id: int, coordinate_id: int) -> None:
        self.region_id = region_id
        self.filepath = filepath
        # configから共有フォルダを取得してフルパス作成
        self.fullpath = os.path.join(get_shared_folder(), filepath)
        self.stl_type_id = stl_type_id
        self.coordinate_id = coordinate_id
        
    def is_obj_file(self)->bool:
        return self.fullpath.lower().endswith(OBJ_EXTENSION)
    
    def convert(self):
        three_d_model = None
        if self.is_obj_file():
            three_d_model = ObjFile(self.fullpath)
        else:
            three_d_model = StlFile(self.fullpath)
        three_d_model.load()
        building_list = ThreeDModelBuildingList(three_d_model, self.stl_type_id)
        building_list.set_buildings_details()
        # 緯度経度に変換用のConverterを作成
        converter = ConverterToLatLon(self.coordinate_id)
        # 中間ファイルを出力する
        building_list.export_to_bldg_file()
        # CZMLとして出力する
        building_list.export_to_czml(converter)
        # STLテーブルにファイルパスを設定
        update_czml_file(self.region_id, self.stl_type_id, 
                         os.path.splitext(self.filepath)[0] + ".czml")


if __name__ == "__main__":
    print(f"{__file__} is called")
    region_id = sys.argv[1]
    filepath = sys.argv[2]
    stl_type_id = sys.argv[3]
    coordinate_id = sys.argv[4]
    c = ConvertToCZML(region_id, filepath, int(stl_type_id), int(coordinate_id))
    c.convert()