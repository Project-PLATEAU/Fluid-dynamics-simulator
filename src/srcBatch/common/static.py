FOLDER_NAME_SIMULATION_INPUT='0.orig/inc'
FOLDER_NAME_SIMULATION_CONSTANT='constant/inc'
FOLDER_NAME_SIMULATION_SYSTEM='system/inc'
FOLDER_NAME_RESOURCES = 'resources'
FILE_NAME_OPENFOAM_LAUNCH='launcher'
FILE_NAME_OPENFOAM_MONITOR='Allrun'
FILE_NAME_OPENFOAM_ABORT='Allrun'

# WEBアプリDBのシミュレーションモデルテーブル.実行ステータス
SIMULATION_MODEL_RUN_STATUS_NOT_STARTED = 0         # 未
SIMULATION_MODEL_RUN_STATUS_START_IN_PROGRESS = 1   # 開始処理中
SIMULATION_MODEL_RUN_STATUS_IN_PROGRESS = 2         # 実行中
SIMULATION_MODEL_RUN_STATUS_NORMAL_END = 3          # 正常終了
SIMULATION_MODEL_RUN_STATUS_ABNORMAL_END = 4        # 異常終了
SIMULATION_MODEL_RUN_STATUS_CANCEL_IN_PROGRESS = 5  # 中止処理中
SIMULATION_MODEL_RUN_STATUS_CANCELED = 6            # 中止
SIMULATION_MODEL_RUN_STATUS_CANCELED_BY_ADMIN = 7   # 管理者中止
