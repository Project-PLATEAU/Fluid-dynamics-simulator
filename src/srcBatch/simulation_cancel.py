from common import log_writer
from common import utils
from common import file_controller
from common import file_path_generator
from common import status_db_connection
from common import shell_controller
from common import static

def abort(model_id:str): 
    path_copied_shell_folder = file_path_generator.get_model_id_folder_sim(model_id)
    path_copied_shell_file = file_path_generator.combine(path_copied_shell_folder,static.FILE_NAME_OPENFOAM_ABORT)
    shell_controller.abort(path_copied_shell_file)

def main(model_id:str):
    task_id = status_db_connection.TASK_SIMULATION_CANCEL
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_SIMULATION_CANCEL、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)
    
    try:
        abort(model_id)
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをNORMAL_ENDに、proces_idを取得したpidに更新する。。
        status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_NORMAL_END)
    except Exception as e:
        #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
        status_db_connection.throw_error(model_id,task_id,"シミュレーション中止サービス実行時エラー", e)
        

if __name__ == "__main__":
    model_id=utils.get_args(1)[0] 
    main(model_id)