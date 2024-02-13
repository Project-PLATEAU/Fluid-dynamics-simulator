@extends('layouts.app')

@section('title', 'シミュレーションモデル編集')

@section('css')
<style type="">
    .bg-cyan {
        background-color: cyan;
    }
</style>
 <link rel="stylesheet" href="{{ asset('/css/jquery-ui-1.13.2.min.css') }}">
@endsection

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_SIMULATION }}</span>
@endsection

@section('city-model-display-area')
<span>{{ $simulationModel->identification_name }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container">
    <form id="frmSimulation" method="POST" action="{{ request()->fullUrl() }}">
        {{ csrf_field() }}
        <div class="row">
            <div class="col-sm-7">
                <div class="mb-3 row">
                    <label for="identification_name" class="col-sm-2 col-form-label">3D都市モデル</label>
                    <div class="col-sm-9">
                        <label for="identification_name" class="col-form-label">{{ $simulationModel->city_model->identification_name }}</label>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="identification_name" class="col-sm-2 col-form-label">モデル識別名</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="identification_name" id="identification_name" value="{{ $simulationModel->identification_name }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-5">
                <label for="" class="col-sm-3 col-form-label">対象地域</label>
                <label for="" class="col-sm-8 col-form-label">{{ $simulationModel->region->region_name }}</label>
            </div>
        </div>

        <div class="row d-none">
            <div class="col-sm-7">
                <div class="row">
                    <label for="" class="col-sm-2 col-form-label">解析範囲</label>
                    <div class="col-sm-8">
                        <a class="col-sm-2" href="https://www.google.com/maps/" target="blank">Google Map</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-1 d-none">
            <div class="col-sm-7">
                <div class="px-2 row">
                    <label for="" class="col-sm-2 col-form-label">南,西</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="south_west" id="south_west" value="{{ $simulationModel->south_latitude . ', '. $simulationModel->west_longitude }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-1 d-none">
            <div class="col-sm-7">
                <div class="px-2 row">
                    <label for="" class="col-sm-2 col-form-label">北,東</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="north_east" id="north_east" value="{{ $simulationModel->north_latitude . ', '. $simulationModel->east_longitude }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 d-none">
            <div class="col-sm-7">
                <div class="px-2 row">
                    <label for="" class="col-sm-2 col-form-label">地面高度</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" name="ground_altitude" id="ground_altitude" value="{{ $simulationModel->ground_altitude }}">
                    </div>
                    <label for="" class="col-sm-1 col-form-label">(m)</label>

                    <label for="" class="col-sm-2 col-form-label">上空高度</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" name="sky_altitude" id="sky_altitude" value="{{ $simulationModel->sky_altitude }}">
                    </div>
                    <label for="" class="col-sm-1 col-form-label">(m)</label>
                </div>
            </div>
        </div>
        {{-- 解析ソルバのエリア --}}
        <details class="border border-secondary border-2 mb-3 mt-2">
            <summary class="d-block">
                <p class="border-bottom border-secondary border-2 text-center p-2">解析ソルバ</p>
            </summary>
            <div class="row mb-1">
                <div class="col-sm-7">
                        <div class="px-2 row">
                            <label for="" class="col-sm-2 col-form-label">解析ソルバー</label>
                            <div class="col-sm-9">
                                <select class="form-select mx-1" id="solver_id" name="solver_id">
                                    @foreach($solverList as $solver)
                                        <option value="{{ $solver->solver_id }}" @if ($solver->solver_id == $simulationModel->solver_id) selected @endif>{{ $solver->solver_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-sm-7">
                    <div class="px-2 row">
                        <label for="" class="col-sm-3 col-form-label">解析メッシュ粒度</label>
                        <div class="col-sm-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mesh_level" id="mesh_level_1" value="1" @if ($simulationModel->mesh_level == 1 ) checked @endif>
                                <label class="form-check-label" for="mesh_level_1">1 (粗い)</label>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mesh_level" id="mesh_level_2" value="2" @if ($simulationModel->mesh_level == 2 ) checked @endif>
                                <label class="form-check-label" for="mesh_level_2">2</label>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mesh_level" id="mesh_level_3" value="3" @if ($simulationModel->mesh_level == 3 ) checked @endif>
                                <label class="form-check-label" for="mesh_level_3">3 (細かい)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </details>

        {{-- 外力等環境条件のエリア --}}
        <details class="border border-secondary border-2 mb-3 mt-3" open>
            <summary class="d-block">
                <p class="border-bottom border-secondary border-2 text-center p-2">外力等環境条件</p>
            </summary>
            <div class="row px-2 mb-2">
                <div class="col-sm-6">
                    <div class="d-flex flex-row">
                        <label for="south_latitude" class="col-form-label me-2 col-sm-2">外気温</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="temperature" id="temperature" value="{{ $simulationModel->temperature }}">
                        </div>
                        <label for="temperature" class="col-form-label px-2 col-sm-2">(°C)</label>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label for="west_longitude" class="col-form-label me-2 col-sm-2">風速</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control update-region" name="wind_speed" id="wind_speed" value="{{ $simulationModel->wind_speed }}">
                        </div>
                        <label for="wind_speed" class="col-form-label px-2 col-sm-2">(m/s)</label>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label for="" class="col-form-label me-2 col-sm-2">風向</label>
                        <div class="col-sm-4">
                            <input class="form-check-input" type="radio" name="wind_direction" id="wind_direction_1" @if ($simulationModel->wind_direction == 1 ) checked @endif value="1">
                            <label class="form-check-label" for="wind_direction_1">南→北</label>
                        </div>
                        <div class="col-sm-4">
                            <input class="form-check-input" type="radio" name="wind_direction" id="wind_direction_2" @if ($simulationModel->wind_direction == 2 ) checked @endif value="2">
                            <label class="form-check-label" for="wind_direction_2">北→南</label>
                        </div>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label for="" class="col-form-label me-2 col-sm-2"></label>
                        <div class="col-sm-4">
                            <input class="form-check-input" type="radio" name="wind_direction" id="wind_direction_3" @if ($simulationModel->wind_direction == 3 ) checked @endif value="3">
                            <label class="form-check-label" for="wind_direction_3">西→東</label>
                        </div>
                        <div class="col-sm-4">
                            <input class="form-check-input" type="radio" name="wind_direction" id="wind_direction_4" @if ($simulationModel->wind_direction == 4 ) checked @endif value="4">
                            <label class="form-check-label" for="wind_direction_4">東→西</label>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="d-flex flex-row mt-2">
                        <label for="ground_altitude" class="col-form-label me-2 col-sm-2">日付</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="solar_altitude_date" id="solar_altitude_date" value="{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->solar_altitude_date, 'm月d日') }}">
                        </div>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label for="ground_altitude" class="col-form-label me-2 col-sm-2">時間帯</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control" name="solar_altitude_time" id="solar_altitude_time" value="{{ $simulationModel->solar_altitude_time }}">
                        </div>
                        <label for="ground_altitude" class="col-form-label px-2 col-sm-2">(時)</label>
                    </div>
                </div>

                {{-- <div class="col-sm-6">
                    <label for="north_latitude" class="col-form-label me-2">日射吸収率(0以上1以下の実数)</label>
                    <div class="d-flex flex-row px-3">
                        <label for="" class="col-form-label me-2 col-sm-2">建物(木造)</label>
                        <div class="col-sm-8 mb-1">
                            <input type="text" class="form-control" name="solar_absorptivity[]" id="solar_absorptivity_1" value="{{ App\Models\Db\SolarAbsorptivity::getBySimulationIdAndStlTypeId($simulationModel->simulation_model_id, 1)->solar_absorptivity }}">
                        </div>
                    </div>
                    <div class="d-flex flex-row px-3">
                        <label for="" class="col-form-label me-2 col-sm-2">建物(非木造)</label>
                        <div class="col-sm-8 mb-1">
                            <input type="text" class="form-control" name="solar_absorptivity[]" id="solar_absorptivity_2" value="{{ App\Models\Db\SolarAbsorptivity::getBySimulationIdAndStlTypeId($simulationModel->simulation_model_id, 2)->solar_absorptivity }}">
                        </div>
                    </div>
                    <div class="d-flex flex-row px-3">
                        <label for="" class="col-form-label me-2 col-sm-2">建物(その他)</label>
                        <div class="col-sm-8 mb-1">
                            <input type="text" class="form-control" name="solar_absorptivity[]" id="solar_absorptivity_3" value="{{ App\Models\Db\SolarAbsorptivity::getBySimulationIdAndStlTypeId($simulationModel->simulation_model_id, 3)->solar_absorptivity }}">
                        </div>
                    </div>
                    <div class="d-flex flex-row px-3">
                        <label for="" class="col-form-label me-2 col-sm-2">地表面(道路)</label>
                        <div class="col-sm-8 mb-1">
                            <input type="text" class="form-control" name="solar_absorptivity[]" id="solar_absorptivity_4" value="{{ App\Models\Db\SolarAbsorptivity::getBySimulationIdAndStlTypeId($simulationModel->simulation_model_id, 4)->solar_absorptivity }}">
                        </div>
                    </div>
                    <div class="d-flex flex-row px-3">
                        <label for="" class="col-form-label me-2 col-sm-2">地表面(緑地)</label>
                        <div class="col-sm-8 mb-1">
                            <input type="text" class="form-control" name="solar_absorptivity[]" id="solar_absorptivity_5" value="{{ App\Models\Db\SolarAbsorptivity::getBySimulationIdAndStlTypeId($simulationModel->simulation_model_id, 5)->solar_absorptivity }}">
                        </div>
                    </div>
                    <div class="d-flex flex-row px-3">
                        <label for="" class="col-form-label me-2 col-sm-2">地表面(その他)</label>
                        <div class="col-sm-8 mb-1">
                            <input type="text" class="form-control" name="solar_absorptivity[]" id="solar_absorptivity_6" value="{{ App\Models\Db\SolarAbsorptivity::getBySimulationIdAndStlTypeId($simulationModel->simulation_model_id, 6)->solar_absorptivity }}">
                        </div>
                    </div>
                </div> --}}
            </div>
        </details>

         {{-- 熱対策施策条件のエリア --}}
        <details class="border border-secondary border-2 mb-3 mt-3">
            <summary class="d-block">
                <p class="border-bottom border-secondary border-2 text-center p-2">熱対策施策条件</p>
            </summary>
            <div class="row px-2 mb-2">
                <div class="col-sm-5">
                    <div class="d-flex flex-row">
                        <label for="south_latitude" class="col-form-label me-2 col-sm-2">施設</label>
                        <div class="col-sm-8">
                            <select class="form-select me-2" style="width: 380px;" id="policy_id" name="policy_id">
                                <option>未選択</option>
                                <option>打ち水</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label for="west_longitude" class="col-form-label me-2 col-sm-2">対象</label>
                        <div class="col-sm-8">
                            <select class="form-select me-2" style="width: 380px;" id="stl_type_id" name="stl_type_id">
                                <option>未選択</option>
                                <option>地表面(道路)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div class="d-flex flex-column mt-2">
                        <button type="button" class="btn btn-outline-secondary mb-2" id="ButtonXXXX">追加→</button>
                        <button type="button" class="btn btn-outline-secondary" id="ButtonYYYY">←削除</button>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="px-5">
                        <table class="table table-hover" id="tblSimulationModel">
                            <thead>
                                <tr>
                                    <th scope="col">施策</th>
                                    <th scope="col">対象建物・地表面</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                <tr>
                                    <td>施策テスト１</td>
                                    <td>建物(住宅)</td>
                                </tr>
                                <tr>
                                    <td>施策テスト２</td>
                                    <td>地表面(道路)</td>
                                </tr>
                                <tr>
                                    <td>施策テスト３</td>
                                    <td>地表面(道路)</td>
                                </tr>
                                <tr>
                                    <td>施策テスト４</td>
                                    <td>地表面(道路)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </details>

        <div class="form-check {{ ($message && $message['code'] == 'I5') ? 'd-none' : '' }}">
            <input class="form-check-input" type="checkbox" value="1" id="CheckboxSimulationStart" name="isStart">
            <label class="form-check-label" for="CheckboxSimulationStart">保存に続けてシミュレーションを開始する</label>
        </div>

        <div class="button-area mt-3">
            <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('simulation_model.index') }}'">キャンセル</button>
            <button type="submit" class="btn btn-outline-secondary {{ ($message && $message['code'] == 'I5') ? 'd-none' : '' }}" id="ButtonUpdate">保存</button>

        </div>
    </form>
</div>
@endsection

@section('js')
    <script src="{{ asset('/js/jquery-ui-1.13.2.min.js') }}"></script>
    <script>
        $(function(){

            $("#solar_altitude_date").datepicker({
                dateFormat: "mm月dd日",
                changeYear: true, // 年の選択を有効にする
                changeMonth: true, // 月の選択を有効にする
                showOtherMonths: true, // 前後の月の日付も一緒に表示する
            });

            @if ($message)
                const msg_type = "{{ $message['type'] }}";
                const code = "{{ $message['code'] }}";
                const msg = "{!! $message['msg'] !!}";

                if (msg_type == "E")
                {
                    // エラーメッセージダイアログを表示
                    $("div#messageModal [class='modal-body']").html(
                        '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/error.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                    $("div#messageModal [class='modal-footer']").html('<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');
                }
                else if (msg_type == "W")
                {
                    // 警告メッセージダイアログを表示
                    $("div#messageModal [class='modal-body']").html(
                        '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/warning.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                    $("div#messageModal [class='modal-footer']").html(
                        '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-outline-secondary" id="ButtonOK">OK</button>');
                }
                else if (msg_type == "I")
                {
                    // 情報メッセージダイアログを表示
                    $("div#messageModal [class='modal-body']").html(
                        '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/info.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                    $("div#messageModal [class='modal-footer']").html(
                        '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');
                }

                $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
                $("div#messageModal [class='modal-body'] span#message").html(msg);
                $('#messageModal').modal('show');
            @endif
        });
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
