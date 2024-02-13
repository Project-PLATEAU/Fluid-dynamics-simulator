from common import log_writer
from common import utils
from common import file_controller
from common import file_path_generator
from common import status_db_connection
from common import shell_controller
from common import static

def execute(model_id:str):
    shell_file_path_sim = file_path_generator.get_model_id_folder_sim(model_id)
    return shell_controller.launch(shell_file_path_sim)

def main(model_id:str):
    task_id = status_db_connection.TASK_SIMULATION_EXEC
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_SIMULATION_EXEC、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)
    
    try:
        #simulation_inputフォルダ配下のSIMULATION_MODEL.idのフォルダをコピーし、シミュレーションマシンに転送する
        execute(model_id)
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをNORMAL_ENDに、proces_idを取得したpidに更新する。。
        status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_NORMAL_END)
    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"シミュレーション実行サービス実行時エラー", e)
        

if __name__ == "__main__":
    model_id=utils.get_args(1)[0] 
    main(model_id)