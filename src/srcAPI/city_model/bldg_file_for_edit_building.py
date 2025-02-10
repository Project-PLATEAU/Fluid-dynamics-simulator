import json
from typing import List
import os
from typing import TYPE_CHECKING
if TYPE_CHECKING:
    from .building import *
class BldgFileForEditBuilding():
    def __init__(self, filepath: str) -> None:
        self.filepath = filepath
        self.bldg_file = None
        self.vertice_ids_tobe_removed = []
        self.new_index_list = []
        self.vertices_tobe_removed = []
        self.old_vertices_num = None
    
    def load(self):
        if not os.path.exists(self.filepath):
            # 存在しない場合は、空のbldgファイルを作成する。
            with open(self.filepath, "w") as f:
                json.dump({
                    "vertices": [],
                    "buildings": []
                    }, f)

        with open(self.filepath, 'r') as f:
                self.bldg_file = json.load(f)

    
    @staticmethod
    def get_new_index(old_index, vertice_ids_tobe_removed):
        # 建物削除前のindexをold_indexとする
        # 建物削除後のindexをnew_indexとして返却する
        # 削除対象の頂点はnew_indexをNoneとする
        i = 0
        new_index = None
        if old_index not in vertice_ids_tobe_removed:
            for id in vertice_ids_tobe_removed:
                if id < old_index:
                    i = i + 1
            new_index = old_index - i
        return new_index

    def remove_buildings(self, building_ids_tobe_removed: List[str]):
        buildings_tobe_removed = [item for item in self.bldg_file['buildings'] if item.get('id') in building_ids_tobe_removed]
        for b in buildings_tobe_removed:
            faces_tobe_removed = b['faces']
            for f in faces_tobe_removed:
                for v in f:
                    if v not in self.vertice_ids_tobe_removed:
                        self.vertice_ids_tobe_removed.append(v)
                        self.vertices_tobe_removed.append(self.bldg_file['vertices'][v])
        # 古い頂点番号を新しい番号に変換するmap、ただし削除された番号はNoneとなる
        new_index_map = map(lambda x: self.get_new_index(x, self.vertice_ids_tobe_removed), range(len(self.bldg_file['vertices'])))

        # self.bldg_file['vertices']から削除対象頂点番号を削除する
        self.bldg_file['vertices'] = [item for i, item in enumerate(self.bldg_file['vertices']) if i not in self.vertice_ids_tobe_removed]
        # buildingsから削除対象のidの要素を消す
        self.bldg_file['buildings'] = [item for item in self.bldg_file['buildings'] if item.get('id') not in building_ids_tobe_removed]
        # buildingsのfacesの番号をnew_index_mapで変換する
        self.new_index_list = list(new_index_map)
        for b in self.bldg_file['buildings']:
            for index, f in enumerate(b['faces']):
                b['faces'][index] = list(map(lambda i: self.new_index_list[i], f))

    def add_new_building(self, new_building: "BuildingForNewBuilding"):
        self.old_vertices_num = len(self.bldg_file['vertices'])

        # 新しい頂点を追加
        for vertice in new_building.vertices:
            self.bldg_file['vertices'].append(vertice['coordinate'])
        # 新しい建物を追加
        self.bldg_file['buildings'].append({
            'id' : new_building.building_id,
            'faces' : new_building.face_index_list
        })
        return
    
    def export(self):
        with open(self.filepath, 'w') as f:
            json.dump(self.bldg_file, f)

if __name__ == "__main__":
    bldg_file = BldgFileForEditBuilding("bldg_file.json")
    bldg_file.load()
    bldg_file.remove_buildings(["2-0"])
    bldg_file.export()
