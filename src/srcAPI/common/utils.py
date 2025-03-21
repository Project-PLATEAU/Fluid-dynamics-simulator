
import sys
import configparser
from . import log_writer
import os
from pathlib import Path

logger = log_writer.getLogger()

SETTINGS_FILENAME : str = "common/config.ini" # 設定ファイルのパス

def get_args( count: int) ->list[str]:
    if len(sys.argv)-1!= count:
        message = 'コマンドライン引数を'+ format(count,'0')+'つ指定してください'
        print(message)
        # status_db_connection.throw_error("コマンドライン引数指定不正")

    argList =sys.argv
    message = '起動：'+' '.join(argList)
    log_writer.fileConfig()
    logger.info(log_writer.format_str(0, message))

    argList.pop(0) #最初の要素はPython.exeとなるため削除
    return argList

def get_py_filename()->str:
    return sys.argv[0]

def get_settings(section : str, key : str) -> str:
    """get_settings
        設定ファイル（common/config.ini）を読み込む

    Args:
        section (str): 設定ファイル内のセクション名（[]で記述されている部分）
        key (str): 設定ファイル内のキー

    Returns:
        str: 指定されたセクション、キーに設定された値
    """
    settings = configparser.ConfigParser()
    settings.read(SETTINGS_FILENAME,'utf-8')
    return settings.get(section, key)

PATH_SECTION: str = "PATH"  # ファイルパスを記述しているセクション名
SHARED_FOLDER_ROOT: str = "shared_folder_root"  # 共有フォルダのパスを記述しているキー

def get_shared_folder() -> str:
    """
    Get the path of the shared file server.
    """
    return get_settings(PATH_SECTION, SHARED_FOLDER_ROOT)

def get_folder_path(file_path: str) -> str:
    """
    Get the folder path by removing the file name from the file path.

    Return:
        folder_path (str), file_name (_)
    """
    return os.path.split(file_path)

def get_stl_folder(cm_id, re_id, st_id) -> str:

    city_model_id = os.path.join('city_model', str(cm_id))
    region_id = os.path.join('region', str(re_id))
    stl_id = str(st_id) + ".obj"
    stl_type_id = os.path.join(str(st_id), stl_id)

    stl_folder_path = os.path.join(city_model_id, region_id, stl_type_id)
    return stl_folder_path

def create_lock_folder(cm_id, re_id, st_id) -> str:

    city_model_id = os.path.join('city_model', str(cm_id))
    region_id = os.path.join('region', str(re_id))
    stl_type_id = str(st_id)

    lock_folder_path = os.path.join(get_shared_folder(), city_model_id, region_id, stl_type_id)
    os.makedirs(lock_folder_path)
    return lock_folder_path