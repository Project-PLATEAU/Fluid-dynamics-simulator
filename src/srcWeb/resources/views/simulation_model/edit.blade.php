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
                <label class="col-sm-3 col-form-label">対象地域</label>
                <label class="col-sm-8 col-form-label">{{ $simulationModel->region->region_name }}</label>
            </div>
        </div>

        {{-- 境界条件のエリア --}}
        <details class="border border-secondary border-2 mb-3 mt-2">
            <summary class="d-block">
                <p class="border-bottom border-secondary border-2 text-center p-2">境界条件</p>
            </summary>
            <div class="row ms-3">
                <div class="col-sm-7">
                    <div class="row">
                        <label class="col-sm-2 col-form-label"></label>
                        <div class="col-sm-8 ms-2">
                            <a class="col-sm-2" href="https://www.google.com/maps/" target="blank">Google Map</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-1 ms-3">
                <div class="col-sm-7">
                    <div class="px-2 row">
                        <label class="col-sm-2 col-form-label">南,西</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="south_west" id="south_west" value="{{ $simulationModel->south_latitude . ', '. $simulationModel->west_longitude }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-1 ms-3">
                <div class="col-sm-7">
                    <div class="px-2 row">
                        <label class="col-sm-2 col-form-label">北,東</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="north_east" id="north_east" value="{{ $simulationModel->north_latitude . ', '. $simulationModel->east_longitude }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 ms-3">
                <div class="col-sm-7">
                    <div class="px-2 row">
                        <label class="col-sm-2 col-form-label">地面高度</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" name="ground_altitude" id="ground_altitude" value="{{ $simulationModel->ground_altitude }}">
                        </div>
                        <label class="col-sm-1 col-form-label">(m)</label>

                        <label class="col-sm-2 col-form-label">上空高度</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" name="sky_altitude" id="sky_altitude" value="{{ $simulationModel->sky_altitude }}">
                        </div>
                        <label class="col-sm-1 col-form-label">(m)</label>
                    </div>
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
    </form>
</div>
@endsection

@section('js')
    <script src="{{ asset('/js/jquery-ui-1.13.2.min.js') }}"></script>
    <script src="{{ asset('/js/table.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script>
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
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
