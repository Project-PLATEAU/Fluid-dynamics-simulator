/**
 * 3D地図操作周りの処理
 */

// Cesium ionの読み込み指定
Cesium.Ion.defaultAccessToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI5N2UyMjcwOS00MDY1LTQxYjEtYjZjMy00YTU0ZTg5MmViYWQiLCJpZCI6ODAzMDYsImlhdCI6MTY0Mjc0ODI2MX0.dkwAL1CcljUV7NA7fDbhXXnmyZQU_c-G5zRx8PtEcxE";

// 新規建物や植被作成(1っの建物作成)に使用PolygonIDとPolylineID
const NEW_ENTITY_POLYGON_ID = "polygon_id";
const NEW_ENTITY_POLYLINE_ID = "polyline_id_";
// クリックするごとに作成するpolylineにIDを設定する。
let createPolylineId = 0;

// モデルを非表示にするモード
const HIDE_MODEL = 1;
// モデルを表示にするモード
const SHOW_MODEL = 2;

// 建物・樹木の追加・削除モード
let mapActivityMode = 0;
const ADDNEW_MODEL_BUILDING = 2;    // 新規建物作成
const ADDNEW_MODEL_PLANT_COVER = 3; // 植被作成
const ADDNEW_MODEL_TREE = 4;        // 単独木作成

// 単独木作成（複数あり）に使用するCylinderID
const NEW_ENTITY_CYLINDER_ID = "cylinder_id_";
const NEW_ENTITY_ELLIPSE_ID = "ellipse_id_";
// クリックするごとに作成するEllipseにIDを設定する。(小さな点描画のため)
let createEllipseId = 0;
//単独木は直線に追加するフラグ(true:追加する; false: 追加しない)
let createTreeOnLineFlg = false;

/**
* 3D地図描画
*
* @param {string} cesiumContainer 地図表示用のid要素
* @param {Array} czmlFiles 特定の解析対象地域に紐づいていたCZMLファイル配列
* @param {Date} viewerLockCurrentTime ビューアーに設定する時間
* @param {Array} viewerCamera ビューアーにカメラを設定する情報
*
* @returns {Cesium.Viewer} ビューアー
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
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {number} heading カメラコントローラ用の方向(東)
 * @param {number} pitch カメラコントローラ用の方向(北)
 * @param {number} roll カメラコントローラ用の方向(上)
 * @param {number} positionX カメラの位置(経度)
 * @param {number} positionY カメラの位置(緯度)
 * @param {number} positionZ カメラの位置(標高)
 *
 * @returns ビューアー
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
 * @param {Cesium.Viewer} viewer ビューアー
 *
 * @returns
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
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {Array.<number>} positions 指定した点の座標と標高
 *
 * @returns
 */
function createLine(viewer, positions) {

    // polylineIdを定義
    let _id = NEW_ENTITY_POLYLINE_ID + createPolylineId;

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
 * 円の描画
 *
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {Cesium.PositionProperty} cartesian 指定した点の座標と標高
 *
 * @returns
 */
function createEllipse(viewer, cartesian) {
    // ellipseIDを定義
    let _id = NEW_ENTITY_ELLIPSE_ID + createEllipseId;

    viewer.entities.add({
        id: _id,
        position: cartesian,
        ellipse: {
            semiMinorAxis: 2, // 半長軸の設定
            semiMajorAxis: 2, // 半短軸の設定
            material: Cesium.Color.RED,
        }
    });

    // 作成するellipseIDを増加
    createEllipseId += 1;

    return viewer;
}


/**
 * 地面をクリックするイベントの設定(建物・植被：単独木作成用)
 *
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {Array.<Cesium.Cartographic>} hierarchy 建物・植被：単独木の描画に必要な座標
 * @param {Array.<number>} positions 建物・植被：単独木の描画に必要な座標
 *
 * @returns ハンドル
 */
function mapClickEventSetting(viewer, hierarchy, positions) {
    // 新しいhandlerを作成して、viewerに設定
    let handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);

    // クリックイベントを設定
    handler.setInputAction(function (click) {
        // クリックしたスクリーン座標を取得
        let cartesian = viewer.scene.pickPosition(click.position);

        if (Cesium.defined(cartesian)) {
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

            let _hierarchy = null;
            if (mapActivityMode === ADDNEW_MODEL_TREE) {
                // 単独木の場合
                let canopyHeight = Number($("#canopy_height").val()); // 樹高
                _hierarchy = Cesium.Cartesian3.fromDegrees(longitude, latitude, elevation + (canopyHeight / 2));
            } else {
                _hierarchy = Cesium.Cartesian3.fromDegrees(longitude, latitude, elevation);
            }

            // 単独木 + 直線モードの場合：2点に制限し、線と円を描画
            if (mapActivityMode === ADDNEW_MODEL_TREE && createTreeOnLineFlg) {
                if (hierarchy.length < 2) {
                    // hierarchy.push(_hierarchy);
                    hierarchy.push(cartographic);
                    positions.push(longitude, latitude, elevation);
                    createEllipse(viewer, cartesian); // プレビュー用の円

                    if (hierarchy.length === 2) {
                        createLine(viewer, positions); // 線を作成
                    }
                }
            } else {
                // 通常の追加処理（制限なし）
                hierarchy.push(_hierarchy);
                positions.push(longitude, latitude, elevation);

                // 単独木モードなら円を作成
                if (mapActivityMode === ADDNEW_MODEL_TREE) {
                    createEllipse(viewer, cartesian);
                }

                // 建物・植生などで2点以上なら線を作成
                if ((mapActivityMode === ADDNEW_MODEL_BUILDING || mapActivityMode === ADDNEW_MODEL_PLANT_COVER) && hierarchy.length >= 2) {
                    createLine(viewer, positions);
                }
            }
        }
    }, Cesium.ScreenSpaceEventType.LEFT_CLICK);

    return handler;
}

