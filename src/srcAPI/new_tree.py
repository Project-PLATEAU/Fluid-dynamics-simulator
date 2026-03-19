
from typing import List
import sys
from city_model.czml_for_edit_tree import CzmlForEditTree
from city_model.tree import TreeForNewTree
from city_model.tree_file_for_edit_tree import TreeFileForEditTree
from common.coordinate_converter import *


from common.file_path_generator import *
from common.lockfile import LockMag
from common.utils import *
from common.webapp_db_connection import *

class NewTree:
    def __init__(self, coordinates: List[float], height: float, canopy_diameter: float, region_id: str):
        # 底面のすべての頂点の標高を、標高が一番低い頂点に合わせる。
        self.coordinates = coordinates
        self.height = height
        self.canopy_diameter = canopy_diameter
        self.region_id = region_id
        # region_idでcoordinate_idを取得
        self.coordinate_id = fetch_coordinate_id(region_id)
        self.stl_type_id = fetch_stl_type_tree()
        stl_file = fetch_stl_file(self.region_id, self.stl_type_id)
        self.stl_file_dict = {
            "stl_file": stl_file,
            "czml_file": get_czml_filename(stl_file),
            "tree_file": get_tree_file_filename(stl_file)
        }


    def create(self):
        try:
            logger.info(f"new_tree.create is called")
            # 3D都市モデルのフルパス
            three_d_model_fullpath = os.path.join(get_shared_folder(), self.stl_file_dict["stl_file"])
            # ファイルのディレクトリ部分が存在しない場合はエラー
            directory = os.path.dirname(three_d_model_fullpath)
            if not os.path.exists(directory):
                raise FileNotFoundError(f"Directory does not exist: {directory}")
    
            # CZMLファイルに新規建物を追加
            czml_fullpath = os.path.join(get_shared_folder(), self.stl_file_dict["czml_file"])
            czml = CzmlForEditTree(czml_fullpath)
            czml.load()
            # CZMLに木を追加
            czml.create(self.coordinates, self.height, self.canopy_diameter)
            czml.export()
    
            # tree.jsonから既存の木を読み込み
            tree_file_fullpath = os.path.join(get_shared_folder(), self.stl_file_dict["tree_file"])
            tree_file = TreeFileForEditTree(tree_file_fullpath)
            tree_file.load()
            # 緯度経度に変換用のConverterを作成
            converter = ConverterFromLatLon(self.coordinate_id)
            # TreeForNewTreeオブジェクトを作成
            new_tree = TreeForNewTree(czml, self.height, self.canopy_diameter, converter)
    
            tree_file.add_new_tree(new_tree)
            tree_file.export()
    
            # STLテーブルにファイルパスを設定
            update_czml_file(self.region_id, self.stl_type_id, 
                            self.stl_file_dict["czml_file"])
        except FileNotFoundError as fnf_error:
            logger.error(f"Error in NewTree: File not found: {fnf_error}")
            sys.exit(1)
        except Exception as e:
            logger.error(f"Error in NewTree: {e}")
            sys.exit(1)
        finally:
            # 対象フォルダからlockfileを削除
            lockfile = LockMag([self.stl_file_dict['stl_file']])
            lockfile.delete_lockfile()
            logger.info("NewTree process finished.")

if __name__ == "__main__":
        logger.info(f"{__file__} is called")
        coordinates_str = sys.argv[1:-3]
        height = float(sys.argv[-3])
        canopy_diameter = float(sys.argv[-2])
        region_id = sys.argv[-1]
        coordinates = [float(coordinate_str) for coordinate_str in coordinates_str]
        n =  NewTree(coordinates, height, canopy_diameter, region_id)
        n.create()
