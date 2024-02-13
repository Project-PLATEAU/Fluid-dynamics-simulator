@extends('layouts.app')

@section('title', '3D都市モデル閲覧')

@section('css')
<link href="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
@endsection

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_CITY }}</span>
@endsection

@section('city-model-display-area')
<span>{{ $cityModel->identification_name }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container-fluid">

    <input type="hidden" id="tilesUrl" name="tilesUrl" value="{{ $cityModel->url }}">

    <div class="3d-view-area" id="cesiumContainer">
        {{-- 3D都市モデル 3d地図描画 --}}
    </div>

    <div class="button-area mt-3">
        <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('city_model.index') }}'">戻る</button>
    </div>

</div>
@endsection


{{-- 個別js --}}
@section('js')
    <script src="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Cesium.js"></script>
    <script>
        $(function(){

            $('#cesiumContainer').outerHeight($(window).height() - 150);
            $(window).resize(function () {
                $('#cesiumContainer').outerHeight($(window).height() - 150);
            });

            // Cesium ionの読み込み指定
            Cesium.Ion.defaultAccessToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI5N2UyMjcwOS00MDY1LTQxYjEtYjZjMy00YTU0ZTg5MmViYWQiLCJpZCI6ODAzMDYsImlhdCI6MTY0Mjc0ODI2MX0.dkwAL1CcljUV7NA7fDbhXXnmyZQU_c-G5zRx8PtEcxE";

            // Terrainの指定（EGM96、国土数値情報5m標高から生成した全国の地形モデル、5m標高データが無い場所は10m標高で補完している）
            var viewer = new Cesium.Viewer("cesiumContainer", {
                terrainProvider: new Cesium.CesiumTerrainProvider({
                    url: Cesium.IonResource.fromAssetId(770371),
                })
            });

            const tilesUrlInput = $('#tilesUrl').val();

            // 東京都千代田区の建物データ（3D Tiles）
            let tileset = viewer.scene.primitives.add(new Cesium.Cesium3DTileset({
                url: tilesUrlInput
            }));

            // カメラの初期位置の指定
            viewer.zoomTo(tileset, new Cesium.HeadingPitchRange(0, -0.3, 0));
        });
    </script>
@endsection
