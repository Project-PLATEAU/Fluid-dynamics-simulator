from sqlalchemy import create_engine, or_
from sqlalchemy.orm import sessionmaker, scoped_session
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy.ext.automap import automap_base
from contextlib import contextmanager
from common.utils import get_settings
from common import log_writer
from common import static

logger = log_writer.getLogger()
log_writer.fileConfig()

# 未利用
def initialize():
    return

type = get_settings("WebappDB","type")
user = get_settings("WebappDB","user")
password = get_settings("WebappDB","password")
host = get_settings("WebappDB","host")
port = get_settings("WebappDB","port")
dbname = get_settings("WebappDB","dbname")

# DB接続
DATABASE = f"{type}://{user}:{password}@{host}:{port}/{dbname}"
# Engine作成
engine = create_engine(DATABASE, echo=False)

# テーブル準備
Base = automap_base()
Base.prepare(engine, reflect=True)

# テーブル定義
CityModel = Base.classes.city_model
StlModel = Base.classes.stl_model
Solver = Base.classes.solver
SimulationModel = Base.classes.simulation_model
SimulationModelPolicy = Base.classes.simulation_model_policy
Policy = Base.classes.policy
Region = Base.classes.region
Height = Base.classes.height
StlType = Base.classes.stl_type
SolarAbsorptivity = Base.classes.solar_absorptivity
Visualization = Base.classes.visualization
# (未定義)
# CityModelReferenceAuthority = Base.classes.city_model_reference_authority
# SimulationModelReferenceAuthority = Base.classes.simulation_model_reference_authority
# Migrations = Base.classes.migrations
# UserAccount = Base.classes.user_account

# Session作成(定義)
# autoflush=False → Updateでsession.commit()
Session = scoped_session(sessionmaker(autocommit=False, autoflush=False, bind=engine))

@contextmanager
def session_con():
    session = Session()
    try:
        yield session
    except Exception as e:
        session.rollback()
        logger.error(f"Database error: {str(e)}")
        raise Exception(f"Database error: {str(e)}")

@contextmanager
def session_scope():
    session = Session()
    try:
        yield session
        session.commit()
    except Exception as e:
        session.rollback()
        logger.error(f"Database error: {str(e)}")
        raise Exception(f"Database error: {str(e)}")
    finally:
        session.close()

# # 2.1 Wrapper統括サービス
# 実行ステータスが1,2,5いずれかのレコードを取得
def select_model():
    initialize()
    with session_con() as session:
        model = session.query(SimulationModel).filter(
            or_(SimulationModel.run_status == static.SIMULATION_MODEL_RUN_STATUS_START_IN_PROGRESS,
                SimulationModel.run_status == static.SIMULATION_MODEL_RUN_STATUS_IN_PROGRESS,
                SimulationModel.run_status == static.SIMULATION_MODEL_RUN_STATUS_CANCEL_IN_PROGRESS)
        ).all()  # use all() to fetch all results
        return model
# 実行ステータスの更新
def update_status(model_id, status_id,run_status_details,cfd_error_log_file,last_sim_end_datetime):
    initialize()
    with session_scope() as session:
        model = session.query(SimulationModel)\
            .where(SimulationModel.simulation_model_id == model_id)\
            .first()
        model.run_status = status_id
        model.run_status_details = run_status_details
        model.cfd_error_log_file = cfd_error_log_file
        model.last_sim_end_datetime = last_sim_end_datetime
    return
# # 2.2 インプットデータ変換サービス
# 対象レコードの取得：WEBアプリDBのシミュレーションモデルテーブルと解析対象地域テーブルをJoinし、引数で取得したシミュレーションモデルIDのレコードを取得
def fetch_model(model_id):
    initialize()
    with session_con() as session:
        try:
            model = session.query(SimulationModel, Region)\
            .join(SimulationModel, SimulationModel.city_model_id == Region.city_model_id)\
            .filter(SimulationModel.simulation_model_id == model_id).one()
            return model
        except NoResultFound :
            logger.error(f'No records found for model_id: {model_id}')
            raise Exception(f'No records found for model_id: {model_id}')
        except MultipleResultsFound:
            logger.error(f'Multiple records found for model_id: {model_id}')
            raise Exception(f"Multiple records found for model_id: {model_id}")
# SMテーブルのsolver_idをキーにして、solverテーブルからsolver_compressed_fileを取得
def fetch_solver(solver_id):
    initialize()
    with session_con() as session:
        try:
            solver = session.query(Solver).where(Solver.solver_id == solver_id).one()
            return solver
        except NoResultFound :
            logger.error(f'No records found for solver_id: {solver_id}')
            raise Exception(f'No records found for solver_id: {solver_id}')
        except MultipleResultsFound:
            logger.error(f'Multiple records found for solver_id: {solver_id}')
            raise Exception(f"Multiple records found for solver_id: {solver_id}")
# stlテーブルから特定地域のstlレコードを取得し、日射吸収率も結合する
def select_stls(region_id,model_id):
    initialize()
    with session_con() as session:
        records = session.query(SolarAbsorptivity, StlModel)\
        .join(SolarAbsorptivity, SolarAbsorptivity.stl_type_id == StlModel.stl_type_id)\
        .filter(StlModel.region_id == region_id, SolarAbsorptivity.simulation_model_id == model_id)\
        .all()
    return records

def select_policies(model_id,stl_type_id):
    with session_con() as session:
        try:
            records = session.query(SimulationModelPolicy, Policy)\
            .join(SimulationModelPolicy, SimulationModelPolicy.policy_id == Policy.policy_id)\
            .filter(SimulationModelPolicy.simulation_model_id == model_id, SimulationModelPolicy.stl_type_id == stl_type_id)\
            .all()
            return records
        except NoResultFound :
            logger.error(f'No records found for model_id: {model_id} & stl_type_id: {stl_type_id}')
            raise Exception(f'No records found for solver_id: {model_id} & stl_type_id: {stl_type_id}')

# # 2.7 アウトプットデータ変換サービス
# 高さテーブルからすべてのレコードを取得
def fetch_height():
    initialize()
    with session_con() as session:
        height = session.query(Height).all()
    return height
# VISUALIZATIONテーブルから特定のレコードがあれば削除する
def fetch_and_delete_visualization(model_id):
    initialize()
    with session_scope() as session:
        try:
            # filterメソッドを使って条件を指定
            del_records = session.query(Visualization).filter(Visualization.simulation_model_id == model_id).all()
            if del_records:
                for del_record in del_records:
                    session.delete(del_record)
            else:
                return
        except NoResultFound :
            logger.error(f'No records found for solver_id: {model_id}')
            raise Exception(f'No records found for solver_id: {model_id}')
    return
# VISUALIZATIONテーブルにリストに含まれるすべてのレコードを挿入する
def insert_visualization(visualizations):
    initialize()
    with session_scope() as session:
        try:
            session.add_all(visualizations)
        except Exception as e:
            logger.error(f"Database error: {str(e)}")
            raise Exception(f"Database error: {str(e)}")
    return
#STLファイル種別テーブルから地面フラグが有効なレコードのidを取得
def get_ground_stl_type_ids():
    initialize()
    with session_con() as session:
        records = session.query(StlType).filter(StlType.ground_flag==True).all()
    return records