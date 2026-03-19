{{-- <label class="col-sm-1 col-form-label"></label> --}}
<div class="col-sm-2">
    <div class="d-flex flex-column mt-2">
        <div class="">
            <input class="form-check-input disabled-all edit-disabled" type="radio" name="map_activity" id="map_activity_addnew_building" value="{{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }}" onclick="onClickMapActivity(this)" disabled>
            <label class="form-check-label" for="map_activity_addnew_building">建物作成</label>
        </div>
        <div class="mt-4">
            <input class="form-check-input disabled-all edit-disabled" type="radio" name="map_activity" id="map_activity_addnew_plant_cover" value="{{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }}" onclick="onClickMapActivity(this)" disabled>
            <label class="form-check-label" for="map_activity_addnew_plant_cover">植被作成</label>
        </div>
        <div class="mt-4">
            <input class="form-check-input disabled-all edit-disabled" type="radio" name="map_activity" id="map_activity_addnew_tree" value="{{ App\Commons\Constants::ADDNEW_MODEL_TREE }}" onclick="onClickMapActivity(this)" disabled>
            <label class="form-check-label" for="map_activity_addnew_tree">単独木作成</label>
        </div>
        <div class="" style="margin-top: 50px;">
            <input class="form-check-input disabled-all edit-disabled" type="radio" name="map_activity" id="map_activity_delete" value="{{ App\Commons\Constants::DELETE_MODEL }}" onclick="onClickMapActivity(this)" disabled>
            <label class="form-check-label" for="map_activity_delete">モデル削除</label>
        </div>
    </div>
</div>

<div class="col-sm-10">
    <div class="d-flex flex-column mt-2">
        <div class="">
            <div class="d-flex flex-row">
                <label class="form-check-label" for="building_height">高さ</label>
                {{-- 小小数を入力できるようにする: step="0.1" --}}
                {{-- 負の数の入力を許さない: min="0" --}}
                <input type="number" step="0.1" min="0" class="form-control disabled-all activity-addnew-building" name="building_height" id="building_height" oninput="validatePositiveNumber(this)" style="margin-left: 29px; width: 165px;" disabled>
                <label class="form-check-label ms-1" for="building_height">(m)</label>
                <label class="form-check-label ms-5" for="obj_stl_type_id">建物種別</label>
                <select class="form-select mx-1 w-auto ms-5 disabled-all activity-addnew-building" id="obj_stl_type_id" name="obj_stl_type_id" disabled>
                    @foreach ($stlTypeOptionsByGroundFalse as $stlType)
                    <option value="{{ $stlType->stl_type_id }}">{{ $stlType->stl_type_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-1">
            <div class="d-flex flex-row mt-2">
                <label class="form-check-label" for="building_height">高さ</label>
                {{-- 小小数を入力できるようにする: step="0.1" --}}
                {{-- 負の数の入力を許さない: min="0" --}}
                <input type="number" step="0.1" min="0" class="form-control disabled-all activity-addnew-plant-cover" name="plant-cover-height" id="plant-cover-height" oninput="validatePositiveNumber(this)" style="margin-left: 29px; width: 165px;" disabled>
                <label class="form-check-label ms-1" for="plant-cover-height">(m)</label>
            </div>
        </div>
        <div class="mt-1">
            <div class="d-flex flex-row mt-2">
                <label class="form-check-label" for="tree_type_id">分類</label>
                 <select class="form-select w-auto ms-4 disabled-all activity-addnew-tree" id="tree_type_id" name="tree_type_id" onchange="onchangeTreeType(this)" disabled>
                    {{-- 未選択 --}}
                    <option value="-1">未選択</option>
                    <option value="0">手動入力</option>
                    @foreach ($treeTypeOptions as $treeType)
                        <option value="{{ $treeType->tree_type_id }}">{{ $treeType->tree_type_name }}</option>
                    @endforeach
                </select>
                <button class="btn" id="display_info" onclick="displayInfo(event)"><i class="bi bi-info-circle-fill"></i></button>

                <label class="form-check-label ms-3" for="canopy_height">樹高</label>
                {{-- 小小数を入力できるようにする: step="0.1" --}}
                {{-- 負の数の入力を許さない: min="0" --}}
                <input type="number" step="0.1" min="0" class="form-control ms-3 disabled-all activity-addnew-tree" name="canopy_height" id="canopy_height" oninput="validatePositiveNumber(this)" onblur="checkBelowMinimum(this)" data-type="1" style="margin-left: 30px;width: 165px;" disabled>
                <label class="form-check-label ms-1" for="canopy_height">(m)</label>

                <label class="form-check-label ms-5" for="canopy_diameter">樹冠直径</label>
                {{-- 小小数を入力できるようにする: step="0.1" --}}
                {{-- 負の数の入力を許さない: min="0" --}}
                <input type="number" step="0.1" min="0" class="form-control ms-3 disabled-all activity-addnew-tree" name="canopy_diameter" id="canopy_diameter" oninput="validatePositiveNumber(this)" onblur="checkBelowMinimum(this)" data-type="2" style="width: 165px;" disabled>
                <label class="form-check-label ms-1" for="canopy_diameter">(m)</label>
            </div>
        </div>
        <div class="mt-2">
            <div class="d-flex flex-row ">
                <input class="form-check-input disabled-all activity-addnew-tree" type="checkbox" name="add_tree_to_line" id="add_tree_to_line" value="" onchange="onChangeAddTreeMode(this)" disabled>
                <label class="form-check-label" for="model_hidden" style="margin-left: 40px;">直線上に追加</label>

                <label class="form-check-label" for="tree_interval" style="margin-left: 130px;">間隔</label>
                <input type="number" step="0.1" min="0" class="form-control ms-3 disabled-all activity-addnew-tree" name="tree_interval" id="tree_interval" oninput="validatePositiveNumber(this)" style="width: 165px;" disabled>
                <label class="form-check-label ms-1" for="tree_interval">(m)</label>
            </div>
        </div>
        <div class="mt-2">
            <input class="form-check-input disabled-all activity-del" type="checkbox" name="model_hidden" id="model_hidden" value="" onclick="hideOrShowBuilding(this)" disabled>
            <label class="form-check-label" for="model_hidden" style="margin-left: 35px;">選択モデルを非表示</label>
        </div>
        <div class="mt-4 text-end">
            <button type="button" class="btn btn-outline-secondary disabled-all" id="ButtonPreviewModel" onclick="previewModel(this)" disabled>プレビュー</button>
            <button type="button" class="btn btn-outline-secondary ms-2 disabled-all" id="ButtonReset" onclick="resetMapActivity(this)" disabled>元に戻す</button>
            <button type="button" class="btn btn-danger ms-2 disabled-all" id="ButtonAddNewModel" onclick="addNewModel(this)" disabled>作成</button>
            <button type="button" class="btn btn-primary ms-2 disabled-all activity-del" id="ButtonDeleteModel" onclick="deleteModel(this)" disabled>モデルを削除</button>
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
