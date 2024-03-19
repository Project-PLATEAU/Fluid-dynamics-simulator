@extends('layouts.app')

@section('title', 'シミュレーションモデル編集')

@section('css')
<style type="">
    .bg-cyan {
        background-color: cyan;
    }
</style>
<link rel="stylesheet" href="{{ asset('/css/jquery-ui-1.13.2.min.css') }}">
<link rel="stylesheet" href="{{ asset('/css/leaflet-1.9.4.css') }}">
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
                <label class="col-sm-3 col-form-label">対象地域</label>
                <label class="col-sm-8 col-form-label">{{ $simulationModel->region->region_name }}</label>
            </div>
        </div>

        {{-- 境界条件のエリア --}}
        <details class="border border-secondary border-2 mb-3 mt-2">
            <summary class="d-block">
                <p class="border-bottom border-secondary border-2 text-center p-2">境界条件</p>
            </summary>

            <div class="row">
                <div class="col-sm-4">
                    <div class="h-100">
                        <div style="height: 70%;">
                            <div class="row ms-1">
                                <div class="col-sm-12 ms-3">
                                    <label class="col-form-label">東西南北境界</label>
                                </div>
                            </div>
                            <div class="row mb-1 ms-3">
                                <div class="col-sm-12">
                                    <div class="px-2 row">
                                        <label class="col-sm-1 col-form-label"></label>
                                        <div class="col-sm-6">
                                            <input class="form-check-input" type="radio" name="boundary" id="boundary_whole_area" value="1" {{ $simulationModel->isWholeArea() ? "checked" : "" }} onclick="drawMap('1')">
                                            <label class="form-check-label" for="boundary_whole_area">全域</label>
                                        </div>
                                        <div class="col-sm-5">
                                            <label>東西</label>
                                            <div class="float-end">
                                                <label class="ms-4" id="eastAndWestDistance"></label><label>(m)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-1 ms-3">
                                <div class="col-sm-12">
                                    <div class="px-2 row">
                                        <label class="col-sm-1 col-form-label"></label>
                                        <div class="col-sm-6">
                                            <input class="form-check-input" type="radio" name="boundary" id="boundary_narrow_area" value="2" {{ !$simulationModel->isWholeArea() ? "checked" : "" }} onclick="drawMap('2')">
                                            <label class="form-check-label" for="boundary_narrow_area">狭域指定</label>
                                        </div>
                                        <div class="col-sm-5">
                                            <label>南北</label>
                                            <div class="float-end">
                                                <label class="ms-4" id="northAndSouthDistance"></label><label>(m)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="height: 30%;">
                            <div class="row ms-1">
                                <div class="col-sm-12 ms-3">
                                        <label class="col-form-label">上下境界</label>
                                </div>
                            </div>
                            <div class="row mb-1 ms-1">
                                <div class="col-sm-12">
                                    <div class="px-2 row">
                                        <label class="col-sm-1 col-form-label"></label>
                                        <label class="col-sm-3 col-form-label ms-2">地面高度</label>
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control" name="ground_altitude" id="ground_altitude" value="{{ $simulationModel->ground_altitude }}">
                                        </div>
                                        <label class="col-sm-1 col-form-label">(m)</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-1 ms-1">
                                <div class="col-sm-12">
                                    <div class="px-2 row">
                                        <label class="col-sm-1 col-form-label"></label>
                                        <label class="col-sm-3 col-form-label ms-2">上空高度</label>
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control" name="sky_altitude" id="sky_altitude" value="{{ $simulationModel->sky_altitude }}">
                                        </div>
                                        <label class="col-sm-1 col-form-label">(m)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2D地図描画エリア --}}
                <div class="col-sm-8 mb-3">
                    <div id="map" class="border" style="width: 98%; height: 500px;"></div>
                </div>
            </div>
        </details>

        {{-- 解析ソルバのエリア --}}
        <details class="border border-secondary border-2 mb-3 mt-2">
            <summary class="d-block">
                <p class="border-bottom border-secondary border-2 text-center p-2">解析ソルバ</p>
            </summary>
            <div class="row mb-1 ms-3">
                <div class="col-sm-7">
                        <div class="px-2 row">
                            <label class="col-sm-2 col-form-label">解析ソルバー</label>
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
            <div class="row mt-3 ms-3">
                <div class="col-sm-7">
                    <div class="px-2 row">
                        <label class="col-sm-3 col-form-label">解析メッシュ粒度</label>
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
            <div class="row px-2 mb-2 ms-3">
                <div class="col-sm-6">
                    <div class="d-flex flex-row">
                        <label for="temperature" class="col-form-label me-2 col-sm-2">外気温</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="temperature" id="temperature" value="{{ $simulationModel->temperature }}">
                        </div>
                        <label for="temperature" class="col-form-label px-2 col-sm-2">(°C)</label>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label for="wind_speed" class="col-form-label me-2 col-sm-2">風速</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control update-region" name="wind_speed" id="wind_speed" value="{{ $simulationModel->wind_speed }}">
                        </div>
                        <label for="wind_speed" class="col-form-label px-2 col-sm-2">(m/s)</label>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label class="col-form-label me-2 col-sm-2">風向</label>
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
                        <label class="col-form-label me-2 col-sm-2"></label>
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
                        <label for="solar_altitude_date_view" class="col-form-label me-2 col-sm-2">日付</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="solar_altitude_date_view" id="solar_altitude_date_view" value="{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->solar_altitude_date, 'm月d日') }}">
                            <input type="hidden" class="form-control" name="solar_altitude_date" id="solar_altitude_date" value="{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->solar_altitude_date, App\Utils\DatetimeUtil::DATE_FORMAT) }}">
                        </div>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label for="solar_altitude_time" class="col-form-label me-2 col-sm-2">時間帯</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control" name="solar_altitude_time" id="solar_altitude_time" value="{{ $simulationModel->solar_altitude_time }}">
                        </div>
                        <label for="solar_altitude_time" class="col-form-label px-2 col-sm-2">(時)</label>
                    </div>
                </div>
            </div>
        </details>

         {{-- 熱対策施策条件のエリア --}}
        <details class="border border-secondary border-2 mb-3 mt-3">
            <summary class="d-block">
                <p class="border-bottom border-secondary border-2 text-center p-2">熱対策施策条件</p>
            </summary>
            <div class="row px-2 mb-2 ms-3">
                <div class="col-sm-5">
                    <div class="d-flex flex-row">
                        <label class="col-form-label me-2 col-sm-2">施策</label>
                        <div class="col-sm-8">
                            <select class="form-select me-2" id="policy_id" name="policy_id">
                                <option value="0">未選択</option>
                                @foreach($policyList as $policy)
                                    <option value="{{ $policy->policy_id }}">{{ $policy->policy_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-flex flex-row mt-2">
                        <label class="col-form-label me-2 col-sm-2">対象</label>
                        <div class="col-sm-8">
                            <select class="form-select me-2" id="stl_type_id" name="stl_type_id">
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
                </div>
                <div class="col-sm-1">
                    <div class="d-flex flex-column mt-2">
                        <button type="button" class="btn btn-outline-secondary mb-2" id="ButtonAddSmPolicy" onclick="ajaxRequestAddNewSmPolicy()">追加→</button>
                        <button type="button" class="btn btn-outline-secondary" id="ButtonDeleteSmPolicy" onclick="ajaxRequestDeleteSmPolicy()">←削除</button>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="px-5 mx-4" id="smPoliciesTblDiv">
                        @include('simulation_model/partial_sm_policy.partial_sm_policy_list',
                            ['smPolicies' => $simulationModel->simulation_model_policies()->get(),
                             'simulationModelId' => $simulationModel->simulation_model_id
                            ])
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

        {{-- 隠し項目設定のエリア--}}
        <input type="hidden" name="south_latitude" id="south_latitude" value="{{ $simulationModel->south_latitude }}">{{-- 狭域指定で設定した南端緯度 --}}
        <input type="hidden" name="north_latitude" id="north_latitude" value="{{ $simulationModel->north_latitude }}">{{-- 狭域指定で設定した北端緯度 --}}
        <input type="hidden" name="west_longitude" id="west_longitude" value="{{ $simulationModel->west_longitude }}">{{-- 狭域指定で設定した西端経度 --}}
        <input type="hidden" name="east_longitude" id="east_longitude" value="{{ $simulationModel->east_longitude }}">{{-- 狭域指定で設定した東端経度 --}}
    </form>
</div>
@endsection

@section('js')
    <script src="{{ asset('/js/jquery-ui-1.13.2.min.js') }}"></script>
    <script src="{{ asset('/js/table.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script src="{{ asset('/js/leaflet-1.9.4.js') }}"></script>
    <script src="{{ asset('/js/leaflet.draw-1.0.4.js') }}"></script>
    <script src="{{ asset('/js/2d_map.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script>

        // 東西南北境界：全域
        const BOUNDARY_WHOLE_AREA  = "1";
        // 東西南北境界：狭域
        const BOUNDARY_NARROW_AREA = "2";
        // 長方形のサイズや位置を編集する前のRectangleのバウンズを保持する変数
        let originalBounds;
        // 2d地図
        // ※地図オブジェクトを適切に初期化しないと地図が描画されない可能性があるため、適当に東京を中心に表示する。
        let map = L.map('map').setView([35.689487, 139.691706], 13);

        // 全域の背景色
        const WHOLE_AREA_BG_COLOR = "#da70d6";
        // 狭域の背景色
        const NARROW_AREA_BG_COLOR = "#006400";

        $(function(){

            $("#solar_altitude_date_view").datepicker({
                dateFormat: "yy/mm/dd",
                changeYear: true, // 年の選択を有効にする
                changeMonth: true, // 月の選択を有効にする
                showOtherMonths: true, // 前後の月の日付も一緒に表示する
                onSelect: function(dateText, inst) {
                    const date = $(this).datepicker('getDate');
                    const day  = date.getDate().toString().padStart(2, "0"); // 日に0埋め
                    const month = (date.getMonth() + 1).toString().padStart(2, "0"); // 月に0埋め
                    const year =  date.getFullYear();
                    // 表示用の「日付」項目
                    $("#solar_altitude_date_view").val(month + '月' + day + '日');
                    // 保存処理用の「日付」項目
                    $("#solar_altitude_date").val(year + '/' + month + '/' + day);
                }
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

            // == 地図描画 ============================================
            @if ($simulationModel->isWholeArea())
                // 東西南北境界のデフォルトを「全域」にする。
                drawMap(BOUNDARY_WHOLE_AREA);
            @else
                // 東西南北境界のデフォルトを「狭域指定」にする。
                drawMap(BOUNDARY_NARROW_AREA), 3000;
            @endif
            // == 地図描画 //============================================
        });


        /**
         * 実施施策一覧の行を選択した後のイベント
         * @param mixed target 選択した行
         *
         * @return
         */
        function toggleTr(target)
        {
            // 行の背景色を設定
            if($(target).hasClass(TR_BACKGROUD_COLOR)) {
                removeBgTr(target);
            } else {
                resetBgTr('#tblSmPolicy');
                setBgTr(target);
            }
        }

        /**
         * 実施施策一覧に行を追加する
         * @return
         */
        function ajaxRequestAddNewSmPolicy()
        {
            // 実施施策一覧に行を追加
            const rqUrl = "{{ route('simulation_model.addnew_sm_policy') }}";

            // 対象と施策
            const rqData = {
                simulation_model_id: "{{ $simulationModel->simulation_model_id }}",
                stl_type_id: $("#stl_type_id").val(),
                policy_id: $("#policy_id").val()
            };
            ajaxRequest(rqUrl, rqData);
        }

        /**
         * 実施施策一覧より行を削除する
         * @return
         */
        function ajaxRequestDeleteSmPolicy()
        {
            // 実施施策一覧に行を追加
            const rqUrl = "{{ route('simulation_model.delete_sm_policy') }}";

            // 選択中の「対象」と「施策」を選択する。
            const stlTypeId = $("#tblSmPolicy tr.table-primary").find('input#smPolicyStlTypeId').val();
            const policyId = $("#tblSmPolicy tr.table-primary").find('input#smPolicyPolicyId').val();

            // 対象と施策
            const rqData = {
                simulation_model_id: "{{ $simulationModel->simulation_model_id }}",
                stl_type_id: stlTypeId,
                policy_id: policyId
            };
            ajaxRequest(rqUrl, rqData);
        }

        /**
         * 実施施策一覧を更新
         * @param mixed reponseData レスポンスのデータ
         *
         * @return
         */
        function updateSimulationModelPolicyTable(reponseData)
        {
            const smPoliciesTblHtml = reponseData['paritalViewSmPolicy'];

            if (smPoliciesTblHtml !== undefined)
            {
                // 実施施策一覧を更新
                $("#smPoliciesTblDiv").html(smPoliciesTblHtml);
            }
            else
            {
                // 「未選択」E2のエラーをメッセージダイアログにより表示
                // エラーメッセージダイアログを表示
                $("div#messageModal [class='modal-body']").html(
                    '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/error.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                $("div#messageModal [class='modal-footer']").html('<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');

                $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(reponseData['code']);
                $("div#messageModal [class='modal-body'] span#message").html(reponseData['msg']);
                $('#messageModal').modal('show');
            }
        }

        /**
         * Ajaxによりリクエストを送信する
         * @param mixed rqUrl リクエスト先
         * @param mixed rqData リクエストのデータ
         * @param string rqType リクエストのタイプ(POST or GET)
         *
         * @return
         */
        function ajaxRequest(rqUrl, rqData, rqType = 'POST')
        {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: rqUrl,
                type: rqType,
                data: rqData,
                success: function (response) {
                    updateSimulationModelPolicyTable(response);
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.log(xhr, textStatus, errorThrown);
                },
                complete: function(xhr, textStatus, errorThrown) {
                    // do nothing
                }
            });
        }


        /**
         * 長方形のサイズや位置変更に伴い、シミュレーションエリア(距離を含む)を更新する
         * @param mixed rectangleBounds サイズや位置を変更した長方形の範囲
         * @param Number eastAndWestDistance 東西境界間距離
         * @param Number northAndSouthDistance 南北境界間距離
         *
         * @return
         */
        function updateSimulationArea(rectangleBounds, eastAndWestDistance, northAndSouthDistance)
        {
            // 変更した長方形のサイズや位置を隠し項目に一時保存する。
            $("input[name='south_latitude']").val(rectangleBounds['_southWest'].lat); // 南端緯度
            $("input[name='north_latitude']").val(rectangleBounds['_northEast'].lat); // 北端緯度
            $("input[name='west_longitude']").val(rectangleBounds['_southWest'].lng); // 西端経度
            $("input[name='east_longitude']").val(rectangleBounds['_northEast'].lng); // 東端経度

            // =====距離更新============================
            // 東西境界間距離
            $('label#eastAndWestDistance').html(eastAndWestDistance);
            // 南北境界間距離
            $('label#northAndSouthDistance').html(northAndSouthDistance);
            // =====距離更新 //============================
        }

        /**
        * 長方形のサイズや位置変更時のイベント
        *
        * @param mixed map 全域の表示範囲
        * @param mixed wholeAreaBounds 全域の表示範囲
        * @param mixed subRectangle 狭域の表示範囲
        * @param mixed originalBounds サイズや位置を編集する前の長方形のバウンズ
        * @param Boolean fit_bounds  地図を四角形の境界にズームするかどうか
        * @param String color        方形の背景色
        * @param Boolean editable    長方形のサイズ変更許可用のフラグ
        *
        * @return
        */
        function changeRectangle(map, wholeAreaBounds, subRectangle, originalBounds, fit_bounds, color = "", editable = false)
        {
            subRectangle.on('edit', function () {

                // 長方形の現在の位置やサイズ
                let currentBounds = this.getBounds();

                // 東西境界間距離
                const eastAndWestDistance = haversineDistance(
                    currentBounds['_northEast'].lat, // 北端緯度
                    currentBounds['_southWest'].lng, // 西端経度
                    currentBounds['_northEast'].lat, // 北端緯度
                    currentBounds['_northEast'].lng); // 東端経度

                // 南北境界間距離
                const northAndSouthDistance = haversineDistance(
                    currentBounds['_northEast'].lat, // 北端緯度
                    currentBounds['_southWest'].lng, // 西端経度
                    currentBounds['_southWest'].lat, // 南端端緯度
                    currentBounds['_southWest'].lng); // 西端経度

                // 淡青色の長方形をはみ出すように広げたり、東西境界間距離および南北境界間距離が10m未満となるように狭めたりすることはできないように対応
                if ((!wholeAreaBounds.contains(currentBounds)) || ((eastAndWestDistance < 10) && (northAndSouthDistance < 10))) {
                    this.removeFrom(map);
                    let _newRectangle = drawRectangle(map, originalBounds, fit_bounds, color, editable);
                    changeRectangle(map, wholeAreaBounds, _newRectangle, originalBounds, fit_bounds, color, editable);
                } else {
                    originalBounds = currentBounds;

                    // 長方形のサイズや位置変更に伴い、シミュレーションエリア(距離を含む)を更新する
                    updateSimulationArea(originalBounds, eastAndWestDistance, northAndSouthDistance);
                }
            });
        }

        /**
         * 全域の地図を描画する
         * @param mixed map 地図
         * @param Number southernLatitude CA5南端緯度
         * @param Number northernLatitude CA6北端緯度
         * @param Number westernLongitude CA7西端経度
         * @param Number eastLongitude CA8東端経度
         */
        function drawWholeAreaMap(map, southernLatitude, northernLatitude, westernLongitude, eastLongitude)
        {
            // 全域の長方形描画
            let bounds = getBounds(southernLatitude, northernLatitude, westernLongitude, eastLongitude);
            drawRectangle(map, bounds, true, WHOLE_AREA_BG_COLOR);
            return bounds;
        }

        /**
         * 狭域指定の地図を描画する
         * @param mixed map 地図
         * @param mixed map wholeAreaBounds 全域の範囲
         * @param Number southernLatitude 狭域の南端緯度
         * @param Number northernLatitude 狭域の北端緯度
         * @param Number westernLongitude 狭域の西端経度
         * @param Number eastLongitude 狭域の東端経度
         * @param Boolean editable    長方形のサイズ変更許可用のフラグ
         */
        function drawNarrowAreaMap(map, wholeAreaBounds, subSouthernLatitude, subNorthernLatitude, subWesternLongitude, subEastLongitude, editable = false)
        {
            // 狭域の長方形描画
            let subBounds = getBounds(subSouthernLatitude, subNorthernLatitude, subWesternLongitude, subEastLongitude);
            let subRectangle = drawRectangle(map, subBounds, false, NARROW_AREA_BG_COLOR, editable);

            // 東西境界間距離
            const eastAndWestDistance = haversineDistance(
                subBounds['_northEast'].lat, // 北端緯度
                subBounds['_southWest'].lng, // 西端経度
                subBounds['_northEast'].lat, // 北端緯度
                subBounds['_northEast'].lng); // 東端経度

            // 南北境界間距離
            const northAndSouthDistance = haversineDistance(
                subBounds['_northEast'].lat, // 北端緯度
                subBounds['_southWest'].lng, // 西端経度
                subBounds['_southWest'].lat, // 南端端緯度
                subBounds['_southWest'].lng); // 西端経度

            // 狭域指定に伴い、シミュレーションエリア(距離を含む)を更新する
            updateSimulationArea(subBounds, eastAndWestDistance, northAndSouthDistance);

            //  長方形のサイズ変更許可される場合に限り、以下を行う
            if (editable) {
                // 長方形のサイズや位置を編集する前のRectangleのバウンズを保持する変数
                originalBounds = subBounds;
                // 長方形のサイズや位置変更時のイベント
                changeRectangle(map, wholeAreaBounds, subRectangle, originalBounds, false, NARROW_AREA_BG_COLOR, true);
            }
        }

        /**
         * 東西南北境界により地図を描画する。
         * @return
         */
        function drawMap(boundary = BOUNDARY_WHOLE_AREA)
        {

            // 地図描画のごとに地図をリフレッシュする。
            if (map != undefined) {
                map.off();
                map.remove();
            }

            // 淡青色の長方形（全域）描画用の座標。
            const southernLatitude  = Number("{{ $simulationModel->region->south_latitude }}");   // CA5南端緯度
            const northernLatitude  = Number("{{ $simulationModel->region->north_latitude }}");   // CA6北端緯度
            const westernLongitude  = Number("{{ $simulationModel->region->west_longitude }}");   // CA7西端経度
            const eastLongitude     = Number("{{ $simulationModel->region->east_longitude }}");   // CA8東端経度

            // 2d地図の初期化
            map = iniMap(southernLatitude, northernLatitude, westernLongitude, eastLongitude);

            // 全域の長方形の描画
            const wholeAreaBounds = drawWholeAreaMap(map, southernLatitude, northernLatitude, westernLongitude, eastLongitude);

            // 淡赤色の長方形（狭域）描画用の座標
            let subSouthernLatitude   = 0;
            let subNorthernLatitude   = 0;
            let subWesternLongitude   = 0;
            let subEastLongitude      = 0;

            // 長方形のサイズ変更許可用のフラグ
            let editable = true;

            // 東西南北境界が「全域」と選択された場合
            //  淡赤色の長方形(狭域)を淡青色の長方形(全域)とちょうど重なるようにします。
            if (boundary == BOUNDARY_WHOLE_AREA) {
                subSouthernLatitude   = southernLatitude;
                subNorthernLatitude   = northernLatitude;
                subWesternLongitude   = westernLongitude;
                subEastLongitude      = eastLongitude   ;

                editable = false;
            } else if (boundary == BOUNDARY_NARROW_AREA) {
                // 東西南北境界が「狭域指定」と選択された場合
                @if ($simulationModel->isWholeArea())

                    // ★シミュレーションモデルの東西南北端の座標が変更されていない場合
                    //  以下のように指定した狭い範囲で淡青色の長方形と淡赤色の長方形を重畳表示します。
                    //   ・下辺（南端）＝（CA5南端緯度×4＋CA6北端緯度）÷5
                    //   ・上辺（北端）＝（CA5南端緯度＋CA6北端緯度×4）÷5
                    //   ・左辺（西端）＝（CA7西端経度×4＋CA8東端経度）÷5
                    //   ・右辺（東端）＝（CA7西端経度＋CA8東端経度×4）÷5
                    subSouthernLatitude = (southernLatitude * 4 + northernLatitude) / 5;
                    subNorthernLatitude = (southernLatitude + northernLatitude * 4) / 5;
                    subWesternLongitude = (westernLongitude * 4 + eastLongitude) / 5;
                    subEastLongitude = (westernLongitude + eastLongitude * 4) / 5;
                @else
                    // ★シミュレーションモデルの東西南北端の座標が変更されている場合
                    //  前回で保存した変更した位置および大きさに基づいて淡青色の長方形と淡赤色の長方形を重畳表示します。
                    subSouthernLatitude = Number("{{ $simulationModel->south_latitude }}");   // SM13南端緯度
                    subNorthernLatitude = Number("{{ $simulationModel->north_latitude }}");   // SM14北端緯度
                    subWesternLongitude = Number("{{ $simulationModel->west_longitude }}");   // SM15西端経度
                    subEastLongitude    = Number("{{ $simulationModel->east_longitude }}");   // SM16東端経度
                @endif
            }

            // 狭域指定の地図
            drawNarrowAreaMap(map, wholeAreaBounds, subSouthernLatitude, subNorthernLatitude, subWesternLongitude, subEastLongitude, editable);
        }
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
