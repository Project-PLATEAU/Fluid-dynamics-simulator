from sqlalchemy import create_engine, Column, TEXT, Integer, DateTime, func, and_, String, MetaData, Table
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, scoped_session
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from contextlib import contextmanager
from common import log_writer
from sqlalchemy.ext.automap import automap_base
from common.utils import get_settings
from datetime import datetime

logger = log_writer.getLogger()
log_writer.fileConfig()

# TASK_MASTER定義
TASK_UNKNOWN = 0 #不明なタスク
TASK_INPUT_DATA_CONVERT = 1 #インプットデータの変換
TASK_INPUT_DATA_TRANSFER = 2 #インプットデータの転送
TASK_SIMULATION_EXEC = 3 #シミュレーション開始
TASK_SIMULATION_MONITOR = 4 #シミュレーション監視
TASK_OUTPUT_DATA_TRANSFER = 5 #アウトプットデータ転送
TASK_OUTPUT_DATA_CONVERT = 6 #アウトプットデータ変換
TASK_SIMULATION_ERROR = 7 #シミュレーションエラー
TASK_SIMULATION_CANCEL = 8 #シミュレーションの中止

# STATUS_MASTER定義
STATUS_UNKNOWN = 0
STATUS_IN_PROGRESS = 1
STATUS_NORMAL_END = 2
STATUS_ABNORMAL_END = 3

# # # SQLiteデータベースに接続
# # # engine = create_engine('sqlite:///status.db')

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

# # # テーブルを定義
# # Base = declarative_base()
# # class SIMULATION_MODEL(Base):
# #     __tablename__ = 'SIMULATION_MODEL'

# #     id = Column(TEXT, primary_key=True)
# #     task_id = Column(Integer)
# #     status_id = Column(Integer)
# #     created_at = Column(DateTime(timezone=True), server_default=func.now())
# #     updated_at = Column(DateTime(timezone=True), onupdate=func.now())

# # # テーブルを作成
# # Base.metadata.create_all(engine)

# テーブル準備
Base = automap_base()
Base.prepare(engine, reflect=True)

# テーブル定義
CityModel = Base.classes.city_model
StatusDB_SimulationModel = Base.classes.statusdb_simulation_model

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

#WrapperDB側の実装待ち
def get_progress(model_id:str):
    with session_con() as session:
            try:
                record = session.query(StatusDB_SimulationModel).filter(StatusDB_SimulationModel.id == model_id).one()
            except NoResultFound :
                logger.info(f'No records found for model_id : {model_id}')
                return None
            except MultipleResultsFound:
                logger.error(f'Multiple records found for model_id: {model_id}')
                raise Exception(f"Multiple records found for model_id: {model_id}")
    return record

#Insert
def insert_model(model_id:str, task_id:int, status_id:int):
    with session_scope() as session:
        try:
            new_record = StatusDB_SimulationModel(
                id = model_id,
                task_id = task_id,
                status_id = status_id,
                created_at = datetime.now(),
                updated_at = datetime.now()
                )
            session.add(new_record)
        except Exception as e:
            logger.error(f"Database error: {str(e)}")
            raise Exception(f"Database error: {str(e)}")
    return

#Update
def set_progress(model_id:str, task_id:int, status_id:int):
    with session_scope() as session:
        try:
            record = session.query(StatusDB_SimulationModel).filter(StatusDB_SimulationModel.id==model_id).one()
            record.task_id = task_id
            record.status_id = status_id
            record.updated_at = datetime.now()
        except Exception as e:
            logger.error(f"Database error: {str(e)}")
            raise Exception(f"Database error: {str(e)}")
    return

# #Select
def check(model_id, task_id :int, status_id:int):
    with session_con() as session:
        try:
            session.query(StatusDB_SimulationModel).filter(
                and_(StatusDB_SimulationModel.id == model_id,
                    StatusDB_SimulationModel.task_id == task_id,
                    StatusDB_SimulationModel.status_id == status_id)
            ).one()
        except NoResultFound :
            logger.error(f'No records found for model_id: {model_id}')
            raise Exception(f'No records found for model_id: {model_id}')
        except MultipleResultsFound:
            logger.error(f'Multiple records found for model_id: {model_id}')
            raise Exception(f"Multiple records found for model_id: {model_id}")
    return

# #Delete
def delete_model(model_id : str):
    with session_scope() as session:
        try:
            # filterメソッドを使って条件を指定
            del_record = session.query(StatusDB_SimulationModel).filter(StatusDB_SimulationModel.id==model_id).one()
            if del_record:
                session.delete(del_record)
            else:
                return
        except NoResultFound :
            logger.error(f'No records found for solver_id: {model_id}')
            raise Exception(f'No records found for solver_id: {model_id}')
    return

def throw_error(model_id : str,task_id : int,error_message : str, exeption : Exception):
    log_writer.fileConfig()
    #log_writerでWrapperコンテナ内のログにエラー情報を出力
    logger.error(log_writer.format_str(model_id, format(task_id,'0')+'でエラーが発生しました。'+error_message+' '))
    logger.error(exeption)
    #Wrapper統括サービス（wrapper_organize.py）に例外情報を事後的に取得できるよう、Wrapperコンテナ内の内部記憶に
    set_progress(model_id,task_id,STATUS_ABNORMAL_END)
    raise exeption