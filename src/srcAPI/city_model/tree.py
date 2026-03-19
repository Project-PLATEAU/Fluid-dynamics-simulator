from common.file_path_generator import get_czml_filename, get_tree_file_filename
from .three_d_model_object_list import IThreeDModelObjectList
from .three_d_model import IThreeDModel
from .face import Face
from .czml_for_edit_tree import CzmlForEditTree
from .constants import TREE_COLOR, GEOID_HEIGHT
from common.coordinate_converter import ConverterToLatLon
from typing import List
from common import webapp_db_connection
from common.coordinate_converter import ConverterFromLatLon
import math
import os
import json


class Tree:
    def __init__(self,
                  face: Face, stl_type_id: int, tree_num: int) -> None:
        self.tree_id = self.get_tree_id(stl_type_id, tree_num)
        self.top_coordinate = None
        # 建物に含まれる頂点番号のリスト [0, 1, 2, 3, ...]
        self.vertice_indexes = []
        # 建物に含まれる面(Faceクラス)のリスト [Face1, Face2, ... ]
        self.faces = []
        type_info =  webapp_db_connection.fetch_stl_type_info(stl_type_id)
        self.type_name =  type_info.stl_type_name
        self.faces.append(face)
        self.vertice_indexes.extend(list(face.get_face()))
        self.vertices_dict = {}
        self.bottom_vertice_indexes = []
        self.height = 0
        self.bottom_faces = []
        self.radius = 0
        self.bottom_center = None
        self.top_center = None

    def is_vertice_exist_in_tree(self, face: Face)->bool:
        for vertice_number in face.get_face():
            if vertice_number in self.vertice_indexes:
                return True
        return False
    
    def add_vertice_indexes(self, face: Face):
        for vert_id in face.get_face():
                if vert_id not in self.vertice_indexes:
                    self.vertice_indexes.append(vert_id)    
        return

    def is_face_vert_id_added(self, face: Face)->bool:
        if self.is_vertice_exist_in_tree(face):
            self.faces.append(face)
            self.add_vertice_indexes(face)
            return True
        else:
            return False

    def iterate_faces_and_return_added_index(self, faces: List[Face])->int:
        for i, face in enumerate(faces):
            # facesから一つでもverticesに頂点番号が追加されたらそのindexを返す
            if self.is_face_vert_id_added(face):
                return i
        # faces内のどのfaceからもverticesに頂点番号を追加しなかったら、Noneを返す
        return None
    
    def set_vertices_dict(self, vertices: List[List[float]]):
        # vertices_dictに、vertices_indexをキー、その頂点の座標をバリューとする辞書として格納
        for index in self.vertice_indexes:
            self.vertices_dict[index] = list(vertices[index])
        return
    
    def set_bottom_vertice_indexes_and_height(self):
        # vertices_dictを頂点座標の標高で昇順にソートする
        vertices_indexes_sorted_by_elevation = sorted(self.vertices_dict, key=lambda k: self.vertices_dict[k][2])
        min_elevation_vertice_index = vertices_indexes_sorted_by_elevation[0]
        max_elevation_vertice_index = vertices_indexes_sorted_by_elevation[-1]
        # 単独木の高さを頂点の最高標高-最低標高として設定する
        self.height = self.vertices_dict[
            max_elevation_vertice_index][2] - self.vertices_dict[min_elevation_vertice_index][2]
        # 標高が低い点をの頂点番号をbottom_vertice_indexesに格納する
        for idx in vertices_indexes_sorted_by_elevation:
            if math.isclose(self.vertices_dict[min_elevation_vertice_index][2], 
                            self.vertices_dict[idx][2], abs_tol=1e-2):
                self.bottom_vertice_indexes.append(idx)
            else:
                break
        return
    @staticmethod
    def get_tree_id(stl_type_id: int, tree_num: int) -> str:
        return f"{stl_type_id}-{tree_num}"

class ThreeDModelTreeList(IThreeDModelObjectList):
    def __init__(self, three_d_model: IThreeDModel, stl_type_id: int) -> None:
        self.trees: List[Tree] = []
        self.faces: List[Face] = []
        self.vertices = three_d_model.get_vertices()
        self.full_filepath = three_d_model.get_filepath()
        for face_vert_id in three_d_model.get_face_vert_ids(): 
            self.faces.append(Face(face_vert_id))
        
        while len(self.faces) > 0:
            tree_num = len(self.trees)
            self.trees.append(Tree(self.faces.pop(0), stl_type_id, tree_num))
            added_face_index = 0
            while added_face_index is not None:
                added_face_index = self.trees[-1].iterate_faces_and_return_added_index(self.faces)
                if added_face_index is not None: del self.faces[added_face_index]

    def set_objects_details(self):
        for tree in self.trees:
            tree.set_vertices_dict(self.vertices)
            # 地表面の頂点番号リストを作成し、単独木高さを設定する
            tree.set_bottom_vertice_indexes_and_height()
            # 地表面の頂点を内包する最小の円の中心を求め、半径を設定する
            if tree.bottom_vertice_indexes:
                bottom_points = [tree.vertices_dict[i] for i in tree.bottom_vertice_indexes]
                tree.bottom_center = [sum(p[0] for p in bottom_points) / len(bottom_points),
                          sum(p[1] for p in bottom_points) / len(bottom_points), bottom_points[0][2]]
                tree.radius = max(math.dist(tree.bottom_center, p) for p in bottom_points)
                # tree.top_centerには、bottom_centerの標高に単独木の高さを加えた座標を設定する
                tree.top_center = tree.bottom_center[:2] + [tree.bottom_center[2] + tree.height]
        return
    
    def export_to_objects_file(self):
        trees = []
        for i in range(len(self.trees)):
            tree = self.trees[i]
            trees.append({
                "id": tree.tree_id,
                "p1": tree.top_center,
                "p2": tree.bottom_center,
                "radius": tree.radius
            })
        doc = {"trees": trees}
        tree_file = get_tree_file_filename(self.full_filepath)
        with open(tree_file, 'w') as f:
            json.dump(doc, f)
        return
    
    def export_to_czml(self, converter: ConverterToLatLon):
        doc = []
        # idオブジェクトの作成
        id_obj = {"id":"document", "name":"CZML Geometries: Polyline", "version":"1.0"}
        doc.append(id_obj)
        # 各点のオブジェクト作成
        for tree in self.trees:
            # 底面中心座標を緯度経度に変換
            bottom_center_lat, bottom_center_lon = converter.convert(tree.bottom_center[0], tree.bottom_center[1])
            # 高さはCesiumで表示するとジオイド高だけ沈むのでそれに合わせて調整
            elevation = tree.bottom_center[2] + GEOID_HEIGHT
            # 各点のオブジェクト作成
            doc.append(
                {
                    "id":f"{tree.tree_id}",
                    "name":f"id:{tree.tree_id}, Type:{tree.type_name}",
                    "position":{
                        "cartographicDegrees":[
                            bottom_center_lon, bottom_center_lat, elevation + (tree.height / 2)
                        ]
                    },
                    "cylinder": {
                        "length": tree.height,
                        "topRadius": tree.radius,
                        "bottomRadius": tree.radius,
                        "material": {
                            "solidColor": {
                                "color": {
                                    "rgba": TREE_COLOR
                                }
                            }
                        }
                    },
                }
            )
        # ファイル出力
        visualization_file = get_czml_filename(self.full_filepath)
        with open(visualization_file, 'w') as f:
            json.dump(doc, f)
        return

class TreeForNewTree:
    def __init__(self, czml: CzmlForEditTree, height: float, canopy_diameter: float, converter: ConverterFromLatLon) -> None:
        self.tree_ids = czml.new_tree_ids
        self.bottom_centers = []
        self.top_centers = []
        for coord in czml.new_tree_coordinates:
            # 緯度経度標高を座標に変換、yとxを入れ替え
            y, x = converter.convert(coord[1], coord[0])
            # Cesiumの標高はジオイド高が足された状態なので、座標変換する際はジオイド高を引く
            z = round(coord[2] - GEOID_HEIGHT, 6)
            self.bottom_centers.append([x, y, z])
            self.top_centers.append([x, y, z + height])

        self.height = height
        self.radius = canopy_diameter / 2
        self.converter = converter