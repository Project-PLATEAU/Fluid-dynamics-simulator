@extends('layouts.app')

@section('title', 'シミュレーション結果閲覧')

@section('css')
<link href="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('/css/jquery-ui-1.13.2.min.css') }}">
<style>

    /* summaryの矢印(デフォルト)を非表示 */
    details.simulationRecreateDetails > summary {
        list-style: none;
    }
    details.simulationRecreateDetails summary::-webkit-details-marker {
        display:none;
    }

    /* スクロールカスタマイズ */
    .simulationRecreateScroll::-webkit-scrollbar {
        width: 10px;
    }

    .simulationRecreateScroll::-webkit-scrollbar-thumb {
        background-color: #989898;
    }

    .simulationRecreateScroll::-webkit-scrollbar-track {
        background-color: #323333;
    }

    .simulationRecreateScroll::-webkit-scrollbar-corner {
        background-color: #323333;
    }

    tbody tr:hover {
        background-color:#d1d2d3;
        color:#09090a !important;
    }

    .bg-color-a9ceec {
        background-color:#a9ceec;
    }

</style>
@endsection

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_SIMULATION }}</span>
@endsection

@section('city-model-display-area')
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_RESULT }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container-fluid">

    {{-- 選択したシミュレーション数によりレイアウトを変更する。 --}}
    @if (count($show_results) > 1)
        @include('simulation_model/partial_result_view.partial_result_view_many')
    @else
        @include('simulation_model/partial_result_view.partial_result_view_only_one')
    @endif

</div>
@endsection


{{-- 個別js --}}
@section('js')
    <script src="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Cesium.js"></script>
    <script src="{{ asset('/js/3d_map.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script src="{{ asset('/js/table.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script src="{{ asset('/js/jquery-ui-1.13.2.min.js') }}"></script>
    <script>

        const trBgColor = "bg-color-a9ceec";

        // 画面描画に必要な情報を取得する。
        const showResults = @json($show_results);

        // ビューアー宣言
        showResults.forEach((result, index) => {
            // 動的変数名を宣言・使用(windowを使用)
            window["viewer_" + index] = null;
        });

        $(function(){

            // 地図の高さを調整
            let customizeMapHeight = 0;
            if (showResults.length > 1) {
                // 地図並列表示
                customizeMapHeight = $(window).height() - 300;
            } else {
                // 1地図表示
                customizeMapHeight = $(window).height() - 260;
            }
            $('.3d-view-area').outerHeight(customizeMapHeight);
            $(window).resize(function () {
                $('.3d-view-area').outerHeight(customizeMapHeight);
            });

            // 3D地図描画
            showResults.forEach((result, index) => {
                let cesiumContainer = "cesiumContainer_" + index;
                // 「シミュレーションモデルテーブル.日付」、「シミュレーションモデルテーブル.時間帯」から取得した日付時刻で3D地図を表示するようにする
                const solarAltitudeDatetime = new Date($("#solarAltitudeDatetime_" + index).val());

                // ビューアーにカメラを設定する情報を取得する。
                viewerCamera = null;
                if (typeof result["viewer_camera"] !== 'undefined') {
                    viewerCamera = result["viewer_camera"]
                }

                window["viewer_" + index] = show3DMap(cesiumContainer, result["czml_files"], solarAltitudeDatetime, viewerCamera);

                // 「日付」項目
                $("#solar_altitude_date_view_" + index).datepicker({
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
                        $("#solar_altitude_date_view_" + index).val(month + '月' + day + '日');
                        // 保存処理用の「日付」項目
                        $("#solar_altitude_date_" + index).val(year + '/' + month + '/' + day);
                    }
                });
            });
        });

        /**
         * 表示モード切替(風況、温度、高さ) とダウンロードボタン押下後
         * @param mixed target
         * @param int html_type ラジオボタン：1; セレクトボックス: 2 ; ダウンロードボタン: 3;
         *
         * @return
         */
        function changeMode(target, html_type = 1)
        {
            // シミュレーションモデルIDの配列
            simulationModelIdArr = [];

            showResults.forEach((result, index) => {
                // 地図ごとの現在のカメラの方向、ピッチ、ポジションを維持
                $("#map_current_heading_" + index).val(window["viewer_" + index].camera.heading);
                $("#map_current_pitch_" + index).val(window["viewer_" + index].camera.pitch);
                $("#map_current_roll_" + index).val(window["viewer_" + index].camera.roll);
                $("#map_current_position_x_" + index).val(window["viewer_" + index].camera.position.clone().x);
                $("#map_current_position_y_" + index).val(window["viewer_" + index].camera.position.clone().y);
                $("#map_current_position_z_" + index).val(window["viewer_" + index].camera.position.clone().z);

                simulationModelIdArr.push(result['simulation_model']['simulation_model_id']);
            });

            // フォームID
            const smResultModeForm = "#frmSmResultMode";

            // フォームアクション
            let formAction = "";
            if (html_type != 3) {
                // 表示モード切替(風況、温度、高さ)
                formAction = "{{ route('simulation_model.change_mode', ['id' => ':simulation_model_id']) }}".replace(':simulation_model_id',simulationModelIdArr.toString());
            } else {
                // ダウンロードボタンを押下する。
                formAction = $(target).data('href');
            }

            submitFrm(smResultModeForm, formAction);
        }

        /**
         *
         * フォームサブミット
         * @param mixed frmId フォームID
         * @param mixed action フォームのアクション
         *
         * @return
         */
        function submitFrm(frmId, action, method = 'GET')
        {
            $(frmId).attr('action', action);
            $(frmId).attr('method', method);
            $(frmId).submit();
        }

        /**
         * 固定値利用可否チェックボックスにチェック入れ/外しにより凡例の最大値と最小値を更新
         * @param mixed target 固定値利用可否チェックボックス
         * @param int map_id 特定地図
         *
         * @return
         */
        function onchangeUpdateLegendValue(target, map_id)
        {
            // 固定値利用可否チェックボックスにチェックを入れたら、固定(2)にする
            // 固定値利用可否チェックボックスからチェックを外したら、変動(1)にする
            if (target.checked) {
                target.value = "2";  // チェックされた場合の値を設定
            } else {
                target.value = "1";  // チェックされていない場合の値を設定
            }

            changeMode(target);
        }

        /**
        * 凡例の最大値と最小値を更新
        * @param mixed target 固定値利用可否チェックボックス
        * @param string url 送信データ
        * @param json data 送信データ
        * @param number map_id 特定地図
        *
        * @return
        */
        function ajaxUpdateLegendValue(target, url, data, map_id)
        {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })

            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                dataType: 'json',
                success: function (response) {
                    let error = response['error'];
                    if (!error) {
                        // 凡例の最大値と最小値を更新
                        let legendLabelHigher = response['visualization']['legend_label_higher'];
                        let legendLabelLower = response['visualization']['legend_label_lower'];
                        $("#legend_label_higher_" + map_id).html(legendLabelHigher);
                        $("#legend_label_lower_" + map_id).html(legendLabelLower);
                    } else {
                        // E36のエラーをメッセージダイアログにより表示
                        // エラーメッセージダイアログを表示
                        $("div#messageModal [class='modal-body']").html(
                            '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/error.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                        $("div#messageModal [class='modal-footer']").html('<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');

                        $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(error['code']);
                        $("div#messageModal [class='modal-body'] span#message").html(error['msg']);
                        $('#messageModal').modal('show');

                        // 固定値利用可否チェックボックスから強制にチェックを外す。
                        $(target).prop("checked", false);

                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.log(xhr, textStatus, errorThrown);
                },
                complete: function(xhr, textStatus, errorThrown) {
                    // do nothing
                }
            })
        }

        /**
         * 一方のカメラ位置、ズームレベルをもう一方に適用し、両画面が同じカメラ位置、ズームレベルとする。
         * @param number src_map_id 同期元のマップ
         *
         * @return
         */
        function cameraSynchronization(src_map_id)
        {
            //  同期元のビューアー
            const src_viewer = window["viewer_" + src_map_id];

            showResults.forEach((result, index) => {
                // 一方のカメラ位置、ズームレベルをもう一方に適用し、両画面が同じカメラ位置、ズームレベルとする
                if (index != src_map_id) {
                    setCamera(window["viewer_" + index], src_viewer.camera.heading, src_viewer.camera.pitch, src_viewer.camera.roll, src_viewer.camera.position.clone().x, src_viewer.camera.position.clone().y, src_viewer.camera.position.clone().z)
                }
            });
        }

        /**
         * 実施施策一覧に行を追加する
         * @param string simulation_model_id シミュレーションモデルID
         * @param int map_id 特定地図
         * @return
         */
        function ajaxRequestAddNewSmPolicy(simulation_model_id, map_id = 0)
        {
            // 実施施策一覧に行を追加
            const rqUrl = "{{ route('simulation_model.addnew_sm_policy') }}";

            // 対象と施策
            const rqData = {
                simulation_model_id: simulation_model_id,
                stl_type_id: $("#stl_type_id_" + map_id).val(),
                policy_id: $("#policy_id_" + map_id).val(),
                map_id: map_id
            };
            ajaxUpdateSmPolicy(map_id, rqUrl, rqData);
        }

        /**
         * 実施施策一覧より行を削除する
         * @param string simulation_model_id シミュレーションモデルID
         * @param int map_id 特定地図
         * @return
         */
        function ajaxRequestDeleteSmPolicy(simulation_model_id , map_id = 0)
        {
            // 実施施策一覧に行を追加
            const rqUrl = "{{ route('simulation_model.delete_sm_policy') }}";

            // 選択中の「対象」と「施策」を選択する。
            const stlTypeId = $("#tblSmPolicy_" + map_id +" tr."+ trBgColor +"").find('input#smPolicyStlTypeId_'+ map_id +'').val();
            const policyId = $("#tblSmPolicy_" + map_id +" tr."+ trBgColor +"").find('input#smPolicyPolicyId_'+ map_id +'').val();

            // 対象と施策
            const rqData = {
                simulation_model_id: simulation_model_id,
                stl_type_id: stlTypeId,
                policy_id: policyId,
                map_id: map_id
            };
            ajaxUpdateSmPolicy(map_id, rqUrl, rqData);
        }

        /**
         * 実施施策一覧を更新
         * @param mixed reponseData レスポンスのデータ
         * @param int map_id 特定地図
         *
         * @return
         */
        function updateSimulationModelPolicyTable(reponseData, map_id = 0)
        {
            const smPoliciesTblHtml = reponseData['paritalViewSmPolicy'];

            if (smPoliciesTblHtml !== undefined)
            {
                // 実施施策一覧を更新
                $("#smPoliciesTblDiv_" + map_id).html(smPoliciesTblHtml);
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
         * Ajaxによりシミュレーションモデル実施施策更新のリクエストを送信する
         * @param int map_id 特定地図
         * @param mixed rqUrl リクエスト先
         * @param mixed rqData リクエストのデータ
         * @param string rqType リクエストのタイプ(POST or GET)
         *
         * @return
         */
        function ajaxUpdateSmPolicy(map_id = 0, rqUrl, rqData, rqType = 'POST')
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
                    updateSimulationModelPolicyTable(response, map_id);
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
         * 実施施策一覧の行を選択した後のイベント
         * @param mixed target 選択した行
         * @param int map_id 特定地図
         *
         * @return
         */
        function toggleTr(target, map_id = 0)
        {

            // 行の背景色を設定
            if($(target).hasClass(trBgColor)) {
                removeBgTr(target, trBgColor);
            } else {
                resetBgTr('#tblSmPolicy_' + map_id, trBgColor);
                setBgTr(target, trBgColor);
            }
        }

         /**
         * Ajaxによりシミュレーションモデル再作成のリクエストを送信する
         * @param int map_id 特定地図
         * @param mixed rqUrl リクエスト先
         * @param mixed rqData リクエストのデータ
         * @param string rqType リクエストのタイプ(POST or GET)
         *
         * @return
         */
        function ajaxRecreateSimulationModel(map_id = 0, rqUrl, rqData, rqType = 'POST')
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
                success: function (reponseData) {
                    if (!reponseData['error']) {
                        // シミュレーションモデルの再作成に成功した場合、シミュレーションモデル一覧画面に遷移する。
                        location.href = reponseData['redirect'];
                    } else {
                        // シミュレーションモデルの再作成に異常があった場合、エラーメッセージダイアログを表示
                        $("div#messageModal [class='modal-body']").html(
                            '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/error.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                        $("div#messageModal [class='modal-footer']").html('<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');

                        $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(reponseData['error']['code']);
                        $("div#messageModal [class='modal-body'] span#message").html(reponseData['error']['msg']);
                        $('#messageModal').modal('show');
                    }
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
         * シミュレーションモデル再作成用の保存ボタン押下
         * @param string simulation_model_id_src 作成元のシミュレーションモデルID
         * @param int map_id 特定地図
         *
         * @return
         */
        function onclickRecreateSimulationModel(simulation_model_id_src, map_id = 0)
        {
            // シミュレーションモデル再作成
            const rqUrl = "{{ route('simulation_model.recreate') }}";

            const identificationName = $("#identification_name_" + map_id).val();
            const cityModelId = $("#city_model_id_" + map_id).val();
            const regionId = $("#region_id_" + map_id).val();
            const solarAltitudeDate = $("#solar_altitude_date_" + map_id).val();
            const solarAltitudeTime = $("#solar_altitude_time_" + map_id).val();
            const temperature = $("#temperature_" + map_id).val();
            const windSpeed = $("#wind_speed_" + map_id).val();
            const humidity = $("#humidity_" + map_id).val();
            const windDirection = $("#wind_direction_" + map_id).val();

            const simulationModelPolicy = [];
            $('input[name^="simulationModelPolicy_'+ map_id +'["]').each(function() {
                let name = $(this).attr('name');
                let regex = new RegExp(`^simulationModelPolicy_${map_id}\\[(\\d+)\\]\\[(\\w+)\\]$`);
                let match = name.match(regex);

                if (match) {
                    let index = match[1];
                    let key = match[2];

                    if (!simulationModelPolicy[index]) {
                        simulationModelPolicy[index] = {};
                    }
                    simulationModelPolicy[index][key] = $(this).val();
                }
            });

            const isStart = $("input[name='isStart_"+ map_id +"']:checked").val();

            // シミュレーションモデル再作成用のデータ
            const rqData = {
                simulation_model_id_src: simulation_model_id_src,
                identification_name: identificationName,
                city_model_id: cityModelId,
                region_id: regionId,
                solar_altitude_date: solarAltitudeDate,
                solar_altitude_time: solarAltitudeTime,
                temperature: temperature,
                wind_speed: windSpeed,
                humidity: humidity,
                wind_direction: windDirection,
                simulationModelPolicy: simulationModelPolicy,
                isStart: isStart,
                map_id: map_id
            };

            // ajaxリクエスト
            ajaxRecreateSimulationModel(map_id, rqUrl, rqData)
        }

        /**
         * シミュレーション再実行のキャンセル
         * @param int map_id 特定地図
         *
         * @return
         */
        function cancel(map_id)
        {
            $("#simulationRecreateDetails_"+ map_id +"").prop("open", false);
        }

    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
