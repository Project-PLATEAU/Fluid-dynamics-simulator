import abc
from typing import Self
from typing import List
import numpy as np
from stl import mesh

class IThreeDModel(metaclass=abc.ABCMeta):
    @abc.abstractmethod
    def load(self) -> Self:
        raise NotImplementedError()
    
    @abc.abstractmethod
    def get_vertices(self) -> List[List[float]]:
        raise NotImplementedError()

    @abc.abstractmethod
    def get_face_vert_ids(self) -> List[List[int]]:
        raise NotImplementedError()
    
    @abc.abstractmethod
    def get_filepath(self) -> str:
        raise NotImplementedError()
    
class ObjFile(IThreeDModel):
    def __init__(self, full_filepath : str) -> None:
        self.full_filepath = full_filepath
        self.vertices = []
        self.face_vert_ids = []

    def load(self) -> Self:
        num_vertices = 0
        num_UVs = 0
        num_normals = 0
        num_faces = 0
        uvs = []
        normals = []
        vertex_colors = []
        uv_IDs = []
        normal_IDs = []
        for line in open(self.full_filepath, "r"):
            vals = line.split()
            if len(vals) == 0:
                continue
            if vals[0] == "v":
                v = list(map(float, vals[1:4]))
                self.vertices.append(v)
                if len(vals) == 7:
                    vc = map(float, vals[4:7])
                    vertex_colors.append(vc)
                num_vertices += 1
            if vals[0] == "vt":
                vt = map(float, vals[1:3])
                uvs.append(vt)
                num_UVs += 1
            if vals[0] == "vn":
                vn = map(float, vals[1:4])
                normals.append(vn)
                num_normals += 1
            if vals[0] == "f":
                fv_ID = []
                uv_ID = []
                nv_ID = []
                for f in vals[1:]:
                    w = f.split("/")
                    if num_vertices > 0:
                        fv_ID.append(int(w[0])-1)
                    if num_UVs > 0:
                        uv_ID.append(int(w[1])-1)
                    if num_normals > 0:
                        nv_ID.append(int(w[2])-1)
                self.face_vert_ids.append(fv_ID)
                uv_IDs.append(uv_ID)
                normal_IDs.append(nv_ID)
                num_faces += 1
        return self
    
    def get_vertices(self) -> List[List[float]]:
        return self.vertices
    
    def get_face_vert_ids(self) -> List[List[float]]:
        return self.face_vert_ids
    
    def get_filepath(self) -> str:
        return self.full_filepath

class StlFile(IThreeDModel):
    def __init__(self, full_filepath : str) -> None:
        self.full_filepath = full_filepath
        self.vertices = []
        self.face_vert_ids = []

    def load(self) -> Self:
        stl_data = mesh.Mesh.from_file(self.full_filepath)
        # STLファイルのデータを整形
        reshaped_stl_data = stl_data.points.reshape([-1, 3])
        # 重複を削除したリストを作成
        unique_reshaped_stl_data = np.unique(stl_data.points.reshape([-1, 3]), axis=0)
        # 各要素にインデックスを付与
        unique_reshaped_stl_data_index_list = {
            tuple(item): idx for idx, item in enumerate(unique_reshaped_stl_data)}
        # 重複削除前のリストに、対応する値のインデックスを付与
        reshaped_stl_data_index_list = [
            unique_reshaped_stl_data_index_list[tuple(item)] for item in reshaped_stl_data]
        # 重複削除前のリストを3つずつに分割
        face_vert_ids = [
            reshaped_stl_data_index_list[i:i+3] for i in range(
                0, len(reshaped_stl_data_index_list), 3)]
        self.vertices = unique_reshaped_stl_data.tolist()
        self.face_vert_ids = face_vert_ids
        return self
    
    def get_vertices(self) -> List[List[float]]:
        return self.vertices
    
    def get_face_vert_ids(self) -> List[List[float]]:
        return self.face_vert_ids
    
    def get_filepath(self) -> str:
        return self.full_filepath