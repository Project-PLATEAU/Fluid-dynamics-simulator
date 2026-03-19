from typing import Protocol

class IObjectFileForEditObject(Protocol):
    object_file: dict
    vertice_ids_tobe_removed: list[str]
    new_index_list: list[int]
    vertices_tobe_removed: list[float]
    old_vertices_num: int