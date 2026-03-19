import requests
import sys
import os
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from fastapi import FastAPI, BackgroundTasks, status, Request, utils
from pydantic import BaseModel, field_validator
from typing import List
from convert_to_czml import ConvertToCZML
from remove_building import RemoveBuilding
from new_building import NewBuilding
from city_model.building import Building
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from common import file_path_generator, log_writer
from common import webapp_db_connection
from common.lockfile import LockMag
from common.utils import get_shared_folder, get_stl_folder, create_lock_folder
from new_plant_cover import NewPlantCover
from new_tree import NewTree

app = FastAPI()
logger = log_writer.getLogger()
log_writer.fileConfig()

class ConvertToCZMLArgs(BaseModel):
    region_id: str
    stl_type_id: int
class RemoveBuildingArgs(BaseModel):
    region_id: str
    building_id: List[str]

    @field_validator('building_id', mode='before')
    def validate_building_id(cls, v):
        for item in v:
            if not isinstance(item, str) or not item.count('-') == 1:
                logger.error(f"Invalid format for building_id: {item}")
                raise RequestValidationError([{'loc': ('building_id',), 'msg': 'Invalid format', 'type': 'value_error'}])
            part1, part2 = item.split('-')

            is_part1_digit = part1.isdigit()
            is_part2_digit = part2.isdigit()
            # 2m以上の植被は末尾にhが付くためpart2は「数字h」の形式も許容
            is_part2_h = part2.endswith('h') and part2[:-1].isdigit()

            if not (is_part1_digit and (is_part2_digit or is_part2_h)):
                logger.error(f"Invalid format for building_id: {item}")
                raise RequestValidationError([{'loc': ('building_id',), 'msg': 'Invalid format', 'type': 'value_error'}])
        return v
class NewBuildingArgs(BaseModel):
    coordinates: List[float]
    height: float
    region_id: str
    stl_type_id: int

class NewTreeArgs(BaseModel):
    coordinates: List[float]
    height: float
    canopy_diameter: float
    region_id: str

class NewPlantCoverArgas(BaseModel):
    coordinates: List[float]
    height: float
    region_id:str


@app.post("/convert_to_czml")
async def convert_to_czml(args: ConvertToCZMLArgs, background_tasks: BackgroundTasks):
    try:
        logger.info(f"Start convert to czml.")
        # REGIONからcoordinate_idを取得
        coordinate_id = webapp_db_connection.fetch_coordinate_id(args.region_id)

        # stl_type_idがSTL_TYPE.ground_flag=trueの場合、logger.warningを出力してHTTP_201_CREATEDを返す
        # TODO: BRIDGE_CFD-346 実証用の仮改修 ### 実証後に削除する
        ground_flag = webapp_db_connection.fetch_ground_flag(args.stl_type_id)
        if ground_flag:
            logger.warning(f"stl_type_id:{args.stl_type_id} is ground type. CZML conversion skipped.")
            return JSONResponse(status_code=status.HTTP_201_CREATED, content=None)

        # # STL_MODEL.stl_fileからファイルパスを取得
        stl_file = webapp_db_connection.fetch_stl_file(args.region_id, args.stl_type_id)
        validate_file_extension(stl_file, args.region_id, args.stl_type_id)

        # CZML変換の実行
        converter = ConvertToCZML(args.region_id, stl_file, args.stl_type_id, coordinate_id)
        logger.info(f"convert is called")

        # OBJ->CZML変換をバックグラウンドで処理
        background_tasks.add_task(converter.convert)
        return JSONResponse(status_code=status.HTTP_201_CREATED, content=None)
    except Exception as e:
        logger.error(f"Error was happend in server{e}")
        return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg":"Error was happend in server"})

