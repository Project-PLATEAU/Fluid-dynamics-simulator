#python -m pip install --user pyproj
from pyproj import Transformer

EPSG_CODE_LATLON = 4326 #wgs84

def to_epsg_code(system_id:int):
    return 6668 + system_id #例：IX系(9系)が6677
    
def convert_from_LatLon(system_id:int,lat:float,lon:float) -> tuple:
    '''
        Args:
            system_id(int): 平面角座標系（IX系であれば9）
            lat(float): 緯度（北緯）
            lon(float): 経度（東経）

        Returns:
            tuple: 平面角座標系のX座標(南→北、計算上はY座標),平面角座標系のY座標(西→東、計算上はX座標)  
    '''
    rect_epsg = to_epsg_code(system_id)
    tr = Transformer.from_proj(EPSG_CODE_LATLON, rect_epsg)
    x, y = tr.transform(lat,lon)
    return x, y

def convert_to_LatLon(system_id:int,x:float,y:float) -> tuple:
    '''
        Args:
            system_id(int): 平面角座標系（IX系であれば9）
            x(float): 平面角座標系のX座標(南→北、計算上はY座標)
            y(float): 平面角座標系のY座標(西→東、計算上はX座標)  

        Returns:
            tuple: 緯度（北緯）,経度（東経）    
    '''
    rect_epsg = to_epsg_code(system_id)
    tr = Transformer.from_proj(rect_epsg, EPSG_CODE_LATLON)
    lat,lon  = tr.transform(x, y)
    return lat, lon

if __name__ == "__main__":
    system_id = 9
    x, y  = convert_from_LatLon(system_id,35.71, 139.74)
    print (x,y)
    lat, lon  = convert_to_LatLon(system_id,x,y)
    print (lat, lon)