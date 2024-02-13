
import sys
import configparser
from . import log_writer
logger = log_writer.getLogger()

SETTINGS_FILENAME : str = "common/config.ini" # 設定ファイルのパス

def get_args( count: int) ->list[str]:        
    if len(sys.argv)-1!= count:
        message = 'コマンドライン引数を'+ format(count,'0')+'つ指定してください'
        print(message)
        status_db_connection.throw_error("コマンドライン引数指定不正")
    
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