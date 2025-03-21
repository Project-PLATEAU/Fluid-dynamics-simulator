/**
 * 3D地図操作周りの処理
 */

// Cesium ionの読み込み指定
Cesium.Ion.defaultAccessToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI5N2UyMjcwOS00MDY1LTQxYjEtYjZjMy00YTU0ZTg5MmViYWQiLCJpZCI6ODAzMDYsImlhdCI6MTY0Mjc0ODI2MX0.dkwAL1CcljUV7NA7fDbhXXnmyZQU_c-G5zRx8PtEcxE";

// 新規建物作成(1っの建物作成)に使用PolygonIDとPolylineID
const NEW_BUILDING_POLYGON_ID = "create_new_building_polygon_id";
const NEW_BUILDING_POLYLINE_ID = "create_new_building_polyline_id_";
// クリックするごとに作成するpolylineにIDを設定する。
let createPolylineId = 0;

// 建物を非表示にするモード
const MODE_HIDE_BUILDING = 1;
// 建物を表示にするモード
const MODE_SHOW_BUILDING = 2;

/**
* 3D地図描画
*
* @param string cesiumContainer 地図表示用のid要素
* @param array czmlFiles 特定の解析対象地域に紐づいていたCZMLファイル配列
* @param Date viewerLockCurrentTime ビューアーに設定する時間
* @param array viewerCamera ビューアーにカメラを設定する情報
*
* @return ビューアー
*/
function show3DMap(cesiumContainer, czmlFiles, viewerLockCurrentTime="", viewerCamera = null) {

    // Terrainの指定（EGM96、国土数値情報5m標高から生成した全国の地形モデル、5m標高データが無い場所は10m標高で補完している）
    let viewer = new Cesium.Viewer(cesiumContainer, {
        terrainProvider: new Cesium.CesiumTerrainProvider({
            url: Cesium.IonResource.fromAssetId(770371),
        })
    });

    // 地形データを使用する場合、深度テストを有効にする
    viewer.scene.globe.depthTestAgainstTerrain = true;

    viewer.scene.globe.enableLighting = true;
    viewer.scene.globe.lightingUpdateOnEveryRender = true;
    // CesiumJSでは、Cesium.Sunオブジェクトを使って太陽の光をシミュレートできますが、特定の角度を直接指定するメソッドは提供されていません
    // scene.globe.lightingFixedFrameプロパティを使用して、固定フレーム内で光の位置を指定します。
    viewer.scene.globe.lightingFixedFrame = true;
    // viewer.scene.globe.lightingDirection = lightDirection

    // 日陰の有効化
    viewer.scene.shadowMap.enabled = true;
    viewer.scene.shadowMap.size = 4096;
    viewer.scene.shadowMap.softShadows = true;
    viewer.scene.shadowMap.darkness = 0.3;

    // 「シミュレーションモデルテーブル.日付」、「シミュレーションモデルテーブル.時間帯」から取得した日付時刻で3D地図を表示するようにする
    if (viewerLockCurrentTime) {
        viewer.clock.currentTime = Cesium.JulianDate.fromDate(viewerLockCurrentTime);
    }

    // CZMLデータソースの読み込みと追加
    var promiseCzmlDataSources = [];
    czmlFiles.forEach(function (czmlFile) {

        // 念のため、dataSourcesに追加する前に、czmlファイルがnullかどうかチェックする。
        if (czmlFile) {
            let _czmlFile = czmlFile + "?date=" + new Date().getTime() // ファイルキャッシュ対応
            var promiseCzmlDataSource = Cesium.CzmlDataSource.load(_czmlFile).then(function (dataSource) {
                viewer.dataSources.add(dataSource);
            });
            promiseCzmlDataSources.push(promiseCzmlDataSource);
        }
    });

    // すべてのデータソースが読み込まれたらズームイン
    if (promiseCzmlDataSources.length > 0) {
        Promise.all(promiseCzmlDataSources).then(function () {

            // ズームイン設定
            if (viewerCamera !== null) {

                // 表示モードを切り替え前の状態（方向、ピッチ、ポジション）を取得
                const initHeading = parseFloat(viewerCamera["map_current_heading"]);
                const initPitch = parseFloat(viewerCamera["map_current_pitch"]);
                const initRoll = parseFloat(viewerCamera["map_current_roll"]);
                const initPositionX = parseFloat(viewerCamera["map_current_position_x"]);
                const initPositionY = parseFloat(viewerCamera["map_current_position_y"]);
                const initPositionZ = parseFloat(viewerCamera["map_current_position_z"]);

                // 表示モードを切り替え前の状態（方向、ピッチ、ポジション）を地図初期表示にする。
                setCamera(viewer, initHeading, initPitch, initRoll, initPositionX, initPositionY, initPositionZ);
            } else {
                var entities = [];
                viewer.dataSources._dataSources.forEach(function (dataSource) {
                    entities = entities.concat(dataSource.entities.values);
                });
                viewer.zoomTo(entities);
            }
        });
    }

    return viewer;
}