/**
 * モデル（建物・植被・単独木）をクリックするイベントの設定（モデル削除用）
 *
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {Array.<Object>} selectedEntities 選択したポリゴンのエンティティ)の配列
 *  データ例：
 *    [
 *      0:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *      1:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *    ]
 *
 * @returns ハンドル
 */
function modelClickEventSetting(viewer, selectedEntities = []) {
    // 新しいhandlerを作成して、viewerに設定
    let handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);

    // クリックイベントを設定
    handler.setInputAction(function (click) {
        let pickedFeature = viewer.scene.pick(click.position);
        if (Cesium.defined(pickedFeature)) {
            // エンティティのオブジェクト
            let entity = pickedFeature.id;

            // ポリゴンまたはシリンダを持っているエンティティのみ対象
            let geometryType = null;
            if (entity.polygon && entity.polygon.material) {
                geometryType = "polygon";
            } else if (entity.cylinder && entity.cylinder.material) {
                geometryType = "cylinder";
            }

            if (geometryType) {
                // すでに選択されているかどうかをIDで判定
                const selectedIndex = selectedEntities.findIndex(e => e.entity.id === entity.id);

                if (selectedIndex === -1) {
                    // 選択されていない場合、配列に追加し、色を赤に変更
                    let originalColor;
                    if (geometryType === "polygon") {
                        originalColor = entity.polygon.material.color.getValue(Cesium.JulianDate.now());
                        entity.polygon.material = new Cesium.ColorMaterialProperty(Cesium.Color.RED);
                    } else if (geometryType === "cylinder") {
                        originalColor = entity.cylinder.material.color.getValue(Cesium.JulianDate.now());
                        entity.cylinder.material = new Cesium.ColorMaterialProperty(Cesium.Color.RED);
                    }
                    selectedEntities.push({
                        entity: entity,
                        geometryType: geometryType,
                        originalColor: originalColor
                    });
                } else {
                    // 選択済みの場合、元の色に戻して配列から削除
                    const info = selectedEntities[selectedIndex];
                    if (info.geometryType === "polygon") {
                        entity.polygon.material = new Cesium.ColorMaterialProperty(info.originalColor);
                    } else if (info.geometryType === "cylinder") {
                        entity.cylinder.material = new Cesium.ColorMaterialProperty(info.originalColor);
                    }
                    selectedEntities.splice(selectedIndex, 1);
                }
            }
        }
    }, Cesium.ScreenSpaceEventType.LEFT_CLICK);

    return handler;
}

/**
 * エンティティに色を設定する。
 *
 *  @param {Array.<Object>} entities エンティティ
 *  データ例：
 *    [
 *      0:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *      1:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *    ]
 * @param {number} mode 選択モデルを非表示にするかどうかのモード
 * @param {Cesium.Color} color 色
 *
 * @returns
 */