@app.post("/remove_building")
async def remove_building(args: RemoveBuildingArgs, background_tasks: BackgroundTasks):
    try:
        stl_type_ids = []
        stl_files = []
        stl_types = []

        logger.info(f"Start remove building.")
        # building_idsからstl_type_idsを抜き出す
        stl_type_ids = Building.group_building_ids(args.building_id)
        logger.info(f"get stl_type_ids : [%s]." ,stl_type_ids)

        # STL_MODEL.stl_fileからファイルパスを取得
        for stl_type_id in stl_type_ids:
            stl_file = webapp_db_connection.fetch_stl_file(args.region_id, stl_type_id)
            validate_file_extension(stl_file, args.region_id, stl_type_id)
            stl_files.append(stl_file)
            stl_types.append(stl_type_id)

        # ロックファイルの存在確認
        lock_mag = LockMag(stl_files)
        if lock_mag.is_exist_lockfile():
            logger.warning("Lockfile exists.")
            return JSONResponse(status_code=status.HTTP_409_CONFLICT, content={"msg":"resource-conflict:"})
        else:
            # czmlカラムに空白をセット
            for stl_type_id in stl_types:
                stl_file = webapp_db_connection.update_czml_file_to_blank(args.region_id, stl_type_id)
                logger.info(f"stl_type_id is : [%s]." ,stl_file)

            # 対象フォルダにlockfileを作成
            logger.info(f"create lockfile.")
            if lock_mag.create_lockfile():
                # 建物削除を実行
                task = RemoveBuilding(args.region_id, args.building_id)
                logger.info(f"remove is called")

                # 建物削除実行をバックグラウンドで処理
                background_tasks.add_task(task.remove)
                return JSONResponse(status_code=status.HTTP_201_CREATED, content=None)
            else:
                logger.warning("Lockfile creation failed.")
                return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg": "Failed to create lockfile."})
    except Exception as e:
        logger.error(f"Error was happend in server {e}")
        return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg":"error was happend in server"})

@app.post("/new_building")
async def new_building(args: NewBuildingArgs, background_tasks: BackgroundTasks):
    try:
        logger.info(f"Start create new building.")  # stl_fileは1つ、要素１のリストとして呼び出す
        logger.info(f"region_id:[%s], stl_type_id:[%s]", args.region_id, args.stl_type_id)       
        stl_file = webapp_db_connection.check_stl_file(args.region_id, args.stl_type_id)
        logger.info(f"stl file is [%s]", stl_file)

        if stl_file is None: # 新規レコードを作成
            # 新規レコードのロジック
            city_model_id = webapp_db_connection.fetch_city_model_id(args.region_id)
            new_stl_file = get_stl_folder(city_model_id, args.region_id, args.stl_type_id)
            logger.info(f"new_stl_file is [%s]", new_stl_file)

            lock_folder = create_lock_folder(city_model_id, args.region_id, args.stl_type_id)
            logger.info(f"lock_folder is [%s]", lock_folder)
            lock_files = [new_stl_file]
            lock_mag = LockMag(lock_files)

            stl_type_info = webapp_db_connection.fetch_stl_type_info(args.stl_type_id)
            upload_datetime = 'now()'
            czml_file = ""
            new_record = []
            new_record.append(
                webapp_db_connection.StlModel(
                    region_id = args.region_id, stl_type_id = args.stl_type_id, stl_file = new_stl_file, upload_datetime = upload_datetime,
                    solar_absorptivity = stl_type_info.solar_absorptivity, heat_removal = stl_type_info.heat_removal, czml_file = czml_file
                )
            )
            logger.info(f"new_record is [%s]", new_record)

            webapp_db_connection.insert_stl_model(new_record) # レコード挿入
        else:
            validate_file_extension(stl_file, args.region_id, args.stl_type_id)
            stl_files = [stl_file]
            lock_mag = LockMag(stl_files)
            if lock_mag.is_exist_lockfile():
                logger.error("Lockfile exists.")
                return JSONResponse(status_code=status.HTTP_409_CONFLICT, content={"msg":"resource-conflict:"})
            else: # czmlカラムに空白をセット
                logger.info(f"set stl_file.")
                stl_file = webapp_db_connection.update_czml_file_to_blank(args.region_id, args.stl_type_id)

        # 対象フォルダにlockfileを作成
        logger.info(f"create lockfile.")
        if lock_mag.create_lockfile():
            # 建物作成を実行
            task = NewBuilding(args.coordinates, args.height, args.region_id, args.stl_type_id)
            logger.info(f"create is called")

            # 建物作成実行をバックグラウンドで処理
            background_tasks.add_task(task.create)
            return JSONResponse(status_code=status.HTTP_201_CREATED, content=None)
        else:
            logger.warning("Lockfile creation failed.")
            return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg": "Failed to create lockfile."})
    except Exception as e:
        logger.error(f"Error was happend in server. {e}.")
        return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg":"error was happend in server"})

@app.post("/new_tree")
async def new_tree(args: NewTreeArgs, background_tasks: BackgroundTasks):
    try:
        logger.info(f"Start new tree.")
        logger.info(f"region_id:[{args.region_id}]")
        stl_type_id = webapp_db_connection.fetch_stl_type_tree()
        stl_file = webapp_db_connection.check_stl_file(args.region_id, stl_type_id)

        if stl_file is None: # stl_modelレコードが存在しない場合、新規レコードを作成
            # ロックフォルダ作成のためSTLファイルのパスを取得
            city_model_id = webapp_db_connection.fetch_city_model_id(args.region_id)
            new_stl_file = get_stl_folder(city_model_id, args.region_id, stl_type_id)
            logger.info(f"new_stl_file is [{new_stl_file}]")

            # ロックフォルダを作成
            lock_folder = create_lock_folder(city_model_id, args.region_id, stl_type_id)
            logger.info(f"lock_folder is [{lock_folder}]")
            lock_files = [new_stl_file]
            lock_mag = LockMag(lock_files)

            # 新規レコードを作成
            upload_datetime = 'now()'
            czml_file = ""
            solar_absorptivity = None
            heat_removal = None
            # 追加する新規レコードのリストを作成
            new_record = []
            new_record.append(
                webapp_db_connection.StlModel(
                    region_id=args.region_id, stl_type_id=stl_type_id, stl_file=new_stl_file, upload_datetime=upload_datetime,
                    solar_absorptivity=solar_absorptivity, heat_removal=heat_removal, czml_file=czml_file
                )
            )
            logger.info(f"new_record is [{new_record}]")
            # レコード挿入
            webapp_db_connection.insert_stl_model(new_record)

        else:
            validate_file_extension(stl_file, args.region_id, stl_type_id)
            stl_files = [stl_file]
            lock_mag = LockMag(stl_files)
            if lock_mag.is_exist_lockfile():
                # ロックファイルが存在する場合
                logger.error("Lockfile exists.")
                return JSONResponse(status_code=status.HTTP_409_CONFLICT, content={"msg":"resource-conflict:"})
            else:
                # ロックファイルが存在しない場合、czml_fileを空白に更新
                logger.info(f"set stl_file.")
                stl_file = webapp_db_connection.update_czml_file_to_blank(args.region_id, stl_type_id)

        # 対象フォルダにlockfileを作成
        logger.info(f"create lockfile.")
        if lock_mag.create_lockfile():
            # 単独木作成
            task = NewTree(args.coordinates, args.height, args.canopy_diameter, args.region_id)
            logger.info(f"create is called")
        
            #単独木作成実行をバックグランドで処理
            background_tasks.add_task(task.create)
            return JSONResponse(status_code=status.HTTP_201_CREATED, content=None)
        else:
            logger.warning("Lockfile creation failed.")
            return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg": "Failed to create lockfile."})
    except Exception as e:
        logger.error(f"Error was happend in server {e}")
        return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg":"Error was happend in server"})


@app.post("/new_plant_cover")
async def new_plant_cover(args: NewPlantCoverArgas, background_tasks: BackgroundTasks):
    try:
        logger.info(f"Start new plamt cover.")
        logger.info(f"region_id:{args.region_id}")
        stl_type_id = webapp_db_connection.fetch_stl_type_plant_cover()
        stl_file = webapp_db_connection.check_stl_file(args.region_id, stl_type_id)
        logger.info(f"stl file is {stl_file}")

        # 該当の3D都市モデル/解析対象地域に植被が存在しない場合新規レコードを作成
        if stl_file is None:
            #ロックフォルダ作成のためSTLファイルパスを取得
            city_model_id = webapp_db_connection.fetch_city_model_id(args.region_id)
            new_stl_file = get_stl_folder(city_model_id, args.region_id, stl_type_id)
            logger.info(f"new_stl_file is {new_stl_file}")

            #ロックフォルダ作成
            lock_folder = create_lock_folder(city_model_id, args.region_id, stl_type_id)
            logger.info(f"lock_folder is {lock_folder}")
            lock_files = [new_stl_file] # LockMagにリスト型の引数を渡すため新規作成したSTLファイルをリスト化
            lock_mag = LockMag(lock_files)

            # 新規レコード作成
            upload_datetime = 'now()'
            czml_file = ""
            solar_absorptivity = None
            heat_removal = None
            # 追加する新規レコードのリストを作成
            new_record = []
            new_record.append(
                webapp_db_connection.StlModel(
                    region_id=args.region_id, stl_type_id=stl_type_id, stl_file=new_stl_file, upload_datetime=upload_datetime,
                    solar_absorptivity=solar_absorptivity, heat_removal=heat_removal, czml_file=czml_file
                )
            )
            logger.info(f"new_record is {new_record}")
            # レコード挿入
            webapp_db_connection.insert_stl_model(new_record)

        else:
            validate_file_extension(stl_file, args.region_id, stl_type_id)
            stl_files = [stl_file]
            lock_mag = LockMag(stl_files)
            if lock_mag.is_exist_lockfile():
                logger.error("Lockfile exists.")
                return JSONResponse(status_code=status.HTTP_409_CONFLICT, content={"msg":"resource-conflict:"})
            else: # ロックファイルが存在しない場合czml_fileに空白をセット
                logger.info(f"set stl_file.")
                stl_file = webapp_db_connection.update_czml_file_to_blank(args.region_id, stl_type_id)

        # 対象フォルダにlockfileを作成
        logger.info(f"create lockfile.")
        if lock_mag.create_lockfile():
            # 植被作成
            task = NewPlantCover(args.coordinates, args.height,  args.region_id)
            logger.info(f"create is called")

            #植被作成実行をバックグランドで処理
            background_tasks.add_task(task.create)
            return JSONResponse(status_code=status.HTTP_201_CREATED, content=None)
        else:
            logger.warning("Lockfile creation failed.")
            return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg": "Failed to create lockfile."})

    except Exception as e:
        logger.error(f"Error was happend in server. {e}.")
        return JSONResponse(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, content={"msg":"error was happend in server"})




# BaseModelで例外が発生したときのハンドラ
@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError):
    # エラーログの出力
    logger.error(f"detail: {exc.errors()}")
    print(f"detail: {exc.errors()}")
    # エラーレスポンスの返却
    return JSONResponse(status_code=status.HTTP_400_BAD_REQUEST, content={"detail": exc.errors()})

def validate_file_extension(filepath: str, region_id: str, stl_type_id: int):
    '''
    拡張子がobjまたはstlであるかチェック,それ以外の場合Exceptionをraise
    '''
    # filepathの拡張子が.objまたは.stlであるかをチェック。大文字・小文字は区別しない
    if not (filepath.lower().endswith('.obj') or filepath.lower().endswith('.stl')):
        # TODO: 実証後にコメントを外す
        # logger.error(f"Invalid file extension:{filepath}. File must have .obj or .stl extension.")
        # raise ValueError("File must have .obj or .stl extension.")
        # city_model_id = webapp_db_connection.fetch_city_model_id(region_id)

        # TODO: 実証が終わったら削除する
        ### 方針案1: STL_MODEL.stl_fileから取得されたファイルパスを親フォルダとする。
        #stl_file_fullpath = os.path.join(get_shared_folder(), filepath)
        # stl_file_fullpathに入っているファイルパスがディレクトリでない場合はそのファイルの親ディレクトリを返す。
        # ただし、その親ディレクトリが存在しない場合はそのまた親ディレクトリを返す。
        # それも存在しない場合はさらにその親ディレクトリを返す。
        # パスの要素数が5になるまで繰り返す
        stl_dir = _normalize_to_existing_mstl_dir(filepath)
        # 親ディレクトリ
        if stl_dir == "":
            raise ValueError("stl_type_id folder does not exist.")

        # new_stl_filepathから.objまたは.stlのファイルを探し、あればそのファイル名とそのファイルの更新時刻の一覧を取得する。
        files = os.listdir(os.path.join(get_shared_folder(), stl_dir))
        valid_files = [f for f in files if f.lower().endswith('.obj') or f.lower().endswith('.stl')]
        if not valid_files:
            # STLまたはOBJファイルが存在しない場合は、stl_type_id.objをSTL_MODEL.stl_fileにセットする。
            webapp_db_connection.update_stl_file(region_id, stl_type_id, os.path.join(stl_dir, f"{stl_type_id}.obj"))
            logger.info(f"Set new STL file: {stl_type_id}.obj")
        else:
            # STLまたはOBJファイルが存在する場合は、最も更新時刻が新しいファイルを取得する。
            stl_dir_fullpath = os.path.join(get_shared_folder(), stl_dir)
            latest_file = max(valid_files, key=lambda f: os.path.getmtime(os.path.join(stl_dir_fullpath, f)))
            # latest_fileのファイルパスのうち、get_shared_folder()以降のパスを取得する。
            latest_filepath = os.path.relpath(os.path.join(stl_dir_fullpath, latest_file), get_shared_folder())
            # latest_filepathにはファイルパスが入っているので、ファイル名部分をlatest_filenameとして取得
            latest_filename = os.path.basename(latest_filepath)

            # latest_filenameのファイル名部分が"H_"で始まるかどうかをチェック
            # latest_filenameが"H_"で始まる場合、latest_filepathのファイル名部分を"H_"を取り除いたファイル名にしてSTL_MODEL.stl_fileにセットする。
            # latest_filenameが"H_"で始まない場合、latest_filepathをそのままSTL_MODEL.stl_fileにセットする。
            if latest_filename.startswith("H_"):
                # latest_filenameが"H_"で始まる場合、latest_filepathのファイル名部分を"H_"を取り除いたファイル名にしてSTL_MODEL.stl_fileにセットする。
                new_file = os.path.join(os.path.dirname(latest_filepath), latest_filename[2:])
            else:
                new_file = latest_filepath

            webapp_db_connection.update_stl_file(region_id, stl_type_id, new_file)
            logger.info(f"Set new STL file: {new_file}")
        ### BRIDGE_CFD-339 実証用の仮改修 ###
        # TODO: 実証後に削除する

    logger.info("File extension is valid.")


def _normalize_to_existing_mstl_dir(mstl_file):
    """
    mstl_file: 入力パス（文字列）。末尾スラッシュがある想定だが無くても可。
    file_path_generator: get_shared_folder() と combine(base, rel) を提供するオブジェクト。
    戻り値: 実在するディレクトリ（mstl_file がファイルの場合はそのままの文字列）、存在しなければ空文字列。
    """
    path = mstl_file.strip("/")
    parts = path.split("/")

    # minimal expected length: ['city_model', <id>, 'region', <id>, <stl_type_id>] -> 5 パート
    min_len = 5
    if len(parts) < min_len:
        try_paths = [path]
    else:
        try_paths = []
        for cut in range(0, len(parts) - (min_len - 1)):
            candidate_parts = parts[:len(parts) - cut]
            candidate = "/".join(candidate_parts) + "/"
            try_paths.append(candidate)

    shared = ""
    try:
        shared = get_shared_folder()
    except Exception:
        return ""

    for candidate in try_paths:
        fs_candidate = os.path.join(shared, candidate)
        if os.path.isdir(fs_candidate):
            return candidate
    return ""
## テストコード
## >cd C:~~~\BRIDGE_PLATEAU\git\srcAPI
## >uvicorn main:app --host 127.0.0.1 --port 5000 --reload

BASE_URL = 'http://localhost:5000'

def test_convert_to_czml():
    response = requests.post(f'{BASE_URL}/convert_to_czml', json={'region_id': 'fc1efd39-501c-464b-a954-310aa9a6f67b', 'stl_type_id': 1})
    assert response.status_code == 201
    print(f"Convert to CZML response: {response.status_code}")
    # 不正な入力テスト
    response_invalid = requests.post(f'{BASE_URL}/convert_to_czml', json={'region_id': 'fc1efd39-501c-464b-a954-310aa9a6f67b', 'stl_type_id': 'test'})
    assert response_invalid.status_code == 400
    print(f"Convert to CZML response: {response_invalid.status_code}")
    print(f"Invalid Convert to CZML response: {response_invalid.json()}")

def test_remove_building():
    # response = requests.post(f'{BASE_URL}/remove_building', json={'region_id': 'fc1efd39-501c-464b-a954-310aa9a6f67b','building_id': ["1-2"]})
    # assert response.status_code in [201, 409, 400, 500]  # 201 or conflict
    # print(f"Remove building response: {response.status_code}")
    # # 不正な入力テスト
    response_invalid = requests.post(f'{BASE_URL}/remove_building', json={'region_id': 'fc1efd39-501c-464b-a954-310aa9a6f67b','building_id': ["1-d"]})
    assert response_invalid.status_code in [400, 500]
    print(f"Remove building response: {response_invalid.status_code}")

def test_new_building():
    response = requests.post(f'{BASE_URL}/new_building', json={
        'coordinates': [
            139.6711010315235, 35.274832117277285, 82.01646099999999,
            139.67104837208169, 35.27481322488996, 82.01646099999999
        ],
        'height': 3.8,
        'region_id': '12e3821d-1b71-4fd7-89ed-bb706c7a84f5',
        'stl_type_id': 6
    })
    assert response.status_code in [201, 409, 500]  # 201 for success, 409 for conflict
    print(f"New building response: {response.status_code}")
    # 不正な入力テスト
    # response_invalid = requests.post(f'{BASE_URL}/new_building', json={
    #     'coordinates': 'invalid_coordinates',
    #     'height': 'invalid_height',
    #     'region_id': 'fc1efd39-501c-464b-a954-310aa9a6f67b',
    #     'stl_type_id': 'invalid'
    # })
    # assert response_invalid.status_code == 400
    # print(f"New building response: {response_invalid.status_code}")
    # print(f"Invalid New building response: {response_invalid.json()}")

if __name__ == "__main__":
    logger = log_writer.getLogger()
    log_writer.fileConfig()
    logger.info("test.")

    #test_convert_to_czml()
    test_remove_building()
    #test_new_building()