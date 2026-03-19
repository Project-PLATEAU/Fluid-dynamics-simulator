
import json
import os
import re
from typing import List

from common import webapp_db_connection
from .czml_for_edit_object import ICzmlForEditObject
from .constants import NEW_TREE_COLOR

class CzmlForEditTree(ICzmlForEditObject):
    def __init__(self, filepath: str) -> None:
        self.filepath = filepath
        self.czml = None
        self.new_tree_ids = []
        self.new_tree_coordinates = []
        self.new_tree_stl_type_name = None
        self.stl_type_id = webapp_db_connection.fetch_stl_type_tree()
        self.new_tree_stl_type_name = webapp_db_connection.fetch_stl_type_info(self.stl_type_id).stl_type_name

    def load(self):
        if not os.path.exists(self.filepath):
            # 存在しない場合は、空のczmlファイルを作成する。
            with open(self.filepath, "w") as f:
                json.dump([{"id":"document", 
                            "name":"CZML Geometries: Polyline", 
                            "version":"1.0"}], f)

        with open(self.filepath, "r") as f:
            self.czml = json.load(f)

    def create(self, coordinates: List[float], height: float, canopy_diameter: float):
        pattern = re.compile(r"(\d+)-(\d+)")
        max_tree_num = -1
        for element in self.czml:
            match = pattern.match(element["id"])
            if match:
                tree_num = int(match.group(2))  # tree_idのうち、単独木番号の方を取得
                if tree_num > max_tree_num:
                    max_tree_num = tree_num
        # coordinatesには[lon1,lat1,elevation1,lon2,lat2,elevation2,...]のように緯度経度標高が格納されている。
        # これを[[lon1,lat1,elevation1],[lon2,lat2,elevation2]...のように変換してnew_tree_coordinatesに格納する。
        self.new_tree_coordinates = [coordinates[i:i + 3] for i in range(0, len(coordinates), 3)]
        i = 0
        for new_tree_coordinate in self.new_tree_coordinates:
            i += 1
            new_tree_id = f"{str(self.stl_type_id)}-{str(max_tree_num + i)}"
            self.new_tree_ids.append(new_tree_id)
            bottom_centre_lat, bottom_centre_lon, elevation = new_tree_coordinate[1], new_tree_coordinate[0], new_tree_coordinate[2]
            self.czml.append(
                {
                    "id":f"{new_tree_id}",
                    "name": f"id:{new_tree_id}, Type:{self.new_tree_stl_type_name}",
                    "position" : {
                        "cartographicDegrees" : [
                            bottom_centre_lon, bottom_centre_lat, elevation + (height / 2)
                        ]
                    },
                    "cylinder": {
                        "length": height,
                        "topRadius": canopy_diameter / 2,
                        "bottomRadius": canopy_diameter / 2,
                        "material": {
                            "solidColor": {
                                "color": {
                                    "rgba": NEW_TREE_COLOR
                                }
                            }
                        }
                    }
                }
            )
        return

    def export(self):
        with open(self.filepath, 'w') as f:
            json.dump(self.czml, f)
        return

    def remove(self, trees_tobe_removed: List[str]):
        # trees_tobe_removedに含まれるid以外のidを持つデータをtree_removed_czmlにセット
        self.czml = [item for item in self.czml if item.get('id') not in trees_tobe_removed]


