import sys
import os
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from sqlalchemy import create_engine, or_, and_
from sqlalchemy.orm import sessionmaker, scoped_session
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy.ext.automap import automap_base
from contextlib import contextmanager
from common.utils import get_settings
from common import log_writer
#from common import static

logger = log_writer.getLogger()
log_writer.fileConfig()

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
Base.prepare(autoload_with=engine)

# テーブル定義
StlModel = Base.classes.stl_model
StlType = Base.classes.stl_type
Region = Base.classes.region
# (未定義)
# CityModel = Base.classes.city_model
# Solver = Base.classes.solver
# SimulationModel = Base.classes.simulation_model
# SimulationModelPolicy = Base.classes.simulation_model_policy
# Policy = Base.classes.policy
# Height = Base.classes.height
# SolarAbsorptivity = Base.classes.solar_absorptivity
# Visualization = Base.classes.visualization
# CityModelReferenceAuthority = Base.classes.city_model_reference_authority
# SimulationModelReferenceAuthority = Base.classes.simulation_model_reference_authority
# Migrations = Base.classes.migrations
# UserAccount = Base.classes.user_account

# Session作成(定義)
# autoflush=False → Updateでsession.commit()
Session = scoped_session(sessionmaker(autocommit=False, autoflush=False, bind=engine))

# fetch
@contextmanager
def session_con():
    session = Session()
    try:
        yield session
    except Exception as e:
        session.rollback()
        logger.error(f"Database error: {str(e)}")
        raise Exception(f"Database error: {str(e)}")

# 挿入/削除/更新
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

# # 対象：STL＿MODELテーブル
def fetch_stl_file(region_id, stl_type_id) -> str:
    """
    STL_MODELテーブルで[region_id, stl_type_id]に合致する一意のSTLファイルを取得
    """
    initialize()
    with session_con() as session:
        try:
            record = session.query(StlModel).filter(
                and_(StlModel.region_id == region_id, StlModel.stl_type_id == stl_type_id)).one()
            logger.info(f'Get stl_file. region_id: {region_id} & stl_type_id: {stl_type_id}')
            return record.stl_file
        except NoResultFound :
            logger.error(f'No stl_file found for region_id: {region_id} & stl_type_id: {stl_type_id}')
            raise Exception(f'No stl_file found for region_id: {region_id} & stl_type_id: {stl_type_id}')

def check_stl_file(region_id, stl_type_id) -> str:
    """
    STL_MODELテーブルで[region_id, stl_type_id]に合致する一意のSTLファイルを取得
    """
    initialize()
    with session_con() as session:
        try:
            record = session.query(StlModel).filter(
                and_(StlModel.region_id == region_id, StlModel.stl_type_id == stl_type_id)).first()
            if record:
                logger.info(f'Get stl_file. region_id: {region_id} & stl_type_id: {stl_type_id}')
                return record.stl_file
            else :
                return None
        except Exception as e:
            logger.error(f"Database error: {str(e)}")
            raise Exception(f"Database error: {str(e)}")

def update_czml_file_to_blank(region_id, stl_type_id):
    """
    STL_MODELテーブルの[czml_file]カラムに空白をセット
    """
    initialize()
    with session_scope() as session:
        # 対象レコードを抽出
        stl_files = session.query(StlModel).filter(
            and_(StlModel.region_id == region_id, StlModel.stl_type_id == stl_type_id)).all()
        if not stl_files:
            logger.error(f'No stl_files found for region_id: {region_id} & stl_type_id: {stl_type_id}')
            raise Exception(f'No stl_files found for region_id: {region_id} & stl_type_id: {stl_type_id}')
        # czml_fileカラムに空白をセット
        for stl_file in stl_files:
            stl_file.czml_file = ''
        logger.info(f'Update czml_file to blank.')

def update_czml_file(region_id, stl_type_id, czml_file):
    """
    STL_MODELテーブルで[region_id, stl_type_id]に合致する一意のレコードに対して、新規/更新したCZMLファイルを配置
    """
    initialize()
    with session_scope() as session:
        try:
            # 対象レコードを抽出
            stl_file = session.query(StlModel).filter(
                and_(StlModel.region_id == region_id, StlModel.stl_type_id == stl_type_id)).one()
            # czml_fileカラムにczmlファイルをセット
            stl_file.czml_file = czml_file
            logger.info(f'Update czml_file. region_id: {region_id} & stl_type_id: {stl_type_id}')
        except NoResultFound :
            logger.error(f'No stl_file found for region_id: {region_id} & stl_type_id: {stl_type_id}')
            raise Exception(f'No stl_file found for region_id: {region_id} & stl_type_id: {stl_type_id}')

