from common import log_writer
from common import utils
from common import file_controller
from common import file_path_generator
from common import status_db_connection
from common import shell_controller
from common import static

def monitor(model_id:str)->(bool,bool): 
    shell_file_path_sim = file_path_generator.get_model_id_folder_sim(model_id)
    return shell_controller.monitor(shell_file_path_sim)

def main(model_id:str):
    task_id = status_db_connection.TASK_SIMULATION_MONITOR
    #STATUS_DBのSIMULATION_MODEL.idに引数から取得したIDで、task_idがTASK_SIMULATION_MONITOR、statusがIN_PROGRESSのレコードが存在する
    status_db_connection.check(model_id,task_id, status_db_connection.STATUS_IN_PROGRESS)
    
    res = monitor(model_id)
    if not(res[0]):
        if(res[1]):    # Allrunの戻り値が0である場合には(False,True)
            #引数で取得したSIMULATION_MODEL.idのレコードのstatusをNORMAL_ENDに更新する。
            status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_NORMAL_END)
        else:  # Allrunの戻り値が0でない場合には(False,False)
            #引数で取得したSIMULATION_MODEL.idのレコードのstatusをABNORMAL_ENDに更新する。
            status_db_connection.set_progress(model_id,task_id,status_db_connection.STATUS_ABNORMAL_END)
                        
        
if __name__ == "__main__":
    model_id=utils.get_args(1)[0] 
    main(model_id)