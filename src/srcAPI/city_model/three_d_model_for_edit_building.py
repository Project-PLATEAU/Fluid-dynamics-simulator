import abc
from typing import Self
from typing import List
import numpy as np
from stl import mesh
from .bldg_file_for_edit_building import *
import math
from typing import TYPE_CHECKING
import os
if TYPE_CHECKING:
    from .building import *


class IThreeDModelForEditBuilding(metaclass=abc.ABCMeta):
    @abc.abstractmethod
    def load_and_export(self) -> Self:
        raise NotImplementedError()
 
class ObjFileForRmBuilding(IThreeDModelForEditBuilding):
    def __init__(self, filepath : str, bldg_file : BldgFileForEditBuilding) -> Self:
        self.obj_filepath = filepath
        self.vertices = []
        self.face_vert_ids = []
        # 削除される頂点番号のリスト
        self.vertice_ids_tobe_removed = bldg_file.vertice_ids_tobe_removed
        # 新しい頂点番号のリスト（削除された頂点番号にはNoneが入っている）
        self.new_index_list = bldg_file.new_index_list

    def load_and_export(self) -> None:
        num_vertices = 0
        tmp_file = self.obj_filepath + ".tmp"
        infile = open(self.obj_filepath, "r")
        outfile = open(tmp_file, "w")
        for line in infile:
            if line.startswith(("v", "f")):
                vals = line.split()
                if len(vals) == 0:
                    outfile.write(line)
                    continue
                elif vals[0] == "v":
                    if num_vertices not in self.vertice_ids_tobe_removed:
                        outfile.write(line)
                    num_vertices += 1
                elif vals[0] == "f":
                    face = vals[1:4]
                    face_vert_ID = [int(f) - 1 for f in face]
                    if any(elem in face_vert_ID for elem in self.vertice_ids_tobe_removed):
                        continue
                    else:
                        new_face_vert_id = [self.new_index_list[i] + 1 for i in face_vert_ID]
                        new_line = "f " + " ".join(map(str, new_face_vert_id)) + " \n"
                        outfile.write(new_line)
            else:
                outfile.write(line)
        infile.close()
        outfile.close()
        # tmpfileをリネーム
        os.rename(tmp_file, self.obj_filepath)
        return

class StlFileForRmBuilding(IThreeDModelForEditBuilding):
    def __init__(self, filepath : str, bldg_file : BldgFileForEditBuilding) -> Self:
        self.stl_filepath = filepath
        # 削除される頂点のリスト
        self.vertices_tobe_removed = bldg_file.vertices_tobe_removed

    def tobe_remained(self, face_stl)-> bool:
        float_face_stl = face_stl.astype(float).tolist()
        for vertice_tobe_removed in self.vertices_tobe_removed:
            for v in float_face_stl:
                if all(math.isclose(xyz1, xyz2, rel_tol=1e-9) for xyz1, xyz2 in zip(v, vertice_tobe_removed)):
                    return False
        return True

    def load_and_export(self) -> None:
        stl_data = mesh.Mesh.from_file(self.stl_filepath)
        # すべての面を取得
        all_faces_stl = stl_data.vectors
        # 削除する面をフィルタ
        mask = np.array([self.tobe_remained(face_stl) for face_stl in all_faces_stl])
        # ブールマスキングで削除されない面だけを保持した新しいメッシュを作成
        new_faces = all_faces_stl[mask]
        # 新しいメッシュのverctosを設定
        new_mesh = mesh.Mesh(np.zeros(new_faces.shape[0], dtype=mesh.Mesh.dtype))
        new_mesh.vectors = new_faces
        # 新しいメッシュをstlファイルに保存
        new_mesh.save(self.stl_filepath)
        return

