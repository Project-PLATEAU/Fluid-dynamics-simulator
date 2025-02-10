{{-- 複数(2)シミュレーションモデル結果の描画 --}}
<form id="frmSmResultMode" method="" action="">
    <div class="d-flex flex-row">
        @forEach($show_results as $index => $result)
        <div class="d-flex flex-column w-50">
            {{-- シミュレーションモデル結果表示モード切替のエリア --}}
            <div class="ps-2" id ="sm-result-mode-display-area_{{ $index }}">
                {{-- シミュレーションモデル名 --}}
                <div class="mb-1">
                    <label class="form-check-label">シミュレーションモデル名：</label>
                    <label class="form-check-label ms-1">{{ $result['simulation_model']->identification_name }}</label>
                </div>

                <div class="d-flex flex-row">
                    {{-- 風況ラジオ --}}
                    <div class="form-check me-1">
                        <input class="form-check-input" type="radio" name="visualization_type_{{ $index }}" id="visualization_type_wind_{{ $index }}" data-href="{{ route('simulation_model.change_mode', ['id' => $result['simulation_model']->simulation_model_id]) }}" value="{{ App\Commons\Constants::VISUALIZATION_TYPE_WINDY }}" @if($result['visualization_type'] == App\Commons\Constants::VISUALIZATION_TYPE_WINDY) checked @endif onchange="changeMode(this)">
                        <label class="form-check-label" for="visualization_type_wind_{{ $index }}">風況</label>
                    </div>
                    {{-- 中空温度ラジオ --}}
                    <div class="form-check mx-3">
                        <input class="form-check-input" type="radio" name="visualization_type_{{ $index }}" id="visualization_type_temperature_{{ $index }}" data-href="{{ route('simulation_model.change_mode', ['id' => $result['simulation_model']->simulation_model_id]) }}" value="{{ App\Commons\Constants::VISUALIZATION_TYPE_TEMP }}" @if($result['visualization_type'] == App\Commons\Constants::VISUALIZATION_TYPE_TEMP) checked @endif onchange="changeMode(this)">
                        <label class="form-check-label" for="visualization_type_temperature_{{ $index }}">中空温度</label>
                    </div>
                    {{-- 暑さ指数ラジオ --}}
                    <div class="form-check mx-3">
                        <input class="form-check-input" type="radio" name="visualization_type_{{ $index }}" id="visualization_type_heat_index_{{ $index }}" data-href="{{ route('simulation_model.change_mode', ['id' => $result['simulation_model']->simulation_model_id]) }}" value="{{ App\Commons\Constants::VISUALIZATION_TYPE_HEAT_INDEX }}" @if($result['visualization_type'] == App\Commons\Constants::VISUALIZATION_TYPE_HEAT_INDEX) checked @endif onchange="changeMode(this)">
                        <label class="form-check-label" for="visualization_type_heat_index_{{ $index }}">暑さ指数</label>
                    </div>
                    {{-- 高さプルダウン(※「暑さ指数」が選択された場合、非表示する。) --}}
                    @php
                        $dNone = ($result['visualization_type'] == App\Commons\Constants::VISUALIZATION_TYPE_HEAT_INDEX) ? 'd-none' : ""
                    @endphp
                    <div class="form-check form-group me-5 {{ $dNone }}" id="height-area_{{ $index }}">
                        <div class="d-flex flex-row">
                            <div class="mx-3">
                                <label class="form-check-label" for="height_{{ $index }}">高さ</label>
                            </div>
                            <div class="mx-3">
                                <select class="form-select mx-1" id="height_{{ $index }}" name="height_{{ $index }}" data-href="{{ route('simulation_model.change_mode', ['id' => $result['simulation_model']->simulation_model_id]) }}" onchange="changeMode(this, 2)">
                                    @foreach ($heightList as $height)
                                        <option value="{{ $height->height_id }}" @if($result['height_id'] == $height->height_id) selected @endif>{{ $height->height }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-check-label" for="height_{{ $index }}">(m)</label>
                            </div>
                        </div>
                    </div>

                    {{-- 同期ボタン --}}
                    <div class="form-check">
                        <button type="button" class="btn btn-outline-secondary" onclick="cameraSynchronization({{ $index }})">同期</button>
                    </div>
                </div>

                {{-- 表示モードを切替直前の状態を維持するための情報 --}}
                <input type="hidden" name="map_current_heading_{{ $index }}" id="map_current_heading_{{ $index }}" value="{{ isset($result['viewer_camera']) ? $result['viewer_camera']['map_current_heading'] : null }}">
                <input type="hidden" name="map_current_pitch_{{ $index }}" id="map_current_pitch_{{ $index }}" value="{{ isset($result['viewer_camera']) ? $result['viewer_camera']['map_current_pitch'] : null }}">
                <input type="hidden" name="map_current_roll_{{ $index }}" id="map_current_roll_{{ $index }}" value="{{ isset($result['viewer_camera']) ? $result['viewer_camera']['map_current_roll'] : null }}">
                <input type="hidden" name="map_current_position_x_{{ $index }}" id="map_current_position_x_{{ $index }}" value="{{ isset($result['viewer_camera']) ? $result['viewer_camera']['map_current_position_x'] : null }}">
                <input type="hidden" name="map_current_position_y_{{ $index }}" id="map_current_position_y_{{ $index }}" value="{{ isset($result['viewer_camera']) ? $result['viewer_camera']['map_current_position_y'] : null }}">
                <input type="hidden" name="map_current_position_z_{{ $index }}" id="map_current_position_z_{{ $index }}" value="{{ isset($result['viewer_camera']) ? $result['viewer_camera']['map_current_position_z'] : null }}">

                @php
                    $solarAltitudeDatetime = App\Utils\DatetimeUtil::changeFormat($result['simulation_model']->solar_altitude_date, App\Utils\DatetimeUtil::DATE_FORMAT) . " " . $result['simulation_model']->solar_altitude_time . ":00:00";
                @endphp
                <input type="hidden" id="solarAltitudeDatetime_{{ $index}}" name="solarAltitudeDatetime" value="{{ $solarAltitudeDatetime }}">
            </div>
            {{-- 地図 --}}
            <div class="position-relative pe-1" id="map_{{ $index }}">
                <div class="3d-view-area mt-2 position-relative" id="cesiumContainer_{{ $index }}"></div>
                {{-- シミュレーション再作成エリア --}}
                @include('simulation_model/partial_result_view.partial_result_view_re_create_area',
                ['simulationModel' => $result['simulation_model'], 'map_id' => $index])
            </div>
            {{-- ボタンの表示エリア --}}
            <div class="row">
                <div class="col-sm-8">
                    <div class="button-area mt-4 ms-5">
                        {{-- 戻るボタンは片方(左の地図)しか使わないため、判定処理を追加 --}}
                        @if ($index == 0)
                        <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('simulation_model.index') }}'">戻る</button>
                        @endif
                        <button type="button" class="btn btn-outline-secondary" data-href="{{ route('simulation_model.download', ['id' => $result['simulation_model']->simulation_model_id, 'map_id' => $index]) }}" onclick="changeMode(this, 3)">ダウンロード</button>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="float-end mt-2">
                        <div class="d-flex flex-column" id="usage-guide-area_{{ $index }}">
                            {{-- 凡例表示 --}}
                            @switch($result['visualization_type'])
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

                            <div class="d-flex flex-row ms-2 me-2">
                                <div class="w-50">
                                    <span id="legend_label_higher_{{ $index }}">{{ $result['visualization'] ? $result['visualization']->legend_label_higher : "" }}</span>
                                </div>
                                <div class="w-50">
                                    <div class="float-end">
                                        <span id="legend_label_lower_{{ $index }}">{{ $result['visualization'] ? $result['visualization']->legend_label_lower : "" }}</span>
                                    </div>
                                </div>
                            </div>
                            {{-- 固定値利用可否チェックボックス --}}
                            <div class="{{ $dNone }}">
                                <input type="checkbox" name="ckb_value_fixed_{{ $index }}" id="ckb_value_fixed_{{ $index }}" data-href="{{ route('simulation_model.change_mode', ['id' => $result['simulation_model']->simulation_model_id]) }}" value="{{ isset($result['ckb_value_fixed']) ? $result['ckb_value_fixed'] : '' }}" onchange="onchangeUpdateLegendValue(this, {{ $index }})" @if(isset($result['ckb_value_fixed']) && $result['ckb_value_fixed'] == App\Commons\Constants::LEGENF_TYPE_FIXED) checked @endif>
                                <label for="ckb_value_fixed_{{ $index }}">デフォルトの最高・最低値を使用する</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</form>
