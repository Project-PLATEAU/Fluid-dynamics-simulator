/**
 * 2d地図操作周りの処理
 */

/**
 * 地図の表示範囲を取得
 * @param number southern_latitude 南端緯度
 * @param number northern_latitude 北端緯度
 * @param number western_longitude 西端経度
 * @param number east_longitude    東端経度
 *
 * @return
 */
function getBounds(southern_latitude, northern_latitude, western_longitude, east_longitude)
{
    let _bounds = new L.LatLngBounds(
        new L.LatLng(northern_latitude, east_longitude), // 矩形の右上隅（北端緯度と東端経度）
        new L.LatLng(southern_latitude, western_longitude) // 矩形の左下隅（南端緯度と西端経度）
    );
    return _bounds;
}

/**
 * 2d地図を描画
 * @param number southern_latitude 南端緯度
 * @param number northern_latitude 北端緯度
 * @param number western_longitude 西端経度
 * @param number east_longitude    東端経度
 *
 * @return
 */
function iniMap(southern_latitude, northern_latitude, western_longitude, east_longitude)
{

    // 地図の表示範囲を取得
    let _bounds = getBounds(southern_latitude, northern_latitude, western_longitude, east_longitude);

    let map = L.map('map', {
        center: [northern_latitude, east_longitude], // 地図の中心に設定する場所（北端緯度と東端経度）
        zoom: 6,
        maxBounds: _bounds //地図の表示範囲を設定
    });

    // ★2D地図描画に必要
    // 地理院タイル
    L.tileLayer('https://cyberjapandata.gsi.go.jp/xyz/std/{z}/{x}/{y}.png', {
        attribution: "<a href='https://maps.gsi.go.jp/development/ichiran.html' target='_blank'>地理院タイル</a>"
    }).addTo(map);

    return map;
}

/**
 * 地図上に長方形を描画
 * @param mixed map           2d地図
 * @param mixed bounds        表示範囲
 * @param Boolean fit_bounds  地図を四角形の境界にズームするかどうか
 * @param String color        方形の背景色
 * @param Boolean editable    長方形のサイズ変更許可用のフラグ
 *
 * @return
 */
function drawRectangle(map, bounds, fit_bounds = false, color = "", editable = false)
{
    let option = { weight: 1, editable: editable };
    if (color) {
        option.color = color;
    }

    // 長方形を描画
    let _rectangle = new L.rectangle(bounds, option).addTo(map);

    // 地図を四角形の境界にズームします
    if (fit_bounds) {
        map.fitBounds(bounds);
    }

    return _rectangle;
}

/**
 *  haversine式で緯度と経度の2点間の距離を計算
 * @param Number latitude1 1点目の緯度
 * @param Number longitude1 1点目の経度
 * @param Number latitude2 2点目の緯度
 * @param Number longitude2 2点目の経度
 * @param string unit 距離単位（※デフォルト：メートル）
 *
 * @return 距離
 */
function haversineDistance(latitude1, longitude1, latitude2, longitude2, unit = 'meters')
{
    let theta = longitude1 - longitude2;

    // 地球の周囲の円周をマイル単位で表すためです。
    // 経度の角度は度で表され、1度は約60マイルの距離に相当します。
    // そのため、経度の差を1度単位で表すと、地球の周囲の円周に対する距離として
    // 60マイルが必要になります。また、1.1515は、マイルとキロメートルの単位換算係数です
    let distance = 60 * 1.1515 * (180 / Math.PI) * Math.acos(
        Math.sin(latitude1 * (Math.PI / 180)) * Math.sin(latitude2 * (Math.PI / 180)) +
        Math.cos(latitude1 * (Math.PI / 180)) * Math.cos(latitude2 * (Math.PI / 180)) * Math.cos(theta * (Math.PI / 180))
    );

    if (unit == 'miles') {
        return Math.round(distance.toFixed(1)); // 小数点第１位を四捨五入して整数値での表示
    } else if (unit == 'kilometers') {
        // 1マイル = 1.609344キロメートル
        return Math.round((distance * 1.609344).toFixed(1)); // 小数点第１位を四捨五入して整数値での表示
    } else if (unit == 'meters') {
        // 1マイル = 1609.34メートル
        return Math.round((distance * 1609.34).toFixed(1)); // 小数点第１位を四捨五入して整数値での表示
    }
}

