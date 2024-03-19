from common import status_db_connection
from common import utils
import output_data_transfer
from common import log_writer
from common import file_controller
from common import file_path_generator
from common import shell_controller

logger = log_writer.getLogger()

def transfer_result_folder_and_get_destination(model_id:str):
    try:
        #シミュレーションマシンにssh接続し、SIMULATION_MODEL.idフォルダをsimulation_outputフォルダ内にコピーする
        path_source= file_path_generator.get_model_id_folder_sim(model_id)
        path_destination = file_path_generator.get_simulation_output_folder_fs()
        shell_controller.get_folder(path_source,path_destination, model_id)
    except FileNotFoundError as fnfe:
        logger.error(log_writer.format_str(model_id,"Simulation result folder was not found"))
        raise fnfe
    except Exception as e:
        logger.error(log_writer.format_str(model_id,"Failed to transfer simulation result folder"))
        raise e

def copy_error_logs(source : str, destination : str):
    # source/template/log.*
    file_controller.copy_log_files_fs(source, destination)
    return

def simulation_error(model_id:str):
    logger.info('[%s] Start simulation error handling.'%model_id)
    transfer_result_folder_and_get_destination(model_id)
    simulation_output_model_id_folder = file_path_generator.get_simulation_output_model_id_folder_fs(model_id)

    #converted_outputにmodel_id/logのフォルダを作成する
    #すでにconverted_outputにmodel_idフォルダがある場合は削除する
    model_id_folder = file_path_generator.get_converted_output_model_id_folder_fs(model_id)
    if file_controller.exist_folder_fs(model_id_folder):
        file_controller.delete_folder_fs(model_id_folder)
    log_folder = file_path_generator.get_error_log_folder_model_id_fs(model_id)
    file_controller.create_or_recreate_folder_fs(log_folder)
    #エラーログをコピーする
    copy_error_logs(simulation_output_model_id_folder, log_folder)
    #圧縮する
    file_controller.compress_log_files_fs(log_folder, 
                                          file_path_generator.get_compressed_file_extension())
    logger.info('[%s] Complete simulation error handling.'%model_id)

def main(model_id:str):
    task_id = status_db_connection.TASK_SIMULATION_ERROR
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_OUTPUT_DATA_TRANSFER、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)
    try:
        simulation_error(model_id)
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをNORMAL_ENDに更新する。
        status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_NORMAL_END)
    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"シミュレーションエラー処理サービス実行時エラー", e)

if __name__ == "__main__":
    model_id=utils.get_args(1)[0] 
    main(model_id)