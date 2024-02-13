from common import log_writer
from common import webapp_db_connection
from common import status_db_connection
from common import file_controller
from common import shell_controller
from common import utils
from common import file_path_generator
import input_data_convert
import input_data_transfer
import simulation_exec
import simulation_monitor
import output_data_transfer
import output_data_convert

# import simulation_error
# import simulation_cancel

logger = log_writer.getLogger()

def main():
    # model_id = "c8e0972d-3786-4bc9-8cda-ccc6da25c923"
    # model_id = "5bfa32c2-baf0-43f8-aa6d-fd585287f6b5"
    model_id = "f0070edd-91be-4462-90a4-4c5ca509cccc"
    # input_data_convert.convert(model_id)
    # input_data_transfer.transfer(model_id)
    # simulation_exec.execute(model_id)
    # simulation_monitor.monitor(model_id)
    output_data_transfer.transfer(model_id)
    # output_data_convert.convert(model_id)
    
    # input
    # status_db_connection.check('c8e0972d-3786-4bc9-8cda-ccc6da25c923', 3, 1)
    # status_db_connection.insert_model('a1e0972d-3786-4bc9-8cda-ccc6da25c932', 1, 1)
    # status_db_connection.set_progress('a1e0972d-3786-4bc9-8cda-ccc6da25c932', 2, 1)

if __name__ == "__main__":
    # shell_controller.put_folder('/home/bridge-plateau-cfd/test',file_path_generator.get_root_folder_sim())
    main()
    # shell_controller.launch("/tmp/")
    # bool = shell_controller.monitor("/tmp/")
    # out = shell_controller.abort("/tmp/")
    # file_controller.copy_file_fs("test.txt","C:/Users/HarukaNakanose/Documents/prj/BRIDGE_PLATEAU/git/srcBatch/log")
    #file_controller.put_folder("./test.txt", "/tmp/test.txt")

    # models = webapp_db_connection.fetch_height()
    # for fetch_height in models:
    #     print(fetch_height.height_id, fetch_height.height)

    # records = webapp_db_connection.get_ground_stl_type_ids()
    # for stl_type in records:
    #     print(stl_type.stl_type_id)

    # stls = webapp_db_connection.select_stls("6a7d5869-2127-42f6-b714-510595b72457", "c8e0972d-3786-4bc9-8cda-ccc6da25c923")
    # for stl_info in stls:
    #     print(stl_info.stl_model.stl_file, stl_info.solar_absorptivity.solar_absorptivity)

    # modelid = 1
    # models = webapp_db_connection.fetch_model(modelid)
    # for model in models:
    #     print(
    #     model.simulation_model.solver_id
    #     )
        # region_id = model.city_model.city_model_id
        # place = model.city_model.identification_name
        # name = model.city_model_reference_authority.user_id
        # print(region_id, name, place)

    #webapp_db_connection.update_status(1, 2)
    # records = webapp_db_connection.select_model()
    # for record in records:
    #     print(record.simulation_model_id)
    #     print(record.run_status)


    # stls = webapp_db_connection.fetch_solver(region)
    # for stl in stls:
    #     region_id = stl.solver_compressed_file
    #     print(region_id)
