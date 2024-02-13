from common import utils
from common import shell_controller
from common import file_path_generator
from common import status_db_connection

def transfer(model_id:str):
    path_source= file_path_generator.get_model_id_folder_sim(model_id)
    path_destination = file_path_generator.get_simulation_output_folder_fs()
    shell_controller.get_folder(path_source,path_destination, model_id)

def main(model_id:str):
    task_id = status_db_connection.TASK_OUTPUT_DATA_TRANSFER
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_OUTPUT_DATA_TRANSFER、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)
    
    try:
        #シミュレーションマシンにssh接続し、SIMULATION_MODEL.idフォルダをsimulation_outputフォルダ内にコピーする
        transfer(model_id)
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをNORMAL_ENDに更新する。
        status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_NORMAL_END)
    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"シミュレーション結果転送サービス実行時エラー", e)
        

if __name__ == "__main__":
    model_id=utils.get_args(1)[0] 
    main(model_id)