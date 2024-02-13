<label for="" class="col-sm-2 col-form-label"></label>
<div class="col-sm-5">
    <div class="d-flex flex-row mt-2">
        <label for="south_latitude" class="col-form-label me-2 col-sm-2">南端緯度</label>
        <div class="col-sm-8">
            <input type="text" class="form-control update-region" name="south_latitude" id="south_latitude" value="{{ $region ? $region->south_latitude : "" }}">
        </div>
        <label for="south_latitude" class="col-form-label px-2 col-sm-2">(°)</label>
    </div>
    <div class="d-flex flex-row mt-2">
        <label for="west_longitude" class="col-form-label me-2 col-sm-2">西端経度</label>
        <div class="col-sm-8">
            <input type="text" class="form-control update-region" name="west_longitude" id="west_longitude" value="{{ $region ? $region->west_longitude : "" }}">
        </div>
        <label for="west_longitude" class="col-form-label px-2 col-sm-2">(°)</label>
    </div>
    <div class="d-flex flex-row mt-2">
        <label for="ground_altitude" class="col-form-label me-2 col-sm-2">地面高度</label>
        <div class="col-sm-8">
            <input type="text" class="form-control update-region" name="ground_altitude" id="ground_altitude" value="{{ $region ? $region->ground_altitude : "" }}">
        </div>
        <label for="ground_altitude" class="col-form-label px-2 col-sm-2">(m)</label>
    </div>
</div>

<div class="col-sm-5">
    <div class="d-flex flex-row mt-2">
        <label for="north_latitude" class="col-form-label me-2 col-sm-2">北端緯度</label>
        <div class="col-sm-8">
            <input type="text" class="form-control update-region" name="north_latitude" id="north_latitude" value="{{ $region ? $region->north_latitude : "" }}">
        </div>
        <label for="north_latitude" class="col-form-label px-2 col-sm-2">(°)</label>
    </div>
    <div class="d-flex flex-row mt-2">
        <label for="east_longitude" class="col-form-label me-2 col-sm-2">東端経度</label>
        <div class="col-sm-8">
            <input type="text" class="form-control update-region" name="east_longitude" id="east_longitude" value="{{ $region ? $region->east_longitude : "" }}">
        </div>
        <label for="east_longitude" class="col-form-label px-2 col-sm-2">(°)</label>
    </div>
    <div class="d-flex flex-row mt-2">
        <label for="sky_altitude" class="col-form-label me-2 col-sm-2">上空高度</label>
        <div class="col-sm-8">
            <input type="text" class="form-control update-region" name="sky_altitude" id="sky_altitude" value="{{ $region ? $region->sky_altitude : "" }}">
        </div>
        <label for="sky_altitude" class="col-form-label px-2 col-sm-2">(m)</label>
    </div>
</div>
