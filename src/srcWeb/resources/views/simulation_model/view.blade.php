@extends('layouts.app')

@section('title', 'シミュレーション結果閲覧')

@section('css')
<link href="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
@endsection

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_SIMULATION }}</span>
@endsection

@section('city-model-display-area')
<span>{{ $simulationModel->identification_name }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container-fluid">

    <input type="hidden" id="tilesUrl" name="tilesUrl" value="{{ $simulationModel->city_model->url }}">

    {{-- czmlファイル --}}
    @php $visualizationFilePath = $visualization ? App\Utils\FileUtil::referenceStorageFile($visualization->visualization_file) : "" @endphp
    <input type="hidden" id="visualizationFile" name="visualizationFile" value="{{ $visualizationFilePath }}">

    @php
        $solarAltitudeDatetime = App\Utils\DatetimeUtil::changeFormat($simulationModel->solar_altitude_date, App\Utils\DatetimeUtil::DATE_FORMAT) . " " . $simulationModel->solar_altitude_time . ":00:00";
    @endphp
    <input type="hidden" id="solarAltitudeDatetime" name="solarAltitudeDatetime" value="{{ $solarAltitudeDatetime }}">

    {{-- シミュレーションモデル情報のエリア --}}
    {{-- 仕様変更により、テキスト情報を非表示にする。 --}}
    <div class="row pl-2 px-2 d-none" id ="sm-info-area">
        <div class="col-sm-6">
            <div class="d-flex flex-column">
                <div class="row">
                    <div class="col-sm-5">
                        <label>3D都市モデル</label>
                    </div>
                    <div class="col-sm-7">
                        <span>{{$simulationModel->city_model->identification_name}}</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <label>対象地域</label>
                    </div>
                    <div class="col-sm-7">
                        <span>{{$simulationModel->region->region_name}}</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <label>モデル識別名</label>
                    </div>
                    <div class="col-sm-7">
                        <span>{{$simulationModel->identification_name}}</span>
                    </div>
                </div>

            </div>
        </div>
        <div class="col-sm-6">
            <div class="d-flex flex-column">
                <div class="row">
                    <div class="col-sm-5">
                        <label>登録ユーザ</label>
                    </div>
                    <div class="col-sm-7">
                        <span>{{$simulationModel->user_account->display_name}}</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <label>シミュレーション開始日時</label>
                    </div>
                    <div class="col-sm-7">
                        <span>{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->last_sim_start_datetime)}}</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <label>シミュレーション完了日時</label>
                    </div>
                    <div class="col-sm-7">
                        <span>{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->last_sim_end_datetime)}}</span>
                    </div>
                </div>

            </div>
        </div>

    </div>
    {{-- シミュレーションモデル結果表示モード切替のエリア --}}
    <div class="row pl-2" id ="sm-result-mode-display-area">
        <form id="frmSmResultMode" method="" action="">
            <div class="d-flex flex-row">
                {{-- 風況ラジオ --}}
                <div class="form-check mx-3">
                    <input class="form-check-input" type="radio" name="visualization_type" id="visualization_type_wind" data-href="{{ route('simulation_model.change_mode', ['id' => $simulationModel->simulation_model_id]) }}" value="{{ App\Commons\Constants::VISUALIZATION_TYPE_WINDY }}" @if($visualizationType == App\Commons\Constants::VISUALIZATION_TYPE_WINDY) checked @endif onchange="changeMode(this)">
                    <label class="form-check-label" for="visualization_type_wind">風況</label>
                </div>
                {{-- 中空温度ラジオ --}}
                <div class="form-check mx-3">
                    <input class="form-check-input" type="radio" name="visualization_type" id="visualization_type_temperature" data-href="{{ route('simulation_model.change_mode', ['id' => $simulationModel->simulation_model_id]) }}" value="{{ App\Commons\Constants::VISUALIZATION_TYPE_TEMP }}" @if($visualizationType == App\Commons\Constants::VISUALIZATION_TYPE_TEMP) checked @endif onchange="changeMode(this)">
                    <label class="form-check-label" for="visualization_type_temperature">中空温度</label>
                </div>
                {{-- 暑さ指数ラジオ --}}
                <div class="form-check mx-3">
                    <input class="form-check-input" type="radio" name="visualization_type" id="visualization_type_heat_index" data-href="{{ route('simulation_model.change_mode', ['id' => $simulationModel->simulation_model_id]) }}" value="{{ App\Commons\Constants::VISUALIZATION_TYPE_HEAT_INDEX }}" @if($visualizationType == App\Commons\Constants::VISUALIZATION_TYPE_HEAT_INDEX) checked @endif onchange="changeMode(this)">
                    <label class="form-check-label" for="visualization_type_heat_index">暑さ指数</label>
                </div>
                {{-- 高さプルダウン(※「暑さ指数」が選択された場合、非表示する。) --}}
                @php
                    $dNoneHeight = ($visualizationType == App\Commons\Constants::VISUALIZATION_TYPE_HEAT_INDEX) ? 'd-none' : "";
                @endphp
                <div class="form-check form-group {{ $dNoneHeight }}" id="height-area">
                    <div class="d-flex flex-row">
                        <div class="mx-3">
                            <label class="form-check-label" for="mode_height">高さ</label>
                        </div>
                        <div class="mx-3">
                            <select class="form-select mx-1" id="height" name="height" data-href="{{ route('simulation_model.change_mode', ['id' => $simulationModel->simulation_model_id]) }}" onchange="changeMode(this, 2)">
                                @foreach ($heightList as $height)
                                    <option value="{{ $height->height_id }}" @if($heightId == $height->height_id) selected @endif>{{ $height->height }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-check-label" for="mode_height">(m)</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 表示モードを切替直前の状態を維持するための情報 --}}
            <input type="hidden" name="map_current_heading" id="map_current_heading" value="{{ isset($mapCurrentHeading) ? $mapCurrentHeading : null }}">
            <input type="hidden" name="map_current_pitch" id="map_current_pitch" value="{{ isset($mapCurrentPitch) ? $mapCurrentPitch : null }}">
            <input type="hidden" name="map_current_roll" id="map_current_roll" value="{{ isset($mapCurrentRoll) ? $mapCurrentRoll : null }}">
            <input type="hidden" name="map_current_position_x" id="map_current_position_x" value="{{ isset($mapCurrentPositionX) ? $mapCurrentPositionX : null }}">
            <input type="hidden" name="map_current_position_y" id="map_current_position_y" value="{{ isset($mapCurrentPositionY) ? $mapCurrentPositionY : null }}">
            <input type="hidden" name="map_current_position_z" id="map_current_position_z" value="{{ isset($mapCurrentPositionZ) ? $mapCurrentPositionZ : null}}">

        </form>
    </div>
    {{-- シミュレーションモデル結果の描画 --}}
    <div class="3d-view-area mt-2" id="cesiumContainer"></div>

    {{-- ボタンの表示エリア --}}
    <div class="row">
        <div class="col-sm-8">
            <div class="button-area mt-4">
                <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('simulation_model.index') }}'">戻る</button>
                <button type="button" class="btn btn-outline-secondary" data-href="{{ route('simulation_model.download', ['id' => $simulationModel->simulation_model_id]) }}" onclick="changeMode(this, 3)">ダウンロード</button>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="float-end mt-4">
                <div class="d-flex flex-column" id="usage-guide-area">
                    {{-- 凡例表示 --}}
                    @switch($visualizationType)
                        @case(App\Commons\Constants::VISUALIZATION_TYPE_WINDY)
                            <img class="px-2" src="{{ asset('/image/usage_guide/wind_speed.png') }}?ver={{ config('const.ver_image') }}" height="40px" width="250px" alt="guide">
                            @break
                        @case(App\Commons\Constants::VISUALIZATION_TYPE_TEMP)
                            <img class="px-2" src="{{ asset('/image/usage_guide/temperature.png') }}?ver={{ config('const.ver_image') }}" height="40px" width="250px" alt="guide">
                            @break
                        @case(App\Commons\Constants::VISUALIZATION_TYPE_HEAT_INDEX)
                            <img class="px-2" src="{{ asset('/image/usage_guide/WBGT.png') }}?ver={{ config('const.ver_image') }}" height="40px" width="250px" alt="guide">
                            @break
                        @default
                            <img class="px-2" src="{{ asset('/image/usage_guide/wind_speed.png') }}?ver={{ config('const.ver_image') }}" height="40px" width="250px" alt="guide">
                            @break
                    @endswitch

                    <div class="d-flex flex-row">
                        <div class="w-50">{{ $visualization ? $visualization->legend_label_higher : "" }}</div>
                        <div class="w-50">
                            <div class="float-end">{{ $visualization ? $visualization->legend_label_lower : "" }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
@endsection


{{-- 個別js --}}
@section('js')
    <script src="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Cesium.js"></script>
    <script>

        let viewer = null;

        $(function(){

            $('#cesiumContainer').outerHeight($(window).height() - 230);
            $(window).resize(function () {
                $('#cesiumContainer').outerHeight($(window).height() - 230);
            });

            // Cesium ionの読み込み指定
            Cesium.Ion.defaultAccessToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI5N2UyMjcwOS00MDY1LTQxYjEtYjZjMy00YTU0ZTg5MmViYWQiLCJpZCI6ODAzMDYsImlhdCI6MTY0Mjc0ODI2MX0.dkwAL1CcljUV7NA7fDbhXXnmyZQU_c-G5zRx8PtEcxE";

            // Terrainの指定（EGM96、国土数値情報5m標高から生成した全国の地形モデル、5m標高データが無い場所は10m標高で補完している）
            viewer = new Cesium.Viewer("cesiumContainer", {
                terrainProvider: new Cesium.CesiumTerrainProvider({
                    url: Cesium.IonResource.fromAssetId(770371),
                })
            });

            const tilesUrlInput = $('#tilesUrl').val();

            let tileset = viewer.scene.primitives.add(new Cesium.Cesium3DTileset({
                url: tilesUrlInput
            }));

            const visualizationFile = $('#visualizationFile').val() + "?date=" + new Date().getTime();;
            var dataSourcePromise = Cesium.CzmlDataSource.load(visualizationFile);
            viewer.dataSources.add(dataSourcePromise);

            viewer.scene.globe.enableLighting = true;
            viewer.scene.globe.lightingUpdateOnEveryRender = true;
            // CesiumJSでは、Cesium.Sunオブジェクトを使って太陽の光をシミュレートできますが、特定の角度を直接指定するメソッドは提供されていません
            // scene.globe.lightingFixedFrameプロパティを使用して、固定フレーム内で光の位置を指定します。
            viewer.scene.globe.lightingFixedFrame = true;
            // viewer.scene.globe.lightingDirection = lightDirection;

            // 日陰の有効化
            viewer.scene.shadowMap.enabled = true;
            viewer.scene.shadowMap.size = 4096;
            viewer.scene.shadowMap.softShadows = true;
            viewer.scene.shadowMap.darkness = 0.3;

            // 「シミュレーションモデルテーブル.日付」、「シミュレーションモデルテーブル.時間帯」から取得した日付時刻で3D地図を表示するようにする
            let solarAltitudeDatetime = new Date($("#solarAltitudeDatetime").val());
            viewer.clock.currentTime = Cesium.JulianDate.fromDate(solarAltitudeDatetime);

            // 表示モードを切り替え前の状態（方向、ピッチ、ポジション）を取得
            const initHeading = parseFloat($("#map_current_heading").val());
            const initPitch = parseFloat($("#map_current_pitch").val());
            const initRoll = parseFloat($("#map_current_roll").val());
            const initPositionX = parseFloat($("#map_current_position_x").val());
            const initPositionY = parseFloat($("#map_current_position_y").val());
            const initPositionZ = parseFloat($("#map_current_position_z").val());

            // 表示モードを切り替え前の状態（方向、ピッチ、ポジション）を地図初期表示にする。
            if (initHeading && initPitch && initRoll && initPositionX && initPositionY && initPositionZ) {
                const position = new Cesium.Cartesian3(initPositionX, initPositionY, initPositionZ);
                viewer.camera.setView({
                    destination: position,
                    orientation: {
                        heading: initHeading,
                        pitch: initPitch,
                        roll: initRoll
                    }
                });
            } else {
                viewer.zoomTo(tileset, new Cesium.HeadingPitchRange(0, -0.3, 0));
            }
        });

        /**
         * 表示モード切替(風況、温度、高さ) とダウンロードボタン押下後
         * @param mixed target
         * @param int html_type ラジオボタン：1; セレクトボックス: 2 ; ダウンロードボタン: 3
         *
         * @return
         */
        function changeMode(target, html_type = 1)
        {
            // 現在のカメラの方向、ピッチ、ポジションを維持
            $("#map_current_heading").val(viewer.camera.heading);
            $("#map_current_pitch").val(viewer.camera.pitch);
            $("#map_current_roll").val(viewer.camera.roll);
            $("#map_current_position_x").val(viewer.camera.position.clone().x);
            $("#map_current_position_y").val(viewer.camera.position.clone().y);
            $("#map_current_position_z").val(viewer.camera.position.clone().z);

            const smResultModeForm = "#frmSmResultMode";

            const $formAction = $(target).data('href');

            // フォームサブミット
            if ($formAction !== undefined)
            {
                submitFrm(smResultModeForm, $formAction);
            }
        }

        /**
         *
         * フォームサブミット
         * @param mixed frmId フォームID
         * @param mixed action フォームのアクション
         *
         * @return
         */
        function submitFrm(frmId, action, method = 'GET')
        {
            $(frmId).attr('action', action);
            $(frmId).attr('method', method);
            $(frmId).submit();
        }
    </script>
@endsection
