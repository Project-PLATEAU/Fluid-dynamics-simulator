from fastapi import FastAPI, BackgroundTasks, Request
from pydantic import ValidationError, BaseModel
from convert_to_czml import ConvertToCZML
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from typing import List

REQUIRED_RESULT = 500

app = FastAPI()

class ConvertToCZMLArgs(BaseModel):
    region_id: str
    if REQUIRED_RESULT == 400:
        stl_type_id: str
    else:
        stl_type_id: int

class RemoveBuildingArgs(BaseModel):
    if REQUIRED_RESULT == 400:
        region_id: int
    else:
        region_id: str
    building_id: List[str]

class NewBuildingArgs(BaseModel):
    coordinates: List[float]
    height: float
    if REQUIRED_RESULT == 400:
        region_id: int
    else:
        region_id: str
    stl_type_id: int

@app.post("/convert_to_czml")
async def add_czml(args: ConvertToCZMLArgs, background_tasks: BackgroundTasks):
    try:
        print(f"region_id={args.region_id}")
        print(f"stl_type_id={args.stl_type_id}")
        
        if REQUIRED_RESULT == 500:
            raise
        return JSONResponse(status_code=201, content=None)
    except Exception as e:
        print(e)
        return JSONResponse(status_code=500, content={"msg":"error was happend in server"})

@app.post("/remove_building")
async def remove_building(args: RemoveBuildingArgs, background_tasks: BackgroundTasks):
    try:
        print(f"region_id={args.region_id}")
        print(f"building_ids={args.building_id}")
        
        if REQUIRED_RESULT == 500:
            raise
        response = JSONResponse(status_code=409, content={"msg":"resource conflict"}) if REQUIRED_RESULT == 409 else JSONResponse(status_code=201, content=None)
        return response
    except Exception as e:
        print(e)
        return JSONResponse(status_code=500, content={"msg":"error was happend in server"})

@app.post("/new_building")
async def new_building(args: NewBuildingArgs, background_tasks: BackgroundTasks):
    try:
        print(f"coordinates={args.coordinates}")
        print(f"region_id={args.height}")
        print(f"region_id={args.region_id}")
        print(f"building_ids={args.stl_type_id}")

        if REQUIRED_RESULT == 500:
            raise
        response = JSONResponse(status_code=409, content={"msg":"resource conflict"}) if REQUIRED_RESULT == 409 else JSONResponse(status_code=201, content=None)
        return response
    except Exception as e:
        print(e)
        return JSONResponse(status_code=500, content={"msg":"error was happend in server"})


# BaseModelで例外が発生したときのハンドラ
@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError):
    # エラーログの出力

    # エラーレスポンスの返却
    return JSONResponse(
        status_code=400,
        content={"detail": exc.errors()},
    )


