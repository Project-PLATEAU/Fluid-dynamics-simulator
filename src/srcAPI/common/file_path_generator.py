
import os
from city_model.constants import *


def get_czml_filename(stl_file: str) -> str:
    return os.path.splitext(stl_file)[0] + ".czml"

def get_bldg_file_filename(stl_file: str) -> str:
    return os.path.join(os.path.split(stl_file)[0], BLDG_FILE_JSON)

def get_tree_file_filename(stl_file: str) -> str:
    return os.path.join(os.path.split(stl_file)[0], TREE_JSON)

def get_plant_cover_filename(stl_file: str) -> str:
    return os.path.join(os.path.split(stl_file)[0], PLANT_COVER_JSON)

def get_plant_cover_h_filename(stl_file: str) -> str:
    high_plant_cover_json = HIGH_PLANT_COVER_PREFIX_FOR_FILENAME + PLANT_COVER_JSON
    return os.path.join(os.path.split(stl_file)[0], high_plant_cover_json)

def get_plant_cover_h_stl_filename(stl_file: str) -> str:
    stl_filename = HIGH_PLANT_COVER_PREFIX_FOR_FILENAME + os.path.basename(stl_file)
    return os.path.join(f"{os.path.dirname(stl_file)}", stl_filename)

def is_obj_file(filename: str) -> bool:
    return filename.lower().endswith(".obj")