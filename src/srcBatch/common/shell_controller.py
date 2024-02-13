import os
import shutil
import paramiko
from stat import S_ISDIR
from common import utils
from common import static
from common import file_path_generator
from common import log_writer
from common import file_controller
logger = log_writer.getLogger()
log_writer.fileConfig()

# サーバー情報
user = utils.get_settings("SimEC2","user")
host = utils.get_settings("SimEC2","host")
key_filename = utils.get_settings("SimEC2","key_filename")
# TODO : pemファイルの取扱い

def create_ssh_client():
    # SSH接続
    ssh_client = paramiko.SSHClient()
    ssh_client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        # 1. パスワード認証認証方式
        # client.connect(hostname=host, port=22, username=user, password=password)
        # 2. 公開鍵認証方式
        ssh_client.connect(hostname=host, port=22, username=user, key_filename=key_filename, timeout=10.0)
    except Exception as e:
        logger.error(f'Error connecting to {host}: {e}')
        raise Exception(f'Error connecting to {host}: {e}')
    return ssh_client

def launch(shell_file_path_sim : str)->int:
    #シミュレーションマシンにssh接続
    with create_ssh_client() as ssh_client:
        try:
            script_file_path = f'{shell_file_path_sim}/launcher'
            # launcherに実行権限を付与
            stdin, stdout, stderr = ssh_client.exec_command(f'chmod 764 {script_file_path}')
            if stderr.read():
                logger.error(f"Failed to give execute permissions to {script_file_path}")
            else:
                logger.info(f"Execute permissions granted to {script_file_path}")
            # SIMULATION_MODEL.idフォルダ内で、launchを実行
            stdin, stdout, stderr = ssh_client.exec_command(f'. {script_file_path}')
            logger.info(f"Execution of {script_file_path} successful")
        except Exception as e:
            logger.error(f'An error occurred during script execution: {e}')
            raise Exception(f'An error occurred during script execution: : {e}')

def monitor(shell_file_path_sim : str)->(bool,bool):
    #シミュレーションマシンにssh接続
    with create_ssh_client() as ssh_client:
        try:
            # pid取得
            pid_file_path = f'{shell_file_path_sim}/pid'
            stdin, stdout, stderr = ssh_client.exec_command(f'cat {pid_file_path}')
            if stderr.read():
                logger.error(f"pid file is not found. pid_file_path: {pid_file_path}")
                raise Exception(f"pid file is not found. pid_file_path: {pid_file_path}")
            pid = stdout.read().decode().strip()
            # PIDが存在するか確認
            stdin, stdout, stderr = ssh_client.exec_command(f'ps -p {pid}')
            pid_exists  = len(stdout.read().decode().strip().split('\n')) > 1

            if pid_exists:
                logger.info(f'PID {pid} is running.')
                return True, True
            else:
                # エラーファイルの確認
                error_file_path = f'{shell_file_path_sim}/error'
                stdin, stdout, stderr = ssh_client.exec_command(f'test -e {error_file_path} && echo 1 || echo 0')
                error_file_exists = stdout.read().decode().strip()

                if error_file_exists=='1':
                    logger.info(f'Error file {error_file_path} exists.')
                    return False, False
                elif error_file_exists=='0':
                    logger.info('PID not found and no error file.')
                    return False, True
                else :
                    logger.error('Processing did not complete successfully.')
                    return False, False
        except Exception as e:
            logger.error(f'Error checking PID status: {e}')
            logger.error(f'get shell_file_path_sim: {shell_file_path_sim}')
            raise Exception(f'Error checking PID status: {e}')

def abort(shell_file_path_sim : str):
    #シミュレーションマシンにssh接続
    with create_ssh_client() as ssh_client:
        try:
            # PID取得
            pid_file_path = f'{shell_file_path_sim}/pid'
            stdin, stdout, stderr = ssh_client.exec_command(f'cat {pid_file_path}')
            pid = stdout.read().decode().strip()
            # PIDが存在するか確認
            stdin, stdout, stderr = ssh_client.exec_command(f'ps -p {pid}')
            pid_exists = bool(stdout.read().decode().strip())

            if pid_exists:
                # プロセスをkill
                stdin, stdout, stderr = ssh_client.exec_command(f'kill -9 {pid}')
                exit_status = stdout.channel.recv_exit_status()
                if exit_status == 0:
                    logger.info(f'Process with PID {pid} killed successfully.')
                else:
                    logger.error(f'Error killing process. Exit status: {exit_status}')
            else:
                logger.info('PID not found.')
        except Exception as e:
            logger.error(f'Error aborting process: {e}')
            raise Exception(f'Error aborting process: {e}')

# in/out_data_transfer.py
def copytree(local_dir_path : str , sim_dir_path : str, model_id : str, flag : int ):
    #シミュレーションマシンにssh接続
    with create_ssh_client() as ssh_client:
        # SFTPクライアントの取得
        sftp = ssh_client.open_sftp()
        # Wrapperコンテナ(EFS)パスの存在チェック
        if not os.path.exists(local_dir_path):
            logger.error(f"Directory not found in EFS: {local_dir_path}")
            raise FileNotFoundError(f"Directory not found in EFS: {local_dir_path}")
        # SimEC2パスの参照チェック
        if not check_remote_path_exist(ssh_client, sim_dir_path):
            print(f"Error: Remote path not found in SimEC2: {sim_dir_path}")

        if flag == 0:
            model_id_dir = os.path.join(sim_dir_path, model_id)
            try: 
                sftp.stat(model_id_dir)
                stdin, stdout, stderr = ssh_client.exec_command(f'rm -r "{model_id_dir}"')
                exit_status = stdout.channel.recv_exit_status()
                if exit_status != 0:
                    raise Exception(f"old model_id directory is existing and fail to remove in SimEC2 : {model_id_dir}")
                sftp.mkdir(model_id_dir)
            except FileNotFoundError: 
                sftp.mkdir(model_id_dir)
            # SFTP経由で送信
            for root, dirs, files in os.walk(local_dir_path):
                for directory in dirs:
                    local_dir = os.path.join(root, directory)
                    remote_dir = os.path.join(model_id_dir, os.path.relpath(local_dir, local_dir_path))
                    try:
                        sftp.stat(remote_dir)
                    except FileNotFoundError:
                        sftp.mkdir(remote_dir)
                for file in files:
                    local_file_path = os.path.join(root, file)
                    remote_file_path = os.path.join(model_id_dir, os.path.relpath(local_file_path, local_dir_path))
                    sftp.put(local_file_path, remote_file_path)
        elif flag == 1:
            logger.info(f"[sftp.get] local_dir_path:{local_dir_path}")
            logger.info(f"[sftp.get] sim_dir_path:{sim_dir_path}")
            add_local_dir_path = os.path.join(local_dir_path, model_id)
            if file_controller.exist_folder_fs(add_local_dir_path):
                file_controller.delete_folder_fs(add_local_dir_path)
            # ローカルにmodel_idのディレクトリを作成
            os.makedirs(add_local_dir_path, exist_ok=True)
            # SFTPダウンロード
            sftp_download_dir(sftp, sim_dir_path, add_local_dir_path)

def sftp_download_dir(sftp, sim_dir_path, local_dir_path):
    # リモートディレクトリ内のファイルをすべてダウンロード
    for remote_item in sftp.listdir_attr(sim_dir_path):
        remote_item_path = os.path.join(sim_dir_path, '', remote_item.filename)
        local_item_path = os.path.join(local_dir_path, remote_item.filename)
        logger.info(f"[sftp.get] remote_item_path:{remote_item_path}")

        # ディレクトリの場合
        if S_ISDIR(remote_item.st_mode):
            if not os.path.exists(local_item_path):
                os.makedirs(local_item_path, exist_ok=True)
            sftp_download_dir(sftp, remote_item_path, local_item_path)
        # ファイルの場合
        else:
            sftp.get(remote_item_path, local_item_path)
            logger.info(f"[sftp.get]  File downloaded from {remote_item_path} to {local_item_path}")

# # input_data_transfer.py
def put_folder(src_folder_path_fs : str , dst_folder_path_sim : str , model_id : str):
    copytree(src_folder_path_fs, dst_folder_path_sim, model_id, 0)
    return
# # output_data_transfer.py
def get_folder(src_folder_path_sim : str , dst_folder_path_fs : str , model_id : str):
    # EFSフォルダの存在チェック
    if not os.path.exists(dst_folder_path_fs):
        logger.error(f"File not found: {dst_folder_path_fs}")
        raise FileNotFoundError(f"File not found: {dst_folder_path_fs}")
    copytree(dst_folder_path_fs, src_folder_path_sim, model_id, 1)
    print(f"local_path:{src_folder_path_sim}")
    print(f"remotre_path:{dst_folder_path_fs}")
    return

# # check_remote_path
def check_remote_path_exist(ssh, remote_path):
    try:
        # SSH接続でコマンド実行
        stdin, stdout, stderr = ssh.exec_command(f'test -e "{remote_path}" && echo "1" || echo "0"')
        # コマンドの結果を読み取り
        result = stdout.read().decode().strip()
        logger.info(f"checked remote path. return result:{result}")
        # 結果が "1" ならパスは存在する
        return result == "1"
    except Exception as e:
        print(f"Error checking remote path existence: {e}")
        logger.error(f"Error checking remote path existence: {e}")
        return False

if __name__ == "__main__":
    put_folder('/home/bridge-plateau-cfd/common/test',file_path_generator.get_root_folder_sim())