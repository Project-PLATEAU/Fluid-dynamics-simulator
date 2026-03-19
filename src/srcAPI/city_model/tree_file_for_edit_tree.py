from typing import List
import json
import os

from city_model.tree import TreeForNewTree


class TreeFileForEditTree():
    def __init__(self, filepath: str) -> None:
        self.filepath = filepath
        self.object_file = None
        self.vertice_ids_tobe_removed = []
        self.new_index_list = []
        self.vertices_tobe_removed = []
        self.old_vertices_num = None
       
    def load(self):
        if not os.path.exists(self.filepath):
            with open(self.filepath, "w") as f:
                json.dump({"trees": []}, f)
        with open(self.filepath, "r") as f:
            self.object_file = json.load(f)

    def add_new_tree(self, new_tree: TreeForNewTree) -> None:
        if self.object_file is None:
            self.load()
        for tree_id, bottom_center, top_center in zip(new_tree.tree_ids, new_tree.bottom_centers, new_tree.top_centers):
            self.object_file["trees"].append({
                "id": tree_id,
                "p1": top_center,
                "p2": bottom_center,
                "radius": new_tree.radius
            })
        return
    
    def remove_trees(self, tree_ids_tobe_removed: List[str]) -> None:       
        # self.object_file['trees']から削除対象のidの要素を消す
        self.object_file['trees'] = [item for item in self.object_file['trees'] if item.get('id') not in tree_ids_tobe_removed]
        return
    
    def export(self) -> None:
        # もし単独木が0個ならファイルを削除する
        if len(self.object_file['trees']) == 0:
            os.remove(self.filepath)
        else:
            with open(self.filepath, 'w') as f:
                json.dump(self.object_file, f)