def insert_stl_model(stl_models):
    """
    STL_MODELテーブルにリストに含まれるすべてのレコードを挿入する
    """
    initialize()
    with session_scope() as session:
        try:
            session.add_all(stl_models)
        except Exception as e:
            logger.error(f"Database error: {str(e)}")
            raise Exception(f"Database error: {str(e)}")
    return

# # 対象：STL_TYPEテーブル
def fetch_stl_type_info(stl_type_id) -> int:
    """
    STL_TYPEテーブルで[stl_type_id]に合致するstl_typeを取得
    """
    initialize()
    with session_con() as session:
        try:
            record = session.query(StlType).filter(StlType.stl_type_id == stl_type_id).one()
            logger.info(f'Get stl_type. stl_type_id: {stl_type_id}')
            return record
        except NoResultFound :
            logger.error(f'No stl_type found for stl_type_id: {stl_type_id}')
            raise Exception(f'No stl_type found for stl_type_id: {stl_type_id}')

# # 対象：REGIONテーブル
def fetch_coordinate_id(region_id) -> int:
    """
    REGIONテーブルで[region_id ]に合致するcoordinate_idを取得
    """
    initialize()
    with session_con() as session:
        try:
            record = session.query(Region).filter(Region.region_id == region_id).one()
            logger.info(f'Get coordinate_id. region_id: {region_id}')
            return record.coordinate_id
        except NoResultFound :
            logger.error(f'No coordinate_id found for region_id: {region_id}')
            raise Exception(f'No coordinate_id found for region_id: {region_id}')

def fetch_city_model_id(region_id) -> int:
    """
    REGIONテーブルで[region_id]に合致するcity_model_idを取得
    """
    initialize()
    with session_con() as session:
        try:
            record = session.query(Region).filter(Region.region_id == region_id).one()
            logger.info(f'Get city_model_id. region_id: {region_id}')
            return record.city_model_id
        except NoResultFound :
            logger.error(f'No city_model_id found for region_id: {region_id}')
            raise Exception(f'No city_model_id found for region_id: {region_id}')

def main():
    # テスト用のデータ
    region_id = 'fc1efd39-501c-464b-a954-310aa9a6f67b'
    city_model_id = '130d1cd9-12d6-4b95-899c-bd56237bd8cb'
    stl_type_id = 4
    stl_type_id_list = [4, 1]
    czml_file = "sample.czml"
    stl_models = []
    stl_models.append(StlModel(region_id='fc1efd39-501c-464b-a954-310aa9a6f67b', stl_type_id=1, stl_file='sample.stl', upload_datetime='now()', solar_absorptivity=9.3, heat_removal=93, czml_file='sample.czml'))

    # # fetch_stl_typeのテスト
    try:
        stl_type = fetch_stl_type_info(stl_type_id)
        print(f"Fetched solar_absorptivity: {stl_type.solar_absorptivity}, heat_removal: {stl_type.heat_removal}")
    except Exception as e:
        print(f"Error fetching city_model_id: {e}")

    # # fetch_xx_idのテスト
    # try:
    #     city_model_id = fetch_city_model_id(region_id)
    #     coordinate_id = fetch_coordinate_id(region_id, city_model_id)
    #     print(f"Fetched city_model_id: {city_model_id}, coordinate_id: {coordinate_id}")
    # except Exception as e:
    #     print(f"Error fetching city_model_id: {e}")

    # fetch_stl_fileのテスト
    # try:
    #     record = fetch_stl_file(region_id, stl_type_id)
    #     print(f"Fetched STL file: {record.stl_file}")
    # except Exception as e:
    #     print(f"Error fetching STL file: {e}")

    # # update_czml_file_to_blankのテスト
    # try:
    #     update_czml_file_to_blank(region_id, stl_type_id_list)
    #     print(f"Updated czml_file to blank for region_id: {region_id} and stl_type_id_list: {stl_type_id_list}")
    # except Exception as e:
    #     print(f"Error updating czml_file to blank: {e}")

    # # update_czml_fileのテスト
    # try:
    #     update_czml_file(region_id, stl_type_id, czml_file)
    #     print(f"Updated czml_file to '{czml_file}' for region_id: {region_id} and stl_type_id: {stl_type_id}")
    # except Exception as e:
    #     print(f"Error updating czml_file: {e}")

    # # update_czml_fileのテスト
    # try:
    #     insert_stl_model(stl_models)
    #     print(f"Add stl_models.")
    # except Exception as e:
    #     print(f"Error insert stl_model: {e}")

if __name__ == "__main__":
    main()
