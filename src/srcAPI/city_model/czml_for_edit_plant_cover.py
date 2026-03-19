import json
from typing import List
import re

from .constants import *
from .czml_for_edit_object import ICzmlForEditObject
from common import webapp_db_connection
import os


class CzmlForEditPlantCover(ICzmlForEditObject):
    def __init__(self, filepath: str) -> None:
        self.filepath = filepath
        self.czml = None
        self.new_plant_cover_id = None
        self.new_plant_cover_stl_type_name = None

    def load(self):
        if not os.path.exists(self.filepath):
            # 存在しない場合は、空のczmlファイルを作成する。
            with open(self.filepath, "w") as f:
                json.dump([{"id":"document", 
                            "name":"CZML Geometries: Polyline", 
                            "version":"1.0"}], f)

        with open(self.filepath, "r") as f:
            self.czml = json.load(f)

    def remove(self, plant_covers_tobe_removed: List[str]):
        # plant_covers_tobe_removedに含まれるid以外のidを持つデータをczmlにセット
        self.czml = [item for item in self.czml if item.get('id') not in plant_covers_tobe_removed]

    def create(self, coordinates: List[float], height: float, stl_type_id: int):
        pattern = re.compile(r"(\d+)-(\d+)")
        max_plant_cover_num = -1
        for element in self.czml:
            match = pattern.match(element["id"])
            if match:
                plant_cover_num = int(match.group(2))
                if plant_cover_num > max_plant_cover_num:
                    max_plant_cover_num = plant_cover_num
        new_plant_cover_id_1st = str(stl_type_id)
        new_plant_cover_id_2nd = (
            str(max_plant_cover_num + 1) + HIGH_PLANT_COVER_SUFFIX_FOR_ID
            if height >= PLANT_COVER_HEIGHT_THRESHOLD
            else str(max_plant_cover_num + 1)
        )
        self.new_plant_cover_id = f"{new_plant_cover_id_1st}-{new_plant_cover_id_2nd}"
        self.new_plant_cover_stl_type_name = webapp_db_connection.fetch_stl_type_info(
            stl_type_id).stl_type_name
        self.czml.append(
            {
                "id":f"{self.new_plant_cover_id}",
                "name": f"id:{self.new_plant_cover_id}, Type:{self.new_plant_cover_stl_type_name}",
                "polygon" : {
                    "positions":{
                        # 底面の頂点の緯度経度標高
                        "cartographicDegrees" : coordinates
                    },
                    "material" : {
                        "solidColor":{
                            "color":{
                                "rgba": NEW_PLANT_COVER_COLOR
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
