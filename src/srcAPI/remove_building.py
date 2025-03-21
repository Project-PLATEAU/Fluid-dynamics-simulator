import sys
from city_model.three_d_model import *
from city_model.building import *
from common.coordinate_converter import *
from common.utils import *
from common.lockfile import *
from common.webapp_db_connection import *
from city_model.czml_for_edit_building import *
from city_model.bldg_file_for_edit_building import *
from city_model.three_d_model_for_edit_building import *

EPSG_CODE_LATLON = 4326 #wgs84
#region ジオイド高、楕円体高計算パラメータ

OBJ_EXTENSION = "obj"
class RemoveBuilding:
        def __init__(self, region_id: str, building_ids: List[str]) -> None:
            self.region_id = region_id
            self.building_ids = building_ids
            # building_idをstl_type_idごとに格納
            self.grouped_dict = Building.group_building_ids(building_ids)
            # stl_type_idごとにfilepathを取得
            # webapp_db_connectionのget_stl_file_list()
            # .obj/.stlを.czmlに書き換え
            # 該当ファイルにbldg_file.jsonのファイルパスを作成
            self.stl_file_list = []
            for stl_type_id in self.grouped_dict:
                stl_file = fetch_stl_file(self.region_id, stl_type_id)
                self.stl_file_list.append({
                    "stl_type_id": stl_type_id,
                    "stl_file": stl_file,
                    "czml_file": os.path.splitext(stl_file)[0] + ".czml",
                    "bldg_file": os.path.join(os.path.split(stl_file)[0], "bldg_file.json")
                })

        @staticmethod
        def is_obj_file(stl_file_str: str)->bool:
            return stl_file_str.lower().endswith(OBJ_EXTENSION)
        
        def remove(self):
            # CZMLファイルから削除される建物を削除
            for stl_file in self.stl_file_list:
                czml_fullpath = os.path.join(get_shared_folder(), stl_file["czml_file"])
                czml = CzmlFileForEditBuilding(czml_fullpath)
                czml.load()
                czml.remove_buildings(self.grouped_dict[stl_file["stl_type_id"]])
                czml.export()
            
                # bldg_fileから削除される建物を削除
                bldg_fullpath = os.path.join(get_shared_folder(), stl_file["bldg_file"])
                bldg = BldgFileForEditBuilding(bldg_fullpath)
                bldg.load()
                bldg.remove_buildings(self.grouped_dict[stl_file["stl_type_id"]])
                bldg.export()

                three_d_model_for_rm_building = None
                three_d_model_fullpath = os.path.join(get_shared_folder(), stl_file["stl_file"])
                if self.is_obj_file(stl_file["stl_file"]):
                    three_d_model_for_rm_building = ObjFileForRmBuilding(three_d_model_fullpath, bldg)
                else:
                    three_d_model_for_rm_building = StlFileForRmBuilding(three_d_model_fullpath, bldg)
                three_d_model_for_rm_building.load_and_export()
                # STLテーブルにファイルパスを設定
                # 対象フォルダからlockfileを削除
                stl_files = []
                for stl_file in self.stl_file_list:
                    update_czml_file(self.region_id, stl_file["stl_type_id"], 
                        stl_file["czml_file"])
                    stl_files.append(stl_file["stl_file"])
                LockMag(stl_files).delete_lockfile()

if __name__ == "__main__":
    print(f"{__file__} is called")
    region_id = sys.argv[1]
    building_ids = sys.argv[2:]
    c = RemoveBuilding(region_id, building_ids)
    c.remove()