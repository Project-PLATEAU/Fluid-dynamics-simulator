import sys
from city_model.constants import HIGH_PLANT_COVER_SUFFIX_FOR_ID
from city_model.czml_for_edit_plant_cover import CzmlForEditPlantCover
from city_model.plant_cover_file_for_edit_plant_cover import PlantCoverFileForEditPlantCover
from city_model.three_d_model import *
from city_model.building import *
from city_model.tree_file_for_edit_tree import TreeFileForEditTree
from common.coordinate_converter import *
from common.file_path_generator import *
from common.utils import *
from common.lockfile import *
from common.webapp_db_connection import *
from city_model.czml_for_edit_building import *
from city_model.bldg_file_for_edit_building import *
from city_model.three_d_model_for_edit_building import *
from city_model.czml_for_edit_tree import CzmlForEditTree

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
        self.building_stl_file_dict_list = []
        self.tree_stl_file_dict = None
        self.plant_cover_stl_file_dict = None
        for stl_type_id in self.grouped_dict:
            stl_file = fetch_stl_file(self.region_id, stl_type_id)
            if stl_type_id == fetch_stl_type_tree() :
                self.tree_stl_file_dict = {
                    "stl_type_id": stl_type_id,
                    "stl_file": stl_file,
                    "czml_file": get_czml_filename(stl_file),
                    "tree_file": get_tree_file_filename(stl_file)
                }
            elif stl_type_id == fetch_stl_type_plant_cover():
                self.plant_cover_stl_file_dict = {
                    "stl_type_id": stl_type_id,
                    "stl_file": stl_file,
                    "stl_h_file": get_plant_cover_h_stl_filename(stl_file),
                    "czml_file": get_czml_filename(stl_file),
                    "plant_cover_h_file": get_plant_cover_h_filename(stl_file),
                    "plant_cover_file": get_plant_cover_filename(stl_file)
                }
            else:
                self.building_stl_file_dict_list.append({
                    "stl_type_id": stl_type_id,
                    "stl_file": stl_file,
                    "czml_file": get_czml_filename(stl_file),
                    "bldg_file": get_bldg_file_filename(stl_file)
                })
    
    def remove(self):
        logger.info(f"remove_building.create is called")
        # 建物削除処理
        for stl_file in self.building_stl_file_dict_list:
            try:
                # CZMLファイルから削除される建物を削除
                czml_fullpath = os.path.join(get_shared_folder(), stl_file["czml_file"])
                czml = CzmlFileForEditBuilding(czml_fullpath)
                czml.load()
                czml.remove(self.grouped_dict[stl_file["stl_type_id"]])
                czml.export()

                # bldg_fileから削除される建物を削除
                bldg_fullpath = os.path.join(get_shared_folder(), stl_file["bldg_file"])
                bldg = BldgFileForEditBuilding(bldg_fullpath)
                bldg.load()
                bldg.remove_buildings(self.grouped_dict[stl_file["stl_type_id"]])
                bldg.export()

                three_d_model_for_rm_building = None
                three_d_model_fullpath = os.path.join(get_shared_folder(), stl_file["stl_file"])
                if is_obj_file(stl_file["stl_file"]):
                    three_d_model_for_rm_building = ObjFileForRmBuilding(three_d_model_fullpath, bldg)
                else:
                    three_d_model_for_rm_building = StlFileForRmBuilding(three_d_model_fullpath, bldg)
                three_d_model_for_rm_building.load_and_export()
                # STLテーブルにファイルパスを設定
                update_czml_file(self.region_id, stl_file["stl_type_id"], stl_file["czml_file"])
            except Exception as e:
                logger.error(f"Error processing building {stl_file['stl_file']}: {e}")
                continue
            finally:
                # 対象フォルダからlockfileを削除
                LockMag([stl_file["stl_file"]]).delete_lockfile()
        # 単独木削除処理
        if self.tree_stl_file_dict is not None:
            try:
                # CZMLファイルから削除される単独木を削除
                czml_fullpath = os.path.join(get_shared_folder(), self.tree_stl_file_dict["czml_file"])
                czml = CzmlForEditTree(czml_fullpath)
                czml.load()
                czml.remove(self.grouped_dict[self.tree_stl_file_dict["stl_type_id"]])
                czml.export()
                # tree_fileから削除される単独木を削除
                tree_fullpath = os.path.join(get_shared_folder(), self.tree_stl_file_dict["tree_file"])
                tree = TreeFileForEditTree(tree_fullpath)
                tree.load()
                tree.remove_trees(self.grouped_dict[self.tree_stl_file_dict["stl_type_id"]])
                tree.export()
                update_czml_file(self.region_id, self.tree_stl_file_dict["stl_type_id"], 
                                 self.tree_stl_file_dict["czml_file"])
            except Exception as e:
                logger.error(f"Error processing remove tree: {e}")
            finally:
                LockMag([self.tree_stl_file_dict["stl_file"]]).delete_lockfile()
        # 植被削除処理
        if self.plant_cover_stl_file_dict is not None:
            try:
                # CZMLファイルから削除される植被を削除
                czml_fullpath = os.path.join(get_shared_folder(), self.plant_cover_stl_file_dict["czml_file"])
                czml = CzmlForEditPlantCover(czml_fullpath)
                czml.load()
                czml.remove(self.grouped_dict[self.plant_cover_stl_file_dict["stl_type_id"]])
                czml.export()
                # plant_cover_fileから削除される植被を削除
                plant_cover_ids_tobe_removed = []
                h_plant_cover_ids_tobe_removed = []
                for plant_cover_id in self.grouped_dict[self.plant_cover_stl_file_dict["stl_type_id"]]:
                    if plant_cover_id.endswith(HIGH_PLANT_COVER_SUFFIX_FOR_ID):
                        h_plant_cover_ids_tobe_removed.append(plant_cover_id)
                    else:
                        plant_cover_ids_tobe_removed.append(plant_cover_id)

                plant_cover_file = self.__rm_plant_covers_in_plant_cover_file(
                    plant_cover_ids_tobe_removed,
                    self.plant_cover_stl_file_dict["plant_cover_file"]
                )
                h_plant_cover_file = self.__rm_plant_covers_in_plant_cover_file(
                    h_plant_cover_ids_tobe_removed,
                    self.plant_cover_stl_file_dict["plant_cover_h_file"]
                )

                three_d_model_for_rm_plant_cover = None
                three_d_model_for_rm_h_plant_cover = None
                three_d_model_fullpath = os.path.join(get_shared_folder(), 
                                                      self.plant_cover_stl_file_dict["stl_file"])
                three_d_model_h_fullpath = os.path.join(get_shared_folder(), 
                                                        self.plant_cover_stl_file_dict["stl_h_file"])
                if is_obj_file(self.plant_cover_stl_file_dict["stl_file"]):
                    if plant_cover_file is not None:
                        three_d_model_for_rm_plant_cover = ObjFileForRmBuilding(three_d_model_fullpath, plant_cover_file)
                    if h_plant_cover_file is not None:
                        three_d_model_for_rm_h_plant_cover = ObjFileForRmBuilding(three_d_model_h_fullpath, h_plant_cover_file)
                else:
                    if plant_cover_file is not None:
                        three_d_model_for_rm_plant_cover = StlFileForRmBuilding(three_d_model_fullpath, plant_cover_file)
                    if h_plant_cover_file is not None:
                        three_d_model_for_rm_h_plant_cover = StlFileForRmBuilding(three_d_model_h_fullpath, h_plant_cover_file)
                if three_d_model_for_rm_h_plant_cover is not None:
                    three_d_model_for_rm_h_plant_cover.load_and_export()
                if three_d_model_for_rm_plant_cover is not None:
                    three_d_model_for_rm_plant_cover.load_and_export()
                # STLテーブルにファイルパスを設定
                update_czml_file(self.region_id, self.plant_cover_stl_file_dict["stl_type_id"], 
                                 self.plant_cover_stl_file_dict["czml_file"])
            except Exception as e:
                logger.error(f"Error processing plant cover: {e}")
            finally:
                # 対象フォルダからlockfileを削除
                LockMag([self.plant_cover_stl_file_dict["stl_file"]]).delete_lockfile()

    def __rm_plant_covers_in_plant_cover_file(self,
                                                  plant_cover_ids_tobe_removed: List[str], 
                                                  plant_cover_file: str) -> PlantCoverFileForEditPlantCover:
        if len(plant_cover_ids_tobe_removed) == 0:
            return None
        plant_cover_fullpath = os.path.join(get_shared_folder(), plant_cover_file)
        plant_cover = PlantCoverFileForEditPlantCover(plant_cover_fullpath)
        plant_cover.load()
        plant_cover.remove_plant_covers(plant_cover_ids_tobe_removed)
        plant_cover.export()
        return plant_cover

if __name__ == "__main__":
    logger.info(f"{__file__} is called")
    region_id = sys.argv[1]
    building_ids = sys.argv[2:]
    c = RemoveBuilding(region_id, building_ids)
    c.remove()