function setEntityColor(entities, mode = HIDE_MODEL, color = Cesium.Color.TRANSPARENT) {
    // エンティティがpolygonやcylinderを持っている場合に限る(CZMLファイルの構造次第で、処理修正が必要)
    entities.forEach(entity => {
        let geometryType = entity['geometryType'];
        if (mode == HIDE_MODEL) {
            entity['entity'][geometryType].material = new Cesium.ColorMaterialProperty(color);
        } else {
            entity['entity'][geometryType].material = new Cesium.ColorMaterialProperty(Cesium.Color.RED);
        }
    });
}

/**
 * エンティティの色と非表示をリセットに色を設定する。
 *
 * @param {Array.<Object>} entities エンティティ
 *  データ例：
 *    [
 *      0:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *      1:
 *       original_color: {red: 0.3137254901960784, green: 0.3137254901960784, blue: 0.3137254901960784, alpha: 1}, entity: {_id: xxx, _name='テストテスト',...}
 *    ]
 *
 * @returns
 */
function resetEntityColor(entities) {
    // エンティティがpolygonやcylinderを持っている場合に限る(CZMLファイルの構造次第で、処理修正が必要)
    entities.forEach(entity => {
        let geometryType = entity['geometryType'];
        entity['entity'][geometryType].material = new Cesium.ColorMaterialProperty(entity['originalColor']);
    });
}

/**
 * ハンダーよりアクションを削除する。
 *
 * @param {Cesium.ScreenSpaceEventHandler} handler
 *
 * @returns
 */
function removeActionFromHandler(handler) {
    // アクションを削除する
    handler.removeInputAction(Cesium.ScreenSpaceEventType.LEFT_CLICK);
}

/**
 * 建物描画
 *
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {Cesium.Cartesian3} hierarchy 建物描画に必要な座標
 * @param {number} extrudedHeight 建物の高さ
 *
 * @returns {Cesium.Viewer} ビューアー
 */
function drawBuilding(viewer, hierarchy, extrudedHeight) {
    // 地形の標高を考慮してextrudedHeightを調整
    let baseHeight = Cesium.Cartographic.fromCartesian(hierarchy[0]).height;

    entity = viewer.entities.add({
        name: "新規建物",
        description: "ここでは新規建物を作成します。",
        id: NEW_ENTITY_POLYGON_ID,
        polygon: {
            hierarchy: hierarchy,
            extrudedHeight: baseHeight + extrudedHeight,
            material: Cesium.Color.RED.withAlpha(0.5),
            outline: true,
            outlineColor: Cesium.Color.RED,
        }
    });

    return viewer;
}

/**
 * 円柱描画
 *
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {integer} index 何番目の円柱か
 * @param {Cesium.Cartesian3} position 円柱の描画位置
 * @param {number} height 樹高
 * @param {number} diameter 樹冠直径
 *
 * @returns
 */
function _drawCylinder(viewer, index, position, height, diameter) {
    var _id = NEW_ENTITY_CYLINDER_ID + index;
    viewer.entities.add({
        id: _id,
        name: "単独木_" + (index + 1),
        description: "ここでは単独木を作成します。",
        position: position,
        cylinder: {
            length: height,
            topRadius: diameter / 2,    // 円柱の上面の半径(直径の半分)
            bottomRadius: diameter / 2, // 円柱の底面の半径(直径の半分)
            material: Cesium.Color.RED.withAlpha(0.5),
            outline: true,
            outlineColor: Cesium.Color.RED,
            numberOfVerticalLines: 2
        }
    });
}

/**
 * 円柱描画（単独木描画）
 *
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {Cesium.Cartesian3} hierarchy 単独木描画に必要な座標(クリックした点数の座標)
 * @param {number} canopyHeight 樹高
 * @param {number} canopyDiameter 樹冠直径
 *
 * @returns
 */
function drawCylinder(viewer, hierarchy, canopyHeight, canopyDiameter) {
    // クリックした点ごとに円柱描画する。
    hierarchy.forEach((_position, index) => {
        _drawCylinder(viewer, index, _position, canopyHeight, canopyDiameter);
    });
}

