"""file_path_generator

*  common/config.iniを開き、ファイルパスの設定を読み込む
* 共有ファイルサーバー、シミュレーションマシン内のフォルダのファイルパスを作成し、呼び出し元に返却

"""
import os
from pathlib import Path
from . import utils

PATH_SECTION : str = "PATH" # ファイルパスを記述しているセクション名
SHARED_FOLDER_ROOT : str = "shared_folder_root" # 共有ファイルサーバのパスを記述しているキー
SIM_FOLDER_ROOT : str = "sim_folder_root" # シミュレーションマシン内のmodel_idフォルダを配置するフォルダのパスを記述しているキー
TEMPLATE : str = "template" # OpenFOAMのテンプレートファイルセットのフォルダ名
SIMULATION_ERROR_LOG_FOLDER : str = "log"
COMPRESSED_FILE_EXTENSION : str = "zip"

CITY_MODEL_FOLDER : str = "city_model"
SIMULATION_INPUT_FOLDER : str = "simulation_input"
SIMULATION_OUTPUT_FOLDER : str = "simulation_output"
CONVERTED_OUTPUT_FOLDER : str = "converted_output"
REGION : str = "region"

CONSTANT : str = "constant" # OpenFOAM内のconstantフォルダ名
POLY_MESH : str = "polyMesh" # OpenFOAM内のpolyMeshフォルダ名
TRI_SURFACE : str = "triSurface" # OpenFOAM内のtriSurfaceフォルダ名

def get_filename_with_extention(file_path:str)->str:
    return os.path.basename(file_path)
def get_filename_without_extention(file_path:str)->str:
    return Path(file_path).stem
def get_file_extension(file_path:str)->str:
    return os.path.splitext(file_path)[1]
def combine(x : str ,y : str ) -> str:
    return os.path.join(x,y)

def get_copied_stl_filename_without_extention(stl_type_id:int)->str:
    return 'type%i'%(stl_type_id)

def get_shared_folder() -> str:
    """get_shared_folder
        共有ファイルサーバのパスを取得する
    
        Returns:
            str: 共有ファイルサーバのパス
    """
    return utils.get_settings(PATH_SECTION, SHARED_FOLDER_ROOT)


def get_city_model_folder_fs() -> str:
   """get_city_model_folder_fs
        共有ファイルサーバで3D都市モデルを配置するディレクトリのパスを取得する
  
        Returns:
            str: 3D都市モデルディレクトリのパス
    """
   return os.path.join(get_shared_folder(), CITY_MODEL_FOLDER)


def get_simulation_input_folder_fs() -> str:
    return os.path.join(get_shared_folder(), SIMULATION_INPUT_FOLDER)


def get_simulation_output_folder_fs() -> str:
    return os.path.join(get_shared_folder(), SIMULATION_OUTPUT_FOLDER)
 

def get_converted_output_folder_fs() -> str:
    return os.path.join(get_shared_folder(), CONVERTED_OUTPUT_FOLDER)
 

def get_city_model_id_folder_fs(model_id : str) -> str:
    """get_city_model_id_folder_fs
        共有ファイルサーバで各都市モデルを配置するディレクトリのパスを取得する

        Args:
            model_id (str): 都市モデルID
    
        Returns:
            str: 引数で指定された都市モデルIDのディレクトリのパス
    """
    return os.path.join(get_shared_folder(), CITY_MODEL_FOLDER , model_id)

def get_region_folder_fs(model_id : str, region_id : str) -> str:
    return os.path.join(get_city_model_id_folder_fs(model_id), REGION, region_id)

def get_simulation_input_model_id_folder_fs(model_id : str) -> str:
    return os.path.join(get_simulation_input_folder_fs(), model_id, '')

def get_triInterface_folder_fs(model_id : str) -> str:
    return os.path.join(get_simulation_input_folder_fs(), model_id, TEMPLATE, CONSTANT, TRI_SURFACE, '')

def get_simulation_output_model_id_folder_fs(model_id : str) -> str:
    return os.path.join(get_simulation_output_folder_fs(), model_id, TEMPLATE)

def get_simulation_output_model_id_poly_mesh_folder_fs(model_id : str) -> str:
    return os.path.join(get_simulation_output_model_id_folder_fs(model_id), CONSTANT, POLY_MESH)

def get_converted_output_model_id_folder_fs(model_id : str) -> str:
    return os.path.join(get_converted_output_folder_fs(), model_id)

# 圧縮前のエラーログフォルダ名(converted_output/<model_id>/log)を取得する関数
# simulation_error.pyで使用する
def get_error_log_folder_model_id_fs(model_id : str) -> str:
    return combine(get_converted_output_model_id_folder_fs(model_id), SIMULATION_ERROR_LOG_FOLDER)

# 圧縮済みエラーログファイル名(converted_output/<model_id>/log.zip)を取得する関数
# wrapper_organize.pyで使用する
def get_compressed_error_log_file_model_id_fs(model_id : str) -> str:
    return get_error_log_folder_model_id_fs(model_id) + "." + get_compressed_file_extension()

def get_compressed_file_extension() -> str:
    return COMPRESSED_FILE_EXTENSION

def get_folder_name_without_shared_folder_fs(file_path : str) -> str:
    shared_folder = get_shared_folder()
    delimiter = os.path.sep
    shared_folder = shared_folder if shared_folder.endswith(delimiter) else shared_folder + delimiter
    if file_path.startswith(shared_folder):
        return file_path[len(shared_folder):]
    else:
        return file_path

def get_root_folder_sim() -> str:
    """get_root_folder_sim
        シミュレーションマシン内でmodel_idごとのファイルセットを配置するディレクトリのパスを取得する
    
        Returns:
            str: シミュレーションマシン内でmodel_idごとのファイルセットを配置するディレクトリのパス
    """
    return utils.get_settings(PATH_SECTION, SIM_FOLDER_ROOT)


def get_model_id_folder_sim(model_id : str) -> str:
    return os.path.join(get_root_folder_sim(), model_id)

def get_execute_folder_wrapper()-> str:
    py_filename = utils.get_py_filename()
    if py_filename.startswith(os.path.abspath(os.sep)):
        return os.path.dirname(py_filename)
    else:
        return os.getcwd()


# デバッグ用、コミット時はコメントアウト
"""
if __name__ == '__main__':
    print(f"{get_city_model_folder_fs()}")
    print(f"{get_simulation_input_folder_fs()}")
    print(f"{get_simulation_output_folder_fs()}")
    print(f"{get_converted_output_folder_fs()}")
    print(f"{get_city_model_id_folder_fs("1234567A")}")
    print(f"{get_region_folder_fs("1234567A", "aaabbbccc")}")
    print(f"{get_simulation_input_model_id_folder_fs("1234567A")}")
    print(f"{get_triInterface_folder_fs("1234567A")}")
    print(f"{get_converted_output_model_id_folder_fs("1234567A")}")
    print(f"{get_model_id_folder_sim("1234567B")}")
"""
    