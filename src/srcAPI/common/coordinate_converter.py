from pyproj import Transformer

EPSG_CODE_LATLON = 4326 #wgs84

def to_epsg_code(system_id:int):
    return 6668 + system_id #例：IX系(9系)が6677

class ConverterToLatLon:
    def __init__(self, system_id) -> None:
        self.rect_epsg = to_epsg_code(system_id)
        self._transformer =  Transformer.from_proj(self.rect_epsg, EPSG_CODE_LATLON)
    def convert(self, x:float, y:float) -> tuple:
        # 平面直角座標系だとxとyが逆転する。南北方向の距離（北が正）を先、東西方向の距離（東が正）を後に渡す
        lat,lon  = self._transformer.transform(y, x)
        return lat, lon

class ConverterFromLatLon:
    def __init__(self, system_id) -> None:
        self.rect_epsg = to_epsg_code(system_id)
        self._transformer = Transformer.from_proj(EPSG_CODE_LATLON, self.rect_epsg)
    
    def convert(self, lat: float, lon: float):
        x, y = self._transformer.transform(lat, lon)
        return x, y