from common import file_path_generator
from common import status_db_connection
from common import webapp_db_connection
from common import log_writer
from common import static
from datetime import datetime
import subprocess

MESSAGE_EXECUTION_CANCELED = "実行がキャンセルされました。"
MESSAGE_SIMULATION_CANCELED = "シミュレーションがキャンセルされました。"
MESSAGE_ERROR_IN_TASK = "でエラーが発生しました。"
MESSAGE_ERROR_TASK_NOT_FOUND = "実行するタスクが見つかりませんでした。"
MESSAGE_SIMULATION_IN_PROGRESS = "シミュレーション実行中です。"
MESSAGE_SIMULATION_ERROR = "シミュレーションがエラー終了しました。エラーログをご確認ください。"
MESSAGE_FINISHED_SUCCESSFULLY = "シミュレーションモデル実行が正常終了しました。"

TASKS_PY_FILENAME = {status_db_connection.TASK_UNKNOWN : None,
         status_db_connection.TASK_INPUT_DATA_CONVERT : "input_data_convert.py",
         status_db_connection.TASK_INPUT_DATA_TRANSFER : "input_data_transfer.py",
         status_db_connection.TASK_SIMULATION_EXEC : "simulation_exec.py",
         status_db_connection.TASK_SIMULATION_MONITOR : "simulation_monitor.py",
         status_db_connection.TASK_OUTPUT_DATA_TRANSFER : "output_data_transfer.py",
         status_db_connection.TASK_OUTPUT_DATA_CONVERT : "output_data_convert.py",
         status_db_connection.TASK_SIMULATION_ERROR : "simulation_error.py",
         status_db_connection.TASK_SIMULATION_CANCEL : "simulation_cancel.py"
         }

NORMAL_TASK_SEQUENCE = [
    status_db_connection.TASK_INPUT_DATA_CONVERT,
    status_db_connection.TASK_INPUT_DATA_TRANSFER,
    status_db_connection.TASK_SIMULATION_EXEC,
    status_db_connection.TASK_SIMULATION_MONITOR,
    status_db_connection.TASK_OUTPUT_DATA_TRANSFER,
    status_db_connection.TASK_OUTPUT_DATA_CONVERT
]

PYTHON_COMMAND = "python3"

logger = log_writer.getLogger()

def run_task(task_id : int, model_id : str):
    command = f"{PYTHON_COMMAND} {file_path_generator.combine(file_path_generator.get_execute_folder_wrapper(),TASKS_PY_FILENAME[task_id])} {model_id}"
    logger.info(f'Run command :{command}')
    subprocess.Popen(command,
                     shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, stdin=subprocess.PIPE)
    return

def is_simulation_in_progress(task_id : int, status_id : int) -> bool:
    if task_id == status_db_connection.TASK_SIMULATION_EXEC:
        is_simulation_in_progress = True if status_id == status_db_connection.STATUS_NORMAL_END else False
    elif task_id == status_db_connection.TASK_SIMULATION_MONITOR:
        is_simulation_in_progress = True if status_id == status_db_connection.STATUS_IN_PROGRESS else False
    else:
        is_simulation_in_progress = False
    return is_simulation_in_progress

def start_first_task(model_id : str):
    starting_task = NORMAL_TASK_SEQUENCE[0]
    # 一番最初のタスク実行時のみSTATUS DBにレコード挿入
    status_db_connection.insert_model(model_id, starting_task, status_db_connection.STATUS_IN_PROGRESS)
    webapp_db_connection.update_status(model_id, static.SIMULATION_MODEL_RUN_STATUS_IN_PROGRESS,
                                       MESSAGE_SIMULATION_IN_PROGRESS, None, datetime.now())
    run_task(starting_task, model_id)
    return

def start_task_process(model_id : str, starting_task : int):
    if starting_task is not None:
        status_db_connection.set_progress(model_id, starting_task, status_db_connection.STATUS_IN_PROGRESS)
        run_task(starting_task, model_id)
    else:
        logger.error(log_writer.format_str(model_id, MESSAGE_ERROR_TASK_NOT_FOUND))
        raise Exception
    return

def get_next_task(current_task_id : int) -> int:
    if current_task_id in NORMAL_TASK_SEQUENCE:
        if NORMAL_TASK_SEQUENCE.index(current_task_id) < len(NORMAL_TASK_SEQUENCE) - 1:
            return NORMAL_TASK_SEQUENCE[NORMAL_TASK_SEQUENCE.index(current_task_id) + 1]
        else:
            return None
    else:
        return None

def check_status_and_start_next_task(model_id : str, task_id : int, status_id : int):
    if status_id == status_db_connection.STATUS_IN_PROGRESS:
        if task_id == status_db_connection.TASK_SIMULATION_MONITOR:
            start_task_process(model_id, task_id)
        else:
            pass
    elif status_id == status_db_connection.STATUS_NORMAL_END:
        if task_id == NORMAL_TASK_SEQUENCE[-1]:
            normal_end_process(model_id)
        elif task_id == status_db_connection.TASK_SIMULATION_ERROR:
            simulation_error_end_process(model_id)
        else:
            start_task_process(model_id, get_next_task(task_id))
    else:
        if task_id == status_db_connection.TASK_SIMULATION_MONITOR:
            start_task_process(model_id,  status_db_connection.TASK_SIMULATION_ERROR)
        else:
            abnormal_end_process(model_id, task_id)
    return

