from typing import List

class Face:
    def __init__(self, face_vert_ids: List[int]) -> None:
        self.face_vert_id_0 = face_vert_ids[0]
        self.face_vert_id_1 = face_vert_ids[1]
        self.face_vert_id_2 = face_vert_ids[2]

    def get_face(self)->List[int]:
        return [self.face_vert_id_0, self.face_vert_id_1, self.face_vert_id_2]
