<div class="row mb-3 align-items-center">
    <label class="col-sm-2 col-form-label">緯度(°)</label>
    <div class="col-sm-10">
        <div class="row">
            <div class="col-sm-3 d-flex align-items-center">
                <label for="south_latitude" class="col-sm-2 col-form-label">南端</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control update-region edit-disabled" name="south_latitude" id="south_latitude" value="{{ $region ? $region->south_latitude : '' }}">
                </div>
            </div>
            <div class="col-sm-3 d-flex align-items-center">
                <label for="north_latitude" class="col-sm-2 col-form-label">北端</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control update-region edit-disabled" name="north_latitude" id="north_latitude" value="{{ $region ? $region->north_latitude : '' }}">
                </div>
            </div>
            <div class="col-sm-3 d-flex align-items-center">
                <label for="west_longitude" class="col-sm-2 col-form-label">西端</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control update-region edit-disabled" name="west_longitude" id="west_longitude" value="{{ $region ? $region->west_longitude : '' }}">
                </div>
            </div>
            <div class="col-sm-3 d-flex align-items-center">
                <label for="east_longitude" class="col-sm-2 col-form-label">東端</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control update-region edit-disabled" name="east_longitude" id="east_longitude" value="{{ $region ? $region->east_longitude : '' }}">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row align-items-center">
    <label class="col-sm-2 col-form-label">高度</label>
    <div class="col-sm-10">
        <div class="row">
            <div class="col-sm-3 d-flex align-items-center">
                <label for="ground_altitude" class="col-sm-4 col-form-label pe-0">地面高度</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control update-region edit-disabled" name="ground_altitude" id="ground_altitude" value="{{ $region ? $region->ground_altitude : '' }}">
                </div>
            </div>
            <div class="col-sm-1 d-flex align-items-center">
                <span>(m)</span>
            </div>
            <div class="col-sm-3 d-flex align-items-center">
                <label for="sky_altitude" class="col-sm-4 col-form-label pe-0">上空高度</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control update-region edit-disabled" name="sky_altitude" id="sky_altitude" value="{{ $region ? $region->sky_altitude : '' }}">
                </div>
            </div>
            <div class="col-sm-1 d-flex align-items-center">
                <span>(m)</span>
            </div>
        </div>
    </div>
</div>
