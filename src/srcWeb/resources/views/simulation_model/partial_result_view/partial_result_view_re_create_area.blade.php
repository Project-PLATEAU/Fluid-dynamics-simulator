<details class="position-absolute simulationRecreateDetails" id="simulationRecreateDetails_{{ $map_id }}" style="top:15px; left: 15px;">
    <summary class="fw-bold text-white fs-5 mt-1 mb-1">
        <span><i class="bi bi-list text-white fs-1"></i></span>
    </summary>

    <div class="rounded-3 bg-dark bg-opacity-25 text-white" style="top: 210px; left: 32px; background-color: white; width: 380px; ">
        <div class="d-flex flex-column">
            <div class="p-2 border-bottom border-2 ms-2">
                {{-- シミュレーションモデル名 --}}
                <div class="row p-2 ps-1">
                    <label for="identification_name" class="col-form-label me-3">シミュレーションモデル名</label>
                </div>
                 <div class="row">
                    <div class="col-sm-12">
                        <input type="text" class="form-control rounded-pill" name="identification_name_{{ $map_id }}" id="identification_name_{{ $map_id }}" value="">
                    </div>
                </div>
                <div class="d-flex flex-column mt-2">
                    {{-- 3D都市モデル名 --}}
                    <div class="d-flex flex-row">
                        <div class="col-sm-5">3D都市モデル名</div>
                        <div class="col-sm-6">{{ $simulationModel->city_model->identification_name }}</div>
                         <input type="hidden" name="city_model_id_{{ $map_id }}" id="city_model_id_{{ $map_id }}" value="{{ $simulationModel->city_model->city_model_id }}">
                    </div>
                    {{-- 解析対象地域 --}}
                    <div class="d-flex flex-row">
                        <div class="col-sm-5">解析対象地域</div>
                        <div class="col-sm-6">{{ $simulationModel->region->region_name }}</div>
                        <input type="hidden" name="region_id_{{ $map_id }}" id="region_id_{{ $map_id }}" value="{{ $simulationModel->region->region_id }}">
                    </div>
                </div>
            </div>

            <div class="p-2 border-bottom border-2 simulationRecreateScroll" id="simulationRecreateScroll_{{ $map_id }}" style="height: 290px; overflow: hidden; overflow-y:auto;">
                {{-- 外力等環境条件のエリア --}}
                <details class="mt-2" open>
                    <summary class="p-2">
                        <span>外力等環境条件</span>
                    </summary>

                    <div class="ms-4">
                        {{-- 日付 --}}
                        <div class="row mt-2">
                            <label for="solar_altitude_date_view_{{ $map_id }}" class="col-form-label me-3 col-sm-3">日付</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control rounded-pill" name="solar_altitude_date_view_{{ $map_id }}" id="solar_altitude_date_view_{{ $map_id }}" value="{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->solar_altitude_date, 'm月d日') }}">
                                <input type="hidden" name="solar_altitude_date_{{ $map_id }}" id="solar_altitude_date_{{ $map_id }}" value="{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->solar_altitude_date, App\Utils\DatetimeUtil::DATE_FORMAT) }}">
                            </div>
                        </div>
                        {{-- 時間帯 --}}
                        <div class="row mt-2">
                            <label for="solar_altitude_time_{{ $map_id }}" class="col-form-label me-3 col-sm-3">時間帯</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control rounded-pill" name="solar_altitude_time_{{ $map_id }}" id="solar_altitude_time_{{ $map_id }}" value="{{ $simulationModel->solar_altitude_time }}">
                            </div>
                            <label for="solar_altitude_time_{{ $map_id }}" class="col-form-label px-2 col-sm-2">(時)</label>
                        </div>
                        {{-- 外気温 --}}
                        <div class="row mt-2">
                            <label for="temperature" class="col-form-label me-3 col-sm-3">外気温</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control rounded-pill" name="temperature_{{ $map_id }}" id="temperature_{{ $map_id }}" value="{{ $simulationModel->temperature }}">
                            </div>
                            <label for="temperature" class="col-form-label px-2 col-sm-2">(°C)</label>
                        </div>
                        {{-- 風速 --}}
                        <div class="row mt-2">
                            <label for="wind_speed" class="col-form-label me-3 col-sm-3">風速</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control update-region rounded-pill" name="wind_speed_{{ $map_id }}" id="wind_speed_{{ $map_id }}" value="{{ $simulationModel->wind_speed }}">
                            </div>
                            <label for="wind_speed" class="col-form-label px-2 col-sm-2">(m/s)</label>
                        </div>
                        {{-- 湿度 --}}
                        <div class="row mt-2">
                            <label for="humidity" class="col-form-label me-3 col-sm-3">湿度</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control rounded-pill" name="humidity_{{ $map_id }}" id="humidity_{{ $map_id }}" value="{{ $simulationModel->humidity }}">
                            </div>
                            <label for="humidity" class="col-form-label px-2 col-sm-2">(%)</label>
                        </div>
                        {{-- 風向 --}}
                        <div class="row mt-2">
                            <label for="wind_direction" class="col-form-label me-3 col-sm-3">風向</label>
                            <div class="col-sm-6">
                                <select class="form-select rounded-pill" id="wind_direction_{{ $map_id }}" name="wind_direction_{{ $map_id }}">
                                    @foreach ($windDirections as $windDirection)
                                        <option value="{{ $windDirection['database_order'] }}" @if($simulationModel->wind_direction == $windDirection['database_order']) selected @endif>
                                            {{ $windDirection['display_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </details>

                {{-- 熱対策施策条件のエリア --}}
                <details class="mt-2">
                    <summary class="p-2">
                        <span>熱対策施策条件</span>
                    </summary>
                    <div class="ms-4">
                        <div class="row">
                            <label for="policy_id" class="col-form-label me-3 col-sm-3">施策</label>
                            <div class="col-sm-6">
                                <select class="form-select me-2 rounded-pill" id="policy_id_{{ $map_id }}" name="policy_id_{{ $map_id }}">
                                    <option value="0">未選択</option>
                                    @foreach($policyList as $policy)
                                        <option value="{{ $policy->policy_id }}">{{ $policy->policy_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <label class="col-form-label me-3 col-sm-3">対象</label>
                            <div class="col-sm-6">
                                <select class="form-select me-2 rounded-pill" id="stl_type_id_{{ $map_id }}" name="stl_type_id_{{ $map_id }}">
                                    <option value="0">未選択</option>
                                    @php
                                        $stlModels = $simulationModel->region->stl_models()->get();
                                    @endphp
                                    @foreach($stlModels as $stlModel)
                                        <option value="{{ $stlModel->stl_type->stl_type_id }}">{{ $stlModel->stl_type->stl_type_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            <button type="button" class="btn btn-outline-secondary me-2 text-white border-white" id="ButtonAddSmPolicy_{{ $map_id }}" onclick="ajaxRequestAddNewSmPolicy('{{ $simulationModel->simulation_model_id }}', {{ $map_id }})">追加↓</button>
                            <button type="button" class="btn btn-outline-secondary text-white border-white" id="ButtonDeleteSmPolicy_{{ $map_id }}" onclick="ajaxRequestDeleteSmPolicy('{{ $simulationModel->simulation_model_id }}', {{ $map_id }})">削除↑</button>
                        </div>
                        <div class="row mt-2 mb-1">
                            <div id="smPoliciesTblDiv_{{ $map_id }}">
                                @include('simulation_model/partial_sm_policy.partial_sm_policy_list',
                                    ['smPolicies' => $simulationModel->simulation_model_policies()->get(),
                                    'simulationModelId' => $simulationModel->simulation_model_id,
                                    'mapId' => $map_id
                                    ])
                            </div>
                        </div>
                    </div>
                </details>
            </div>

            {{-- ボタンのエリア --}}
            <div class="d-flex justify-content-end mt-2 mb-2">
                <div class="d-flex flex-column">
                    <div class="form-check mx-3">
                        <input class="form-check-input" type="checkbox" value="1" id="CheckboxSimulationStart_{{ $map_id }}" name="isStart_{{ $map_id }}">
                        <label class="form-check-label" for="CheckboxSimulationStart">保存に続けてシミュレーションを開始する</label>
                    </div>
                    <div class="button-area d-flex justify-content-end mx-3">
                        <button type="button" class="btn btn-outline-secondary me-2 text-white border-white" id="ButtonSave_{{ $map_id }}" onclick="onclickRecreateSimulationModel('{{ $simulationModel->simulation_model_id }}', {{ $map_id }})">保存</button>
                        <button type="reset" class="btn btn-outline-secondary text-white border-white" id="ButtonCancel_{{ $map_id }}" onclick="cancel({{ $map_id }})">キャンセル</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</details>