class ObjFileForNewBuilding(IThreeDModelForEditBuilding):
    def __init__(self, filepath : str,
                 bldg_file : BldgFileForEditBuilding, 
                 new_building : "BuildingForNewBuilding") -> Self:
        self.obj_filepath = filepath
        # 建物が追加されたときの頂点一覧
        self.vertices = bldg_file.bldg_file['vertices']
        self.face_vert_ids = []
        # 建物追加前の頂点数
        self.old_vertices_num = bldg_file.old_vertices_num
        # 新しい建物のメッシュの頂点番号リスト
        self.face_vert_ids_of_new_building = new_building.face_index_list

    def load_and_export(self) -> None:
        num_vertices = 0
        tmp_file = self.obj_filepath + ".tmpnew"
        if os.path.exists(self.obj_filepath):
            infile = open(self.obj_filepath, "r")
            outfile = open(tmp_file, "w")
            need_to_add_new_face = False
            for line in infile:
                if num_vertices == self.old_vertices_num:
                    for vertice in self.vertices[num_vertices:]:
                        new_line = "v " + " ".join(map(str, vertice)) + " \n"
                        outfile.write(new_line)
                    num_vertices = len(self.vertices)
                    outfile.write(line)
                    continue
                if line.startswith(("v", "f")):
                    vals = line.split()
                    if len(vals) == 0:
                        outfile.write(line)
                        continue
                    elif vals[0] == "v":
                        outfile.write(line)
                        num_vertices += 1
                    elif vals[0] == "f":
                        outfile.write(line)
                        need_to_add_new_face = True
                else:
                    if need_to_add_new_face:
                        for face_vert_id in self.face_vert_ids_of_new_building:
                            new_line = "f " + " ".join(map(str, self.plus1_for_obj_face_index(face_vert_id))) + " \n"
                            outfile.write(new_line)
                        need_to_add_new_face = False
                        outfile.write(line)
                    else:
                        outfile.write(line)
            infile.close()
            outfile.close()
            # tmpfileをリネーム
            os.rename(tmp_file, self.obj_filepath)
        else:
            # 新しいstl_type_idに建物を作成する場合
            outfile = open(self.obj_filepath, "w")
            for vertice in self.vertices[num_vertices:]:
                new_line = "v " + " ".join(map(str, vertice)) + " \n"
                outfile.write(new_line)
            for face_vert_id in self.face_vert_ids_of_new_building:
                new_line = "f " + " ".join(map(str, self.plus1_for_obj_face_index(face_vert_id))) + " \n"
                outfile.write(new_line)           
        return
    
    @staticmethod
    def plus1_for_obj_face_index(face_vert_id : List[str]) -> List[int]:
        # objファイルのfaceのindexが1始まりなので、+1する
        return [int(i) + 1 for i in face_vert_id]
    
class StlFileForNewBuilding(IThreeDModelForEditBuilding):
    def __init__(self, filepath : str, 
                 bldg_file : BldgFileForEditBuilding,
                 new_building : "BuildingForNewBuilding") -> Self:
        self.stl_filepath = filepath
        # 建物が追加されたあとの頂点一覧
        self.vertices = bldg_file.bldg_file['vertices']
        # 建物追加前の頂点数
        self.old_vertices_num = bldg_file.old_vertices_num
        # 新しい建物のメッシュの頂点番号リスト
        self.face_vert_ids_of_new_building = new_building.face_index_list

    def load_and_export(self) -> None:
        stl_data = mesh.Mesh.from_file(self.stl_filepath)
        # すべての面を取得
        all_faces_stl = stl_data.vectors
        # 追加する面をall_faces_stlに追加
        for face_vert_id in self.face_vert_ids_of_new_building:
            new_face = np.array([
                self.vertices[face_vert_id[0]],
                self.vertices[face_vert_id[1]],
                self.vertices[face_vert_id[2]]
            ])
            all_faces_stl = np.append(all_faces_stl, [new_face], axis=0)
        # 新しいメッシュのverctosを設定
        new_mesh = mesh.Mesh(np.zeros(all_faces_stl.shape[0], dtype=mesh.Mesh.dtype))
        new_mesh.vectors = all_faces_stl
        # 新しいメッシュをstlファイルに保存
        new_mesh.save(self.stl_filepath)
        return None