/**
 * 直線上に円柱描画（単独木描画）
 *
 * @param {Cesium.Viewer} viewer ビューアー
 * @param {Cesium.Cartesian3} hierarchy 単独木描画に必要な座標(クリックした点数の座標)
 * @param {number} canopyHeight 樹高
 * @param {number} canopyDiameter 樹冠直径
 * @param {number} createTreeInterval 樹木間隔
 *
 * @returns {Promise<string>} 正常の場合、空文字列を返す。異常の場合はエラーメッセージを返す。
 * @throws {Error} 地形に基づいた標高（高さ）取得に失敗したエラー
 */
async function drawCylindersOnLine(viewer, hierarchy, canopyHeight, canopyDiameter, createTreeInterval) {
    // 2点間の距離を計算
    const start = hierarchy[0];
    const end = hierarchy[1];
    const geodesic = new Cesium.EllipsoidGeodesic(start, end);
    const totalDistance = geodesic.surfaceDistance; // メートル単位

    // 間隔ごとの点の数を計算
    const numPoints = Math.floor(totalDistance / createTreeInterval) + 1;

    // 始点と終点を含む全ての点
    const points = [];
    for (let index = 0; index <= numPoints; index++) {
        // fraction は 0（始点）〜 1（終点）までを均等に分割する値
        const fraction = index / numPoints;
        const interpolated = geodesic.interpolateUsingFraction(fraction);
        points.push(interpolated);
    }

    //「作成」に使用するグローバル変数に値をリセット
    positions = [];
    try {
        // 緯度・経度の位置に対して、地形に基づいた標高（高さ）を取得する
        const terrainData = await Cesium.sampleTerrainMostDetailed(viewer.terrainProvider, points);
        for (const [index, cartographic] of terrainData.entries()) {
            let _longitude = Cesium.Math.toDegrees(cartographic.longitude); // 経度
            let _latitude = Cesium.Math.toDegrees(cartographic.latitude);   // 緯度
            let _height = cartographic.height;                              // 標高
            positions.push(_longitude, _latitude, _height);                 //「作成」に使用するグローバル変数に値を更新

            // 直線上に円柱描画
            var position = Cesium.Cartesian3.fromRadians(cartographic.longitude, cartographic.latitude, cartographic.height + (canopyHeight / 2));
            _drawCylinder(viewer, index, position, canopyHeight, canopyDiameter);
        }
        // 線の削除
        clearLine(viewer);
        return "";
    } catch (error) {
        throw error;
    }

}

/**
 * 追加した建物や植被のpolygonをリセットする。
 *
 * @param {Cesium.Viewer} viewer ビューアー
 *
 * @returns
 */
function clearPolygon(viewer) {
    // 建物アウトラインを削除する。
    var entity = viewer.entities.getById(NEW_ENTITY_POLYGON_ID);
    if (entity) {
        viewer.entities.remove(entity);
    }

    // 追加したpolylineを削除する。
    clearLine(viewer);
}

/**
 * 追加した単独木をリセットする。
 *
 * @param {Cesium.Viewer} viewer ビューアー
 *
 * @returns
 */
function clearTree(viewer) {

    // 円柱の削除
    const matchingCylinderEntities = viewer.entities.values.filter(entity =>
        entity.id.includes(NEW_ENTITY_CYLINDER_ID)
    );
    matchingCylinderEntities.forEach(entity => {
        viewer.entities.remove(entity);
    });

    // 円の削除
    const matchingEllipseEntities = viewer.entities.values.filter(entity =>
        entity.id.includes(NEW_ENTITY_ELLIPSE_ID)
    );
    matchingEllipseEntities.forEach(entity => {
        viewer.entities.remove(entity);
    });

    // 「直線上に追加」の場合、線の削除も行う。
    if (createTreeOnLineFlg) {
        clearLine(viewer);
    }

    // 作成するellipseIDをリセット
    createEllipseId = 0;
    // 「直線上に追加」をリセット
    createTreeOnLineFlg = false;
}

/**
 * 追加した線をリセットする。
 *
 * @param {Cesium.Viewer} viewer ビューアー
 *
 * @returns
 */
function clearLine(viewer) {
    // 追加したpolylineを削除する。
    for (let i = 0; i < createPolylineId; i++) {
        let _id = NEW_ENTITY_POLYLINE_ID + i;
        let polylineEntity = viewer.entities.getById(_id);
        if (polylineEntity) {
            viewer.entities.remove(polylineEntity);
        }
    }

    // 作成するpolylineIDをリセット
    createPolylineId = 0;
}
