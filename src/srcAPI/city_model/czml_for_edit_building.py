import json
from typing import List
import re
from .czml_for_edit_object import ICzmlForEditObject
from .constants import NEW_BUILDING_COLOR
from common import webapp_db_connection
import os



class CzmlFileForEditBuilding(ICzmlForEditObject):
    def __init__(self, filepath: str) -> None:
        self.filepath = filepath
        self.czml = None
        self.new_building_id = None
        self.new_building_stl_type_name = None
    
    def load(self):
        if not os.path.exists(self.filepath):
            # 存在しない場合は、空のczmlファイルを作成する。
            with open(self.filepath, "w") as f:
                json.dump([{"id":"document", 
                            "name":"CZML Geometries: Polyline", 
                            "version":"1.0"}], f)

        with open(self.filepath, "r") as f:
            self.czml = json.load(f)

    def remove(self, buildings_tobe_removed: List[str]):
        # buildings_tobe_removedに含まれるid以外のidを持つデータをczmlにセット
        self.czml = [item for item in self.czml if item.get('id') not in buildings_tobe_removed]
    
    def create(self, coordinates: List[float], height: float, stl_type_id: int):
        pattern = re.compile(r"(\d+)-(\d+)")
        max_building_num = -1
        for element in self.czml:
            match = pattern.match(element["id"])
            if match:
                building_num = int(match.group(2))  # building_idのうち、建物番号の方を取得
                if building_num > max_building_num:
                    max_building_num = building_num
        self.new_building_id = f"{str(stl_type_id)}-{str(max_building_num + 1)}"
        self.new_building_stl_type_name = webapp_db_connection.fetch_stl_type_info(
            stl_type_id).stl_type_name
        self.czml.append(
            {
                "id":f"{self.new_building_id}",
                "name": f"id:{self.new_building_id}, Type:{self.new_building_stl_type_name}",
                "polygon" : {
                    "positions":{
                        # 底面の頂点の緯度経度標高
                        "cartographicDegrees" : coordinates
                    },
                    "material" : {
                        "solidColor":{
                            "color":{
                                "rgba": NEW_BUILDING_COLOR
                            }
                        }
                    },
                    # Cesium上で影を表示するかどうかの設定
                    "shadows":"ENABLED",
                    # 底面の標高
                    "height":coordinates[2],
                    # 天井面の標高
                    "extrudedHeight": coordinates[2] + height,
                    # 天井面を閉じるかどうかの設定
                    "closeTop": True,
                    # 底面を閉じるかどうかの設定
                    "closeBottom": True,
                }
            }
        )
        return

    def export(self):
        with open(self.filepath, 'w') as f:
            json.dump(self.czml, f)

if __name__ == "__main__":
    czml_file = CzmlFileForEditBuilding("converted_file.czml")
    czml_file.load()
    czml_file.remove(["2-0"])
    czml_file.export()
