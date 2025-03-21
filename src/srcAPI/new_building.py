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
class NewBuilding:
    def __init__(self, coordinates: List[float], height: float, region_id: str, stl_type_id: int) -> None:
        # 底面のすべての頂点の標高を、標高が一番低い頂点に合わせる。
        self.coordinates = self.set_min_elevation([coordinate for coordinate in coordinates])
        self.height = height
        self.region_id = region_id
        self.stl_type_id = stl_type_id
        # region_idでcoordinate_idを取得
        self.coordinate_id = fetch_coordinate_id(region_id)
        # stl_type_idごとにfilepathを取得
        # .obj/.stlを.czmlに書き換え
        # 該当ファイルにbldg_file.jsonのファイルパスを作成
        stl_file = fetch_stl_file(self.region_id, stl_type_id)
        self.stl_file_dict = {
            "stl_type_id": stl_type_id,
            "stl_file": stl_file,
            "czml_file": os.path.splitext(stl_file)[0] + ".czml",
            "bldg_file": os.path.join(os.path.split(stl_file)[0], "bldg_file.json")}

    @staticmethod
    def is_obj_file(stl_file_str: str)->bool:
        return stl_file_str.lower().endswith(OBJ_EXTENSION)
    
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
        # 3D都市モデルのフルパス
        three_d_model_fullpath = os.path.join(get_shared_folder(), self.stl_file_dict["stl_file"])
        # ファイルのディレクトリ部分が存在しない場合は作成
        directory = os.path.dirname(three_d_model_fullpath)
        if not os.path.exists(directory):
            os.makedirs(directory)

        # CZMLファイルに新規建物を追加
        czml_fullpath = os.path.join(get_shared_folder(), self.stl_file_dict["czml_file"])
        czml = CzmlFileForEditBuilding(czml_fullpath)
        czml.load()
        czml.create_building(self.coordinates, self.height, self.stl_type_id)
        czml.export()

        # bldg_fileを読み込み
        bldg_fullpath = os.path.join(get_shared_folder(), self.stl_file_dict["bldg_file"])
        bldg_file = BldgFileForEditBuilding(bldg_fullpath)
        bldg_file.load()

        # 緯度経度を座標に変換をするConverterを作成
        converter = ConverterFromLatLon(self.coordinate_id)
        # BuildingForNewBuildingオブジェクトを作成
        new_building = BuildingForNewBuilding(self.coordinates, self.height,
                                              converter, czml, bldg_file)
        new_building.create_vertices()
        new_building.create_faces()
        
        # bldg_fileに新規建物を追加
        bldg_file.add_new_building(new_building)
        bldg_file.export()

        three_d_model_for_new_building = None

        if self.is_obj_file(self.stl_file_dict["stl_file"]):
            three_d_model_for_new_building = ObjFileForNewBuilding(three_d_model_fullpath,
                                                                   bldg_file,
                                                                   new_building)
        else:
            three_d_model_for_new_building = StlFileForNewBuilding(three_d_model_fullpath, 
                                                                   bldg_file, 
                                                                   new_building)
        three_d_model_for_new_building.load_and_export()
        # STLテーブルにファイルパスを設定
        update_czml_file(self.region_id, self.stl_file_dict["stl_type_id"], 
                        self.stl_file_dict["czml_file"])
        # 対象フォルダからlockfileを削除
        lockfile = LockMag([self.stl_file_dict['stl_file']])
        lockfile.delete_lockfile()

if __name__ == "__main__":
    print(f"{__file__} is called")
    coordinates_str = sys.argv[1:-3]
    height = float(sys.argv[-3])
    region_id = sys.argv[-2]
    stl_type_id = int(sys.argv[-1])
    coordinates = [float(coordinate_str) for coordinate_str in coordinates_str]
    n =  NewBuilding(coordinates, height, region_id, stl_type_id)
    n.create()