/**
 * カメラ設定
 *
 * @param Cesium.Viewer viewer ビューアー
 * @param number heading カメラコントローラ用の方向(東)
 * @param number pitch カメラコントローラ用の方向(北)
 * @param number roll カメラコントローラ用の方向(上)
 * @param number positionX カメラの位置(経度)
 * @param number positionY カメラの位置(緯度)
 * @param number positionZ カメラの位置(標高)
 *
 * @return ビューアー
 */
function setCamera(viewer, heading, pitch, roll, positionX, positionY, positionZ) {
    if (heading && pitch && roll && positionX && positionY && positionZ) {
        const position = new Cesium.Cartesian3(positionX, positionY, positionZ);
        viewer.camera.setView({
            destination: position,
            orientation: {
                heading: heading,
                pitch: pitch,
                roll: roll
            }
        });
    }
    return viewer;
}

/**
 * 3D地図をリセットする。
 *
 * @param Cesium.Viewer viewer ビューアー
 *
 * @return
 */
function reset3DMap(viewer) {
    // 完全にビューアーを破棄する。
    if (viewer) {
        viewer.destroy();
    }
}

/**
 * ライン引く
 *
 * @param Cesium.Viewer viewer ビューアー
 * @param array positions 指定した点の座標と標高
 *
 * @return
 */
function createLine(viewer, positions) {

    // polylineIdを定義
    let _id = NEW_BUILDING_POLYLINE_ID + createPolylineId;

    viewer.entities.add({
        id: _id,
        polyline: {
            positions: Cesium.Cartesian3.fromDegreesArrayHeights(positions),
            width: 3,
            material: Cesium.Color.RED
        }
    });

    // 作成するpolylineIDを増加
    createPolylineId += 1;

    return viewer;
}


/**
 * 地面をクリックするイベントの設定(建物作成用)
 *
 * @param Cesium.Viewer viewer ビューアー
 * @param array hierarchy 建物描画に必要な座標
 * @param array positions 建物描画に必要な座標
 *
 * @return ハンドル
 */
function mapClickEventSetting(viewer, hierarchy, positions) {
    // 新しいhandlerを作成して、viewerに設定
    let handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);

    // クリックイベントを設定
    handler.setInputAction(function (click) {
        // クリックしたスクリーン座標を取得
        let cartesian = viewer.scene.pickPosition(click.position);

        if (cartesian) {
            // 地理座標（緯度・経度）に変換
            let cartographic = Cesium.Cartographic.fromCartesian(cartesian);

            // 経度
            let longitude = Cesium.Math.toDegrees(cartographic.longitude);
            // 緯度
            let latitude = Cesium.Math.toDegrees(cartographic.latitude);
            // 標高データの取得
            // pickPositionを使用している時、そのまま標高が設定される
            // let elevation = Cesium.Math.toDegrees(cartographic.height);
            let elevation = cartographic.height;

            let _hierarchy = Cesium.Cartesian3.fromDegrees(longitude, latitude, elevation)
            hierarchy.push(_hierarchy)
            positions.push(longitude, latitude, elevation);

            if (hierarchy.length >= 2) {  // 最初の2点でラインを作成
                createLine(viewer, positions);
            }
        }
    }, Cesium.ScreenSpaceEventType.LEFT_CLICK);

    return handler;
}

/**
 * 建物をクリックするイベントの設定（建物削除用）
 *
 * @param Cesium.Viewer viewer ビューアー
 * @param array selectedEntities 選択したポリゴンのエンティティ)の配列
 *  データ例：
 *    [
 *      0:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *      1:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *    ]
 *
 * @return ハンドル
 */
function buildingClickEventSetting(viewer, selectedEntities = []) {
    // 新しいhandlerを作成して、viewerに設定
    let handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);
    // 選択された建物のID（※重複を取り除くため、一時的に使用する）
    let selectedBuildings = [];

    // クリックイベントを設定
    handler.setInputAction(function (click) {
        let pickedFeature = viewer.scene.pick(click.position);
        if (Cesium.defined(pickedFeature)) {
            // 建物のオブジェクト
            let entity = pickedFeature.id;
            // エンティティがポリゴンを持っている場合に限る(CZMLファイルの構造次第で、処理修正が必要)
            if (entity.polygon && entity.polygon.material) {

                // エンティティが既に選択されているかどうかを確認
                const selectedIndex = selectedBuildings.indexOf(entity.id);

                if (selectedIndex === -1) {
                    // エンティティが選択されていない場合、配列に追加し、色を赤に変更
                    selectedBuildings.push(entity.id);

                    // 元の色を保存して、エンティティ情報を追加
                    let _result = {
                        "original_color": entity.polygon.material.color.getValue(Cesium.JulianDate.now()), // 元の色
                        "entity": entity // 選択したエンティティ
                    };
                    selectedEntities.push(_result);

                    // 色を赤に変更
                    entity.polygon.material = new Cesium.ColorMaterialProperty(Cesium.Color.RED);

                } else {
                    // エンティティが選択されている場合、配列から除去し、元の色に戻す
                    // 配列 selectedEntities からも削除する
                    const originalEntityIndex = selectedEntities.findIndex(e => e.entity.id === entity.id);
                    if (originalEntityIndex !== -1) {
                        entity.polygon.material = new Cesium.ColorMaterialProperty(selectedEntities[originalEntityIndex].original_color);
                    }

                    selectedBuildings.splice(selectedIndex, 1);
                    selectedEntities.splice(originalEntityIndex, 1);  // 元の配列から要素を削除
                }
            }
        }
    }, Cesium.ScreenSpaceEventType.LEFT_CLICK);

    return handler;
}


/**
 * ポリゴンのエンティティに色を設定する。
 *
 * @param array entities ポリゴンのエンティティ
 *  データ例：
 *    [
 *      0:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *      1:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *    ]
 * @param int mode 建物を非表示にするかどうかのモード
 * @param Cesium.Color color 色
 *
 * @return
 */
function setPolygonColor(entities, mode = MODE_HIDE_BUILDING, color = Cesium.Color.TRANSPARENT) {
    // エンティティがポリゴンを持っている場合に限る(CZMLファイルの構造次第で、処理修正が必要)
    entities.forEach(entity => {
        if (mode == MODE_HIDE_BUILDING) {
            entity['entity'].polygon.material = new Cesium.ColorMaterialProperty(color);
        } else {
            entity['entity'].polygon.material = new Cesium.ColorMaterialProperty(Cesium.Color.RED);
        }
    });
}

/**
 * ポリゴンのエンティティの色と非表示をリセットに色を設定する。
 *
 * @param array entities ポリゴンのエンティティ
 *  データ例：
 *    [
 *      0:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *      1:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *    ]
 *
 * @return
 */
function resetPolygonColor(entities) {
    // エンティティがポリゴンを持っている場合に限る(CZMLファイルの構造次第で、処理修正が必要)
    entities.forEach(entity => {
        entity['entity'].polygon.material = new Cesium.ColorMaterialProperty(entity['original_color']);
    });
}

/**
 * ハンダーよりアクションを削除する。
 *
 * @param Cesium.ScreenSpaceEventHandler hander
 *
 * @return
 */
function removeActionFromHander(hander) {
    // アクションを削除する
    hander.removeInputAction(Cesium.ScreenSpaceEventType.LEFT_CLICK);
}

/**
 * 建物描画
 *
 * @param Cesium.Viewer viewer ビューアー
 * @param Cesium.Cartesian3 hierarchy 建物描画に必要な座標
 * @param number extrudedHeight 建物の高さ
 *
 * @return ビューアー
 */
function drawBuilding(viewer, hierarchy, extrudedHeight) {
    // 地形の標高を考慮してextrudedHeightを調整
    let baseHeight = Cesium.Cartographic.fromCartesian(hierarchy[0]).height;

    entity = viewer.entities.add({
        name: "新規建物",
        description: "ここでは新規建物を作成します。",
        id: NEW_BUILDING_POLYGON_ID,
        polygon: {
            hierarchy: hierarchy,
            extrudedHeight: baseHeight + extrudedHeight,
            material: Cesium.Color.RED.withAlpha(0.5),
            outline: true,
            outlineColor: Cesium.Color.RED,
            // outlineWidth: 3 効かない
        }
    });

    return viewer;
}

/**
 * 追加した建物をリセットする。
 *
 * @param Cesium.Viewer viewer ビューアー
 *
 * @return
 */
function clearBuilding(viewer) {
    // 建物アウトラインを削除する。
    let polygonEntity = viewer.entities.getById(NEW_BUILDING_POLYGON_ID);
    if (polygonEntity) {
        viewer.entities.remove(polygonEntity);
    }

    // 建物作成に引いた線を削除する。
    for (let i = 0; i < createPolylineId; i++) {
        let _id = NEW_BUILDING_POLYLINE_ID + i;
        let polylineEntity = viewer.entities.getById(_id);
        if (polylineEntity) {
            viewer.entities.remove(polylineEntity);
        }
    }

    // 作成するpolylineIDをリセット
    createPolylineId = 0;
}
