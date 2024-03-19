import os
import shutil
import tarfile
from common import log_writer
logger = log_writer.getLogger()
log_writer.fileConfig()

# input_data_convert.py
def copy_file_fs(src_file_path_fs : str , dst_file_path_fs : str ):
    # 指定されたファイルの存在チェック
    if not os.path.exists(src_file_path_fs):
        logger.error(f"File not found: {src_file_path_fs}")
        raise FileNotFoundError(f"File not found: {src_file_path_fs}")
    # 指定先のディレクトリの存在チェック
    if not os.path.exists(os.path.dirname(dst_file_path_fs)):
        os.makedirs(os.path.dirname(dst_file_path_fs))
        logger.info(f"Make Directory: {dst_file_path_fs}")
    # ファイルのコピー
    try:
        shutil.copy(src_file_path_fs, dst_file_path_fs)
    except Exception as e:
        logger.error(f"Error copying file: {str(e)}")
        raise Exception(f"Error copying file: {str(e)}")
    return
def extract_tar_file_fs(src_tar_file_path_fs : str , dst_folder_path_fs : str ):
    copy_file_fs(src_tar_file_path_fs, dst_folder_path_fs)
    # tarファイルを解凍
    TAR_FILE : str = "template.tar"
    tar_file_path = os.path.join(dst_folder_path_fs, TAR_FILE)
    try:
        with tarfile.open(tar_file_path, 'r') as tar:
            tar.extractall(dst_folder_path_fs)
        logger.info("Tar file extracted")
    except Exception as e:
        logger.error(f"Error extracting tar file: {str(e)}")
        raise Exception(f"Error extracting tar file: {str(e)}")
    return
def write_text_file_fs(file_path_fs : str ,text:str):
    with open(file_path_fs, mode='w',newline="\n") as f:
        f.write(text)
    return

# output_data_convert.py
def get_subfolder_name_list(folder_name : str):
    return [d for d in os.listdir(folder_name) if os.path.isdir(os.path.join(folder_name, d))]

# in/out_data_convert.py
def delete_folder_fs(folder_path_fs : str ):
    shutil.rmtree(folder_path_fs)
    return
def exist_folder_fs(folder_path_fs : str ) -> bool:
    return os.path.exists(folder_path_fs)
def create_folder_fs(folder_path_fs : str ):
    os.makedirs(folder_path_fs)
    return
def create_or_recreate_folder_fs(folder_path_fs : str):
    if exist_folder_fs(folder_path_fs):
        delete_folder_fs(folder_path_fs)
    create_folder_fs(folder_path_fs)
    return

# simulation_error.py
def copy_log_files_fs(source_folder : str, destination_folder : str):
    # ソースフォルダ内の全てのファイルとサブフォルダを取得
    for root, dirs, files in os.walk(source_folder):
        for file in files:
            if file.startswith("log."):
                # ソースファイルのパスを生成
                source_path = os.path.join(root, file)
                # デスティネーションフォルダにファイルをコピー
                shutil.copy(source_path, destination_folder)
# simulation_error.py
def compress_log_files_fs(log_folder : str, extension : str):
    shutil.make_archive(log_folder, extension, log_folder)
