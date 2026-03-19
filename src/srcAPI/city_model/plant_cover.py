from city_model.czml_for_edit_plant_cover import CzmlForEditPlantCover
from city_model.plant_cover_file_for_edit_plant_cover import PlantCoverFileForEditPlantCover
from .constants import *
from common.file_path_generator import *
import json
from shutil import copyfile
from typing import List
from collections import defaultdict

import os

from .three_d_model_for_edit_building import ObjFileForRmBuilding, StlFileForRmBuilding

from .three_d_model_object_list import IThreeDModelObjectList
from .three_d_model import IThreeDModel
from .face import Face
from common import webapp_db_connection
from common.coordinate_converter import ConverterFromLatLon, ConverterToLatLon
from .triangulate import *

class PlantCover:
    def __init__(self, face: Face, stl_type_id: int, plant_cover_num: int) -> None:
        # 植被に含まれる頂点番号のリスト [0, 1, 2, 3, ...]
        self.vertice_indexes = []
        # 植被に含まれる面(Faceクラス)のリスト [Face1, Face2, ... ]
        self.faces = []
        self.plant_cover_id = self.generate_plant_cover_id(stl_type_id, plant_cover_num)
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
        i = 0
        for idx in vertices_indexes_sorted_by_elevation:  
            if i < len(vertices_indexes_sorted_by_elevation) / 2:
                self.vertices_dict[idx][2] = self.vertices_dict[min_elevation_vertice_index][2]
            # TODO:底面がデコボコしている場合があるので、標高が低い方の点をmin_elevation_vertice_indexの標高と同じ高さとしてしまう。
            #if math.isclose(self.vertices_dict[min_elevation_vertice_index][2], 
            #                self.vertices_dict[idx][2], abs_tol=1e-2):
                self.bottom_vertice_indexes.append(idx)
            else:
                break
            i += 1
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

    def add_suffix_to_plant_cover_id(self, suffix: str):
        self.plant_cover_id += suffix

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
        # 開始点を適当に選ぶ（self.bottom_feature_edgesに含まれる任意の頂点）
        #start_vertex = self.bottom_vertice_indexes[0]
        start_vertex = None
        for bottom_vertice_index in self.bottom_vertice_indexes:
            if any(bottom_vertice_index in e for e in self.bottom_feature_edges):
                start_vertex = bottom_vertice_index
                break
        if start_vertex is None:
            print("Error start_vertex is None")
        # オイラー閉路を求める
        cycle = self.find_eulerian_cycle(graph, start_vertex)
        self.sorted_bottom_indexes = cycle[:-1]
        return
    
    @staticmethod
    def generate_plant_cover_id(stl_type_id: int, plant_cover_num: int)->str:
        # 各植被のplant_cover_idを作成する
        return f"{stl_type_id}-{str(plant_cover_num)}"
    
    def get_height(self) -> float:
        # 植被の高さを取得する
        return self.height

    def get_plant_cover_id(self) -> str:
        # 植被のIDを取得する
        return self.plant_cover_id

class ThreeDModelPlantCoverList(IThreeDModelObjectList):
    def __init__(self, three_d_model: IThreeDModel, stl_type_id: str) -> None:
        self.plant_covers: List[PlantCover] = []
        self.faces: List[Face] = []
        self.vertices = three_d_model.get_vertices()
        self.full_filepath = three_d_model.get_filepath()
        for face_vert_id in three_d_model.get_face_vert_ids():
            self.faces.append(Face(face_vert_id))
        self.h_full_filepath = os.path.join(f"{os.path.dirname(self.full_filepath)}", f"H_{os.path.basename(self.full_filepath)}")
        self.plant_cover_filepath = os.path.join(f"{os.path.dirname(self.full_filepath)}", "plant_cover.json")
        self.h_plant_cover_filepath = os.path.join(f"{os.path.dirname(self.full_filepath)}", "H_plant_cover.json")
        self.high_plant_covers = []
        self.low_plant_covers = []


        while len(self.faces) > 0:
            plant_cover_num = len(self.plant_covers)
            self.plant_covers.append(PlantCover(self.faces.pop(0), stl_type_id, plant_cover_num))
            added_face_index = 0
            while added_face_index is not None:
                added_face_index = self.plant_covers[-1].iterate_faces_and_return_added_index(self.faces)
                if added_face_index is not None: del self.faces[added_face_index]
    
    def set_objects_details(self):
        # 各植被の詳細を設定する
        for plant_cover in self.plant_covers:
            plant_cover.set_vertices_dict(self.vertices)
            # 地表面の頂点番号リストを作成し、植被高さを設定する
            plant_cover.set_bottom_vertice_indexes_and_height()
            # 底面にある面をbottom_facesに格納
            plant_cover.set_bottom_faces()
            plant_cover.set_bottom_feature_edges()
            plant_cover.set_sorted_bottom_indexes()
        return
    
    def export_to_objects_file(self):
        plant_covers = []
        for i in range(len(self.plant_covers)):
            face_list = []
            for face in self.plant_covers[i].faces:
                face_list.append(face.get_face())
            plant_covers.append(
                {
                    "id": self.plant_covers[i].plant_cover_id,
                    "faces": face_list
                }
            )
        doc = {
            "vertices": self.vertices,
            "plant_covers": plant_covers
        }
        # ファイル出力
        with open(self.plant_cover_filepath, "w") as f:
            json.dump(doc, f)
        return

    def split_by_height(self):
        # self.fullpathに保存されている3Dモデルのファイルを"H_"という接頭辞をつけて複製する。
        copyfile(self.full_filepath, self.h_full_filepath)
        # self.plant_cover_filepathに保存されている植被のファイルを"H_"という接頭辞をつけて複製する。
        copyfile(self.plant_cover_filepath, self.h_plant_cover_filepath)
        
        high_plant_cover_ids = []
        low_plant_cover_ids = []
        for plant_cover in self.plant_covers:
            if plant_cover.get_height() >= PLANT_COVER_HEIGHT_THRESHOLD:
                high_plant_cover_ids.append(plant_cover.get_plant_cover_id())
                plant_cover.add_suffix_to_plant_cover_id(HIGH_PLANT_COVER_SUFFIX_FOR_ID)
                self.high_plant_covers.append(plant_cover)
            else:
                low_plant_cover_ids.append(plant_cover.get_plant_cover_id())
                self.low_plant_covers.append(plant_cover)

        if len(high_plant_cover_ids) == 0:
            # 高い植被が一つもない場合、H_の付いた3Dモデルファイルとplant_cover.jsonを削除する。
            os.remove(self.h_full_filepath)
            os.remove(self.h_plant_cover_filepath)
        else:
            # H_plant_cover.jsonから低い植被を削除する。
            high_plant_cover_for_edit = PlantCoverFileForEditPlantCover(self.h_plant_cover_filepath)
            high_plant_cover_for_edit.load()
            high_plant_cover_for_edit.remove_plant_covers(low_plant_cover_ids)
            # H_plant_cover.jsonに含まれる各plant_cover_idには末尾に"h"を付ける。
            high_plant_cover_for_edit.add_suffix_to_plant_cover_ids(HIGH_PLANT_COVER_SUFFIX_FOR_ID)
            high_plant_cover_for_edit.export()
            # H_の付いた3Dモデルファイルから低い植被を削除する。
            three_d_model_for_rm_high_plant_cover = None
            if is_obj_file(self.h_full_filepath):
                three_d_model_for_rm_high_plant_cover = ObjFileForRmBuilding(self.h_full_filepath, high_plant_cover_for_edit)
            else:
                three_d_model_for_rm_high_plant_cover = StlFileForRmBuilding(self.h_full_filepath, high_plant_cover_for_edit)
            three_d_model_for_rm_high_plant_cover.load_and_export()

        if len(low_plant_cover_ids) == 0:
            # 低い植被が一つもない場合、H_の付いていない3Dモデルファイルとplant_cover.jsonを削除する。
            os.remove(self.full_filepath)
            os.remove(self.plant_cover_filepath)
        else:
            # plant_cover.jsonから高い植被を削除する。
            low_plant_cover_for_edit = PlantCoverFileForEditPlantCover(self.plant_cover_filepath)
            low_plant_cover_for_edit.load()
            low_plant_cover_for_edit.remove_plant_covers(high_plant_cover_ids)
            low_plant_cover_for_edit.export()
            # H_の付いていない3Dモデルファイルから高い植被を削除する。
            three_d_model_for_rm_low_plant_cover = None
            if is_obj_file(self.full_filepath):
                three_d_model_for_rm_low_plant_cover = ObjFileForRmBuilding(self.full_filepath, low_plant_cover_for_edit)
            else:
                three_d_model_for_rm_low_plant_cover = StlFileForRmBuilding(self.full_filepath, low_plant_cover_for_edit)
            three_d_model_for_rm_low_plant_cover.load_and_export()
        return
    
    def export_to_czml(self, converter: ConverterToLatLon):
        # highとlowの植被を合わせてczmlファイルに出力する。
        combined_plant_covers = self.high_plant_covers + self.low_plant_covers
        doc = []
        # idオブジェクトの作成
        id_obj = {"id":"document", "name":"CZML Geometries: Polyline", "version":"1.0"}
        doc.append(id_obj)
        # 各植被のオブジェクトを作成
        for plant_cover in combined_plant_covers:
            latlon_coordinates = []
            for bottom_index in plant_cover.sorted_bottom_indexes:
                # 座標を緯度経度に変換する
                lat, lon = converter.convert(plant_cover.vertices_dict[bottom_index][0], plant_cover.vertices_dict[bottom_index][1])
                # 建物データはCesiumで表示するとジオイド高だけ沈むのでそれに合わせて調整
                elevation = plant_cover.vertices_dict[bottom_index][2] + GEOID_HEIGHT
                # CZMLは経度・緯度の順で記載する
                latlon_coordinates.append((lon, lat, elevation))
            doc.append(
                {
                    "id":f"{plant_cover.plant_cover_id}",
                    "name": f"id:{plant_cover.plant_cover_id}, Type:{str(plant_cover.type_name)}",
                    "polygon" : {
                        "positions":{
                            # 底面の頂点の緯度経度標高
                            "cartographicDegrees" : [cartographicDegree for coordinate in latlon_coordinates for cartographicDegree in coordinate]
                        },
                        "material" : {
                            "solidColor":{
                                "color":{
                                    "rgba": PLANT_COVER_COLOR
                                }
                            }
                        },
                        # Cesium上で影を表示するかどうかの設定
                        "shadows":"ENABLED",
                        # 底面の標高
                        "height":latlon_coordinates[0][2],
                        # 天井面の標高
                        "extrudedHeight": latlon_coordinates[0][2] + plant_cover.height,
                        # 天井面を閉じるかどうかの設定
                        "closeTop": True,
                        # 底面を閉じるかどうかの設定
                        "closeBottom": True,
                    }
                }
            )
        # ファイル出力
        visualization_file = os.path.splitext(self.full_filepath)[0] + ".czml"
        with open(visualization_file, "w") as f:
            json.dump(doc, f)
        return

class PlantCoverForNewPlantCover:
    def __init__(self, coordinates: List[float], height: float, converter: ConverterFromLatLon,
                 czml: CzmlForEditPlantCover, plant_cover_file: PlantCoverFileForEditPlantCover) -> None:
        # 経度緯度標高をfloatのリストのリストに変換
        bottom_coordinates_lonlat_str = [coordinates[i:i+3] for i in range(0, len(coordinates), 3)]
        self.bottom_coordinates_lonlat = [[float(x) for x in coordinate] for coordinate in bottom_coordinates_lonlat_str]
        self.plant_cover_id = czml.new_plant_cover_id
        self.height = height
        self.converter = converter
        self.first_vertice_id = len(plant_cover_file.object_file['vertices'])
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
        # 底面エッジを頂点番号一覧で取得
        self.bottom_face = self.vertices[:len(self.vertices) // 2]
        # 天井面エッジを頂点番号一覧で取得
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
        t = Triangulate(bottom_face_2d)
        is_bottom_face_ccw = not t.is_cw
        triangulated_index_list = t.triangulate()

        # triangulated_index_list = [[3,0,1], [1,2,3], ...]

        for triangulated_index in triangulated_index_list:
            converted_index_list = []
            # 底面の三角メッシュが時計回りになるよう並べ替え
            bottom_clockwise = Clockwise([bottom_face_2d[i] for i in triangulated_index])
            is_ccw = not bottom_clockwise.is_clockwise()

            if is_ccw:
                triangulated_index.reverse()
            for index in triangulated_index:
                converted_index_list.append(self.bottom_face[index]["id"])
            self.face_index_list.append(converted_index_list)
        # 天井面をtriangular化
        roof_face_2d = [[v["coordinate"][0], v["coordinate"][1]] for v in self.roof_face]
        triangulated_index_list = Triangulate(roof_face_2d).triangulate()
        for triangulated_index in triangulated_index_list:
            converted_index_list = []
            # 天井面の三角メッシュが反時計回りになるよう並べ替え
            roof_clockwise = Clockwise([roof_face_2d[i] for i in triangulated_index])
            is_ccw = not roof_clockwise.is_clockwise()
            if not is_ccw:
                triangulated_index.reverse()
            for index in triangulated_index:
                converted_index_list.append(self.roof_face[index]["id"])
            self.face_index_list.append(converted_index_list)
        # side_facesをtriangular化
        # 底面エッジが反時計回りの場合は0,1,2->2,3,0の順で三角形を作成
        # 底面エッジが時計回りの場合は2,1,0->0,3,2の順で三角形を作成
        for side_face in self.side_faces:
            tr1 = [side_face[0]["id"], 
                   side_face[1]["id"], 
                   side_face[2]["id"]
                   ] if is_bottom_face_ccw else [
                       side_face[2]["id"], 
                       side_face[1]["id"], 
                       side_face[0]["id"]
                    ]
            tr2 = [side_face[2]["id"], 
                   side_face[3]["id"], 
                   side_face[0]["id"]
                   ] if is_bottom_face_ccw else [
                       side_face[0]["id"], 
                       side_face[3]["id"], 
                       side_face[2]["id"]
                   ]
            self.face_index_list.append(tr1)
            self.face_index_list.append(tr2)
        return
