
from typing import List
from city_model.plant_cover import *
from city_model.plant_cover_file_for_edit_plant_cover import PlantCoverFileForEditPlantCover
from city_model.three_d_model_for_edit_building import ObjFileForNewBuilding, StlFileForNewBuilding
from common.coordinate_converter import ConverterFromLatLon
from common.lockfile import LockMag
from common.webapp_db_connection import *
from common.file_path_generator import *
from common.utils import *
from city_model.czml_for_edit_plant_cover import CzmlForEditPlantCover

class NewPlantCover:
    def __init__(self, coordinates: List[float], height: float, region_id: str) -> None:
        # 底面のすべての頂点の標高を、標高が一番低い頂点に合わせる。
        self.coordinates = self.set_min_elevation([coordinate for coordinate in coordinates])
        self.height = height
        self.region_id = region_id
        # region_idでcoordinate_idを取得
        self.coordinate_id = fetch_coordinate_id(region_id)
        self.stl_type_id = fetch_stl_type_plant_cover()
        stl_file = fetch_stl_file(self.region_id, self.stl_type_id)
        self.stl_file_dict = {
            "stl_type_id": self.stl_type_id,
            "stl_file": stl_file,
            "h_stl_file": get_plant_cover_h_stl_filename(stl_file),
            "czml_file": get_czml_filename(stl_file),
            "plant_cover_h_file": get_plant_cover_h_filename(stl_file),
            "plant_cover_file": get_plant_cover_filename(stl_file)
        }
    @staticmethod
    def set_min_elevation(coordinates):
        # 標高を表す要素を抽出
        indices = [i for i in range(2, len(coordinates), 3)]
        min_value = min(coordinates[i] for i in indices)  # 3n-1番目の要素の中で最小値を取得
        # 3n-1番目の要素を最小値に置き換える
        for i in indices:
            coordinates[i] = min_value
        return coordinates
    
    def create(self):
        try:
            logger.info(f"new_plant_cover.create is called")
            # 3D都市モデルのフルパス
            three_d_model_fullpath = os.path.join(
                get_shared_folder(), 
                self.stl_file_dict["stl_file"]
                if self.height < PLANT_COVER_HEIGHT_THRESHOLD
                else self.stl_file_dict["h_stl_file"])
            # ファイルのディレクトリ部分が存在しない場合は例外発生
            directory = os.path.dirname(three_d_model_fullpath)
            if not os.path.exists(directory):
                raise FileNotFoundError(f"Directory does not exist: {directory}")
    
            # CZMLファイルに新規植被を追加
            czml_fullpath = os.path.join(get_shared_folder(), self.stl_file_dict["czml_file"])
            czml = CzmlForEditPlantCover(czml_fullpath)
            czml.load()
            czml.create(self.coordinates, self.height, self.stl_type_id)
            czml.export()
            
            # plant_cover.jsonまたはplant_cover_h.jsonから既存の植被を読み込み
            plant_cover_file_fullpath = os.path.join(
                get_shared_folder(), 
                self.stl_file_dict["plant_cover_h_file"] 
                if self.height >= PLANT_COVER_HEIGHT_THRESHOLD 
                else self.stl_file_dict["plant_cover_file"]
            )
            plant_cover_file = PlantCoverFileForEditPlantCover(plant_cover_file_fullpath)
            plant_cover_file.load()
            # 緯度経度を座標に変換をするConverterを作成
            converter = ConverterFromLatLon(self.coordinate_id)
            # PlantCoverForNewPlantCoverオブジェクトを作成
            new_plant_cover = PlantCoverForNewPlantCover(self.coordinates, self.height, 
                                                     converter, czml, plant_cover_file)
            new_plant_cover.create_vertices()
            new_plant_cover.create_faces()
    
            # plant_cover_fileに新規植被を追加
            plant_cover_file.add_new_plant_cover(new_plant_cover)
            plant_cover_file.export()
    
            three_d_model_for_new_plant_cover = None
            # 建物の場合と同一の処理なので、ObjFileForNewBuildingまたはStlFileForNewBuildingを使用する
            if is_obj_file(self.stl_file_dict["stl_file"]):
                three_d_model_for_new_plant_cover = ObjFileForNewBuilding(three_d_model_fullpath,
                                                                           plant_cover_file,
                                                                           new_plant_cover)
            else:
                three_d_model_for_new_plant_cover = StlFileForNewBuilding(three_d_model_fullpath,
                                                                           plant_cover_file,
                                                                           new_plant_cover)
            three_d_model_for_new_plant_cover.load_and_export()
            # STLテーブルにファイルパスを設定
            update_czml_file(self.region_id, self.stl_file_dict["stl_type_id"], 
                            self.stl_file_dict["czml_file"])
        except FileNotFoundError as fnf_error:
            logger.error(f"Error in NewPlantCover: File not found: {fnf_error}")
            sys.exit(1)
        except Exception as e:
            logger.error(f"Error in NewPlantCover: {e}")
            sys.exit(1)
        finally:
            # 対象フォルダからlockfileを削除
            lockfile = LockMag([self.stl_file_dict['stl_file']])
            lockfile.delete_lockfile()
            logger.info("NewPlantCover process finished.")


if __name__ == "__main__":
    logger.info(f"{__file__} is called")
    coordinates_str = sys.argv[1:-2]
    height = float(sys.argv[-2])
    region_id = sys.argv[-1]
    coordinates = [float(coordinate_str) for coordinate_str in coordinates_str]
    n =  NewPlantCover(coordinates, height, region_id)
    n.create()