def abnormal_end_process(model_id : str, task_id : int):
    # 最後のタスク処理が異常終了
    msg = TASKS_PY_FILENAME[task_id] + MESSAGE_ERROR_IN_TASK
    logger.error(log_writer.format_str(model_id, msg))
    # status_dbから該当のmodel_idのレコードを削除する
    status_db_connection.delete_model(model_id)
    webapp_db_connection.update_status(model_id, static.SIMULATION_MODEL_RUN_STATUS_ABNORMAL_END,
                                       msg, None, datetime.now())
    return

def simulation_error_end_process(model_id : str):
    # シミュレーションが異常終了
    msg = MESSAGE_SIMULATION_ERROR
    logger.error(log_writer.format_str(model_id, msg))
    # status_dbから該当のmodel_idのレコードを削除する
    status_db_connection.delete_model(model_id)
    error_log_file_path = file_path_generator.get_compressed_error_log_file_model_id_fs(model_id)
    # WebアプリデータベースのURLには絶対パスではなく、共有フォルダ以下のパスのみを格納する
    trimmed_error_log_file_path = file_path_generator.get_folder_name_without_shared_folder_fs(error_log_file_path)
    webapp_db_connection.update_status(model_id, static.SIMULATION_MODEL_RUN_STATUS_ABNORMAL_END,
                                       msg, trimmed_error_log_file_path, datetime.now())
    return

def normal_end_process(model_id : str):
    # すべてのタスクが正常終了
    msg = MESSAGE_FINISHED_SUCCESSFULLY
    logger.info(log_writer.format_str(model_id, msg))
    # status_dbから該当のmodel_idのレコードを削除する
    status_db_connection.delete_model(model_id)
    webapp_db_connection.update_status(model_id, static.SIMULATION_MODEL_RUN_STATUS_NORMAL_END,
                                       msg, None, datetime.now())
    return

def cancel_end_process(model_id : str):
    # シミュレーションが中断されて終了
    logger.info(log_writer.format_str(model_id, MESSAGE_SIMULATION_CANCELED))
    # status_dbから該当のmodel_idのレコードを削除する
    status_db_connection.delete_model(model_id)
    webapp_db_connection.update_status(model_id, static.SIMULATION_MODEL_RUN_STATUS_CANCELED,
                                                   MESSAGE_SIMULATION_CANCELED, None, datetime.now())
    return

def start_sim_cancel_process(model_id : str):
    status_db_connection.set_progress(model_id, status_db_connection.TASK_SIMULATION_CANCEL, status_db_connection.STATUS_IN_PROGRESS)
    run_task(status_db_connection.TASK_SIMULATION_CANCEL, model_id)
    return

def cancel_before_starting_task_end_process(model_id : str):
    msg = MESSAGE_EXECUTION_CANCELED
    # タスクが開始される前に終了
    logger.info(log_writer.format_str(model_id, msg))
    webapp_db_connection.update_status(model_id,static.SIMULATION_MODEL_RUN_STATUS_CANCELED, msg, None, None)
    return

def get_model_in_status_db(model_id : str):
    return status_db_connection.get_progress(model_id)

def cancel_process(model_id : str):
    model_in_status_db = get_model_in_status_db(model_id)
    if model_in_status_db is None:
        cancel_before_starting_task_end_process(model_id)
    else:
        task_id = model_in_status_db.task_id
        status_id = model_in_status_db.status_id
        if is_simulation_in_progress(task_id, status_id):
            # シミュレーション実行中のみ、シミュレーター内でシミュレーション中止を行う
            start_sim_cancel_process(model_id)
        else:
            # その他のタスクの場合はタスク終了まで実行は続けて、完了したら次のタスクに進まずに中止終了処理をする。
            if status_id == status_db_connection.STATUS_IN_PROGRESS:
                # 処理中の場合、そのタスク終了まで続行
                pass
            elif status_id == status_db_connection.STATUS_NORMAL_END:
                cancel_end_process(model_id)
            else:
                abnormal_end_process(model_id, task_id)
    return

def main():
    log_writer.fileConfig()
    logger.info('Start wrapper_organize.py')
    model_id = None
    task_id = status_db_connection.TASK_UNKNOWN
    try:
        # シミュレーションモデルテーブルから実行ステータスが開始処理中・実行中・中止処理中のレコードを取得する
        simulation_models = webapp_db_connection.select_model()
        models = [{"model_id" : str(simulation_model.simulation_model_id), 
                   "run_status" : simulation_model.run_status} 
                  for simulation_model in simulation_models]
        for model in models:
            model_id = model["model_id"]
            run_status = model["run_status"]
            logger.info(f'Start process : model_id = [{model_id}]')
            if run_status == static.SIMULATION_MODEL_RUN_STATUS_CANCEL_IN_PROGRESS:
                cancel_process(model_id)
            else:
                model_in_status_db = get_model_in_status_db(model_id)
                if model_in_status_db is None:
                    start_first_task(model_id)                
                else:
                    task_id = model_in_status_db.task_id
                    status_id = model_in_status_db.status_id
                    check_status_and_start_next_task(model_id, task_id, status_id)
        logger.info('Complete wrapper_organize.py')

    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"Wrapper統括サービス実行時エラー", e)

if __name__ == "__main__":
    main()