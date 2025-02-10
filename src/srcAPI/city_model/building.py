from typing import List
from typing import Dict
from collections import defaultdict
from .three_d_model import IThreeDModel
from common.coordinate_converter import *
from common import webapp_db_connection
from .three_d_model_for_edit_building import *
from .czml_for_edit_building import *
from .bldg_file_for_edit_building import *
import json
from .triangulate import *
import os
import math

GEOID_HEIGHT = 36.7071
BUILDING_COLOR = [240, 240, 240, 255]

class Face:
    def __init__(self, face_vert_ids: List[int]) -> None:
        self.face_vert_id_0 = face_vert_ids[0]
        self.face_vert_id_1 = face_vert_ids[1]
        self.face_vert_id_2 = face_vert_ids[2]

    def get_face(self)->List[int]:
        return [self.face_vert_id_0, self.face_vert_id_1, self.face_vert_id_2]

class Building:
    def __init__(self, face: Face, stl_type_id: int, building_num: int) -> None:
        # 建物に含まれる頂点番号のリスト [0, 1, 2, 3, ...]
        self.vertice_indexes = []
        # 建物に含まれる面(Faceクラス)のリスト [Face1, Face2, ... ]
        self.faces = []
        self.building_id = self.get_building_id(stl_type_id, building_num)
        type_info =  webapp_db_connection.fetch_stl_type_info(stl_type_id)
        self.type_name =  type_info.stl_type_name
        self.faces.append(face)
        self.vertice_indexes.extend(list(face.get_face()))
        self.vertices_dict = {}
        self.bottom_vertice_indexes = []
        self.height = 0
        self.bottom_feature_edges = []
        self.bottom_faces = []
        self.sorted_bottom_indexes = []

    def is_vertice_exist_in_building(self, face: Face)->bool:
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
        if self.is_vertice_exist_in_building(face):
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
        # 建物の高さを頂点の最高標高-最低標高として設定する
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

    def set_bottom_faces(self):
        for face in self.faces:
            if all(elem in self.bottom_vertice_indexes for elem in face.get_face()):
                self.bottom_faces.append(face)
        return
    # def scan_face_vert_ids(self, face_vert_ids: List[])
    def set_bottom_feature_edges(self):
        # エッジの出現回数をカウントするための辞書
        edge_count = defaultdict(int)
        # 各三角形のエッジをリストアップし、カウント
        for face in self.bottom_faces:
            v1, v2, v3 = face.get_face()
            edges = [(v1, v2), (v2, v3), (v3, v1)]
            for edge in edges:
                # 順序に依存しないエッジを作成
                edge = tuple(sorted(edge))
                edge_count[edge] += 1
        # 出現回数が1回のエッジを抽出
        self.bottom_feature_edges = [edge for edge, count in edge_count.items() if count == 1]
        return
    
    # グラフを隣接リスト形式で表現
    @staticmethod
    def create_graph(edges):
        graph = defaultdict(list)
        for u, v in edges:
            graph[u].append(v)
            graph[v].append(u)
        return graph
    
    # オイラー閉路を取得する関数
    @staticmethod
    def find_eulerian_cycle(graph, start_vertex):
        # スタックを使ってオイラー閉路を探索
        stack = [start_vertex]
        path = []
        while stack:
            vertex = stack[-1]
            if graph[vertex]:
                # 次に進む頂点を取得し、その辺を削除
                next_vertex = graph[vertex].pop()
                graph[next_vertex].remove(vertex)
                stack.append(next_vertex)
            else:
                # 頂点に辺がなければ、それを経路に追加し、スタックから削除
                path.append(stack.pop())
        return path

    def set_sorted_bottom_indexes(self):
        # 底面を構成する辺のリストからなる図形を一筆書きするよう、頂点をソートする。
        # グラフを作成
        graph = self.create_graph(self.bottom_feature_edges)
        # 開始点を適当に選ぶ（グラフに含まれる任意の頂点）
        start_vertex = self.bottom_vertice_indexes[0]
        # オイラー閉路を求める
        cycle = self.find_eulerian_cycle(graph, start_vertex)
        self.sorted_bottom_indexes = cycle[:-1]
        return
    
    @staticmethod
    def get_building_id(stl_type_id: int, building_num: int)->str:
        # 各建物のbuilding_idを作成する
        return f"{stl_type_id}-{str(building_num)}"
    
    @staticmethod
    def group_building_ids(building_ids: List[str]) -> Dict[int,List[str]]:
        # building_id一覧をstl_type_id毎に辞書に格納する
        grouped_dict = {}
        for b in building_ids:
            # "-"で文字列を分割し、前の部分をキーとする
            key = int(b.split('-')[0])
            # キーが辞書にない場合、新しいリストを作成
            if key not in grouped_dict:
                grouped_dict[key] = []
            # 対応するリストに文字列を追加
            grouped_dict[key].append(b)
        return grouped_dict

class ThreeDModelBuildingList:
    def __init__(self, three_d_model: IThreeDModel, stl_type_id: str) -> None:
        self.buildings: List[Building] = []
        self.faces: List[Face] = []
        self.vertices = three_d_model.get_vertices()
        self.full_filepath = three_d_model.get_filepath()
        for face_vert_id in three_d_model.get_face_vert_ids():
            self.faces.append(Face(face_vert_id))

        while len(self.faces) > 0:
            building_num = len(self.buildings)
            self.buildings.append(Building(self.faces.pop(0), stl_type_id, building_num))
            added_face_index = 0
            while added_face_index is not None:
                added_face_index = self.buildings[-1].iterate_faces_and_return_added_index(self.faces)
                if added_face_index is not None: del self.faces[added_face_index] 
    
    def set_buildings_details(self):
        for building in self.buildings:
            building.set_vertices_dict(self.vertices)
            # 地表面の頂点番号リストを作成し、建物高さを設定する
            building.set_bottom_vertice_indexes_and_height()
            # 底面にある面をbottom_facesに格納
            building.set_bottom_faces()
            building.set_bottom_feature_edges()
            building.set_sorted_bottom_indexes()
        return
    
    def export_to_czml(self, converter: ConverterToLatLon):
        doc = []
        # idオブジェクトの作成
        id_obj = {"id":"document", "name":"CZML Geometries: Polyline", "version":"1.0"}
        doc.append(id_obj)
        # 各点のオブジェクト作成
        for building in self.buildings:
            latlon_coordinates = []
            for bottom_index in building.sorted_bottom_indexes:
                # 座標を緯度経度に変換する
                lat, lon = converter.convert(building.vertices_dict[bottom_index][0], building.vertices_dict[bottom_index][1])
                # 建物データはCesiumで表示するとジオイド高だけ沈むのでそれに合わせて調整
                elevation = building.vertices_dict[bottom_index][2] + GEOID_HEIGHT
                # CZMLは経度・緯度の順で記載する
                latlon_coordinates.append((lon, lat, elevation))
            
            doc.append(
                {
                    "id":f"{building.building_id}",
                    "name": f"id:{building.building_id}, Type:{str(building.type_name)}",
                    "polygon" : {
                        "positions":{
                            # 底面の頂点の緯度経度標高
                            "cartographicDegrees" : [cartographicDegree for coordinate in latlon_coordinates for cartographicDegree in coordinate]
                        },
                        "material" : {
                            "solidColor":{
                                "color":{
                                    "rgba": BUILDING_COLOR
                                }
                            }
                        },
                        # Cesium上で影を表示するかどうかの設定
                        "shadows":"ENABLED",
                        # 底面の標高
                        "height":latlon_coordinates[0][2],
                        # 天井面の標高
                        "extrudedHeight": latlon_coordinates[0][2] + building.height,
                        # 天井面を閉じるかどうかの設定
                        "closeTop": True,
                        # 底面を閉じるかどうかの設定
                        "closeBottom": True,
                    }
                }
            )
        # ファイル出力
        # TODO:STLファイルテーブル.czmlファイルに出力したファイルのファイルパスを設定する
        visualization_file = os.path.splitext(self.full_filepath)[0] + ".czml"
        with open(visualization_file, "w") as f:
            json.dump(doc, f)
        return visualization_file

    def export_to_bldg_file(self):
        buildings = []

        for i in range(len(self.buildings)):
            face_list = []
            for face in self.buildings[i].faces:
                face_list.append(face.get_face())
            buildings.append(
                {
                    "id": self.buildings[i].building_id,
                    "faces": face_list
                }
            )

        doc = {
            "vertices": self.vertices,
            "buildings": buildings
        }

        # ファイル出力

        bldg_file = os.path.join(os.path.split(self.full_filepath)[0], "bldg_file.json")
        with open(bldg_file, "w") as f:
            json.dump(doc, f)
        return

class BuildingForNewBuilding:
    def __init__(self, coordinates: List[float], height: float, converter: ConverterFromLatLon,
                 czml: CzmlFileForEditBuilding, bldg_file: BldgFileForEditBuilding) -> None:
        # 経度緯度標高をfloatのリストのリストに変換
        bottom_coordinates_lonlat_str = [coordinates[i:i+3] for i in range(0, len(coordinates), 3)]
        self.bottom_coordinates_lonlat = [[float(x) for x in coordinate] for coordinate in bottom_coordinates_lonlat_str]
        self.building_id = czml.new_building_id
        self.height = height
        self.converter = converter
        self.first_vertice_id = len(bldg_file.bldg_file['vertices'])
        self.vertices = []
        self.face_index_list = []

    def create_vertices(self):
        vertice_id = self.first_vertice_id
        # 底面の頂点番号と座標をverticesに登録
        for bottom_coordinate_lonlat in self.bottom_coordinates_lonlat:
            # 座標変換、yとxを入れ替え
            y, x = self.converter.convert(bottom_coordinate_lonlat[1], bottom_coordinate_lonlat[0])
            # Cesiumの標高はジオイド高が足された状態なので、座標変換する際はジオイド高を引く
            z = round(bottom_coordinate_lonlat[2] - GEOID_HEIGHT, 6)
            coordinate = [x, y, z]
            self.vertices.append(
                { 
                    "id": vertice_id, 
                    "coordinate": coordinate
                }
            )
            vertice_id += 1
        # 天井面の頂点座標を算出
        roof_coordinates = [[vertice["coordinate"][0], 
                                         vertice["coordinate"][1], 
                                         vertice["coordinate"][2] + self.height
                                         ] for vertice in self.vertices]
        # 天井面の頂点座標をverticesに登録
        for roof_coordinate in roof_coordinates:
            self.vertices.append(
                {
                    "id": vertice_id, 
                    "coordinate": roof_coordinate
                }
            )
            vertice_id += 1
        return
    
    def create_faces(self):
        # 底面を頂点番号一覧で取得
        self.bottom_face = self.vertices[:len(self.vertices) // 2]
        # 天井面を頂点番号一覧で取得
        self.roof_face = self.vertices[len(self.vertices) // 2:]
        self.side_faces = []
        # 側面の頂点番号一覧を取得
        for index in range(len(self.vertices) // 2):
            if index == len(self.vertices) // 2 - 1:
                self.side_faces.append([self.vertices[index],
                                        self.vertices[0],
                                        self.vertices[len(self.vertices) // 2],
                                        self.vertices[index + len(self.vertices) // 2]])
            else:
                self.side_faces.append([self.vertices[index],
                                        self.vertices[index + 1],
                                        self.vertices[index + len(self.vertices) // 2 + 1],
                                        self.vertices[index + len(self.vertices) // 2]])
        # 底面をtriangular化
        bottom_face_2d = [[v["coordinate"][0], v["coordinate"][1]] for v in self.bottom_face]
        triangulated_index_list = Triangulate(bottom_face_2d).triangulate()
        for triangulated_index in triangulated_index_list:
            converted_index_list = []
            for index in triangulated_index:
                converted_index_list.append(self.bottom_face[index]["id"])
            self.face_index_list.append(converted_index_list)
        # 天井面をtriangular化
        roof_face_2d = [[v["coordinate"][0], v["coordinate"][1]] for v in self.roof_face]
        triangulated_index_list = Triangulate(roof_face_2d).triangulate()
        for triangulated_index in triangulated_index_list:
            converted_index_list = []
            for index in triangulated_index:
                converted_index_list.append(self.roof_face[index]["id"])
            self.face_index_list.append(converted_index_list)
        # side_facesをtriangular化
        for side_face in self.side_faces:
            self.face_index_list.append([side_face[0]["id"], side_face[1]["id"], side_face[2]["id"]])
            self.face_index_list.append([side_face[2]["id"], side_face[3]["id"], side_face[0]["id"]])


