<label class="col-sm-1 col-form-label"></label>
<div class="col-sm-3">
    <div class="d-flex flex-column mt-2">
        <div class="">
            <input class="form-check-input disabled-all edit-disabled" type="radio" name="building_activity" id="building_activity_delete" value="1" onclick="onClickBuildingActivity(this)" disabled>
            <label class="form-check-label" for="building_activity_delete">既存建物削除</label>
        </div>
        <div class="mt-4">
            <input class="form-check-input disabled-all edit-disabled" type="radio" name="building_activity" id="building_activity_addnew" value="2" onclick="onClickBuildingActivity(this)" disabled>
            <label class="form-check-label" for="building_activity_addnew">新規建物作成</label>
        </div>

    </div>
</div>

<div class="col-sm-8">
    <div class="d-flex flex-column mt-2">
        <div class="">
            <input class="form-check-input disabled-all building-activity-del" type="checkbox" name="building_hidden" id="building_hidden" value="" onclick="hideOrShowBuilding(this)" disabled>
            <label class="form-check-label" for="building_hidden">選択建物を非表示</label>
            <button type="button" class="btn btn-outline-secondary ms-5 disabled-all building-activity-del" id="ButtonDeleteBuilding" onclick="deleteBuilding(this)" disabled>選択建物を削除</button>
        </div>
        <div class="mt-2">
            <div class="d-flex flex-row mt-2">
                <label class="form-check-label" for="building_height">高さ</label>
                {{-- 小小数を入力できるようにする: step="0.1" --}}
                {{-- 負の数の入力を許さない: min="0" --}}
                <input type="number" step="0.1" min="0" class="form-control w-25 ms-2 disabled-all building-activity-addnew" name="building_height" id="building_height" oninput="validateBuildingHeight(this)" disabled>
                <label class="form-check-label ms-1" for="building_height">(m)</label>
                <label class="form-check-label ms-5" for="obj_stl_type_id">建物種別</label>
                <select class="form-select mx-1 w-auto ms-2 disabled-all building-activity-addnew" id="obj_stl_type_id" name="obj_stl_type_id" disabled>
                    @foreach ($stlTypeOptionsByGroundFalse as $stlType)
                    <option value="{{ $stlType->stl_type_id }}">{{ $stlType->stl_type_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-outline-secondary disabled-all building-activity-addnew" id="ButtonPreviewBuilding" onclick="previewBuilding(this)" disabled>プレビュー</button>
            <button type="button" class="btn btn-outline-secondary ms-5 disabled-all" id="ButtonResetBuilding" onclick="resetAddNewBuilding(this)" disabled>元に戻す</button>
            <button type="button" class="btn btn-outline-secondary ms-5 disabled-all" id="ButtonAddNewBuilding" onclick="addNewBuilding(this)" disabled>新規建物を作成</button>
        </div>

    </div>
</div>

{{-- 3D地図表示エリア --}}
<div class="col-sm-12">

    <div class="d-flex flex-column justify-content-center align-items-center mt-4 bg-light d-none" id="loadingDiv" style="height: 600px;">
        <div class="spinner-border text-primary" role="status" style="width: 5em;height: 5em;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2" role="status">
            <span class="">3D地図描画準備中</span>
        </div>
    </div>

    <div class="3d-view-area mt-4" id="cesiumContainer" style="height: 600px;">
        {{-- CZMLファイル表示エリア --}}
    </div>
</div>
