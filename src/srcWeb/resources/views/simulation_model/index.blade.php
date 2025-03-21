@extends('layouts.app')

@section('title', 'シミュレーションモデル一覧')

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_SIMULATION }}</span>
@endsection

@section('city-model-display-area')
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_BLANK }}</span>
@endsection

@section('content')
<div class="d-flex flex-column">
    {{-- ボタン配置のエリア --}}
<div class="button-area d-flex justify-content-between">
    <div>
        <button type="button" name="ButtonSimulationCreate" id="ButtonSimulationCreate" class="btn btn-outline-secondary" onclick="location.href='{{ route('simulation_model.create') }}'">新規作成</button>
        <button type="button" name="ButtonCopy" id="ButtonCopy" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.copy', ['id' => 0]) }}">複製</button>
        <button type="button" name="ButtonEdit" id="ButtonEdit" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.edit', ['id' => 0]) }}">編集</button>
        <button type="button" name="ButtonDelete" id="ButtonDelete" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.delete', ['id' => 0]) }}">削除</button>
    </div>
    <div>
        シミュレーション
        <button type="button" name="ButtonSimulationStart" id="ButtonSimulationStart" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.start', ['id' => 0]) }}">実行</button>
        <button type="button" name="ButtonSimulationStop" id="ButtonSimulationStop" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.stop', ['id' => 0]) }}">中止</button>
        <button type="button" name="ButtonSimulationResultShow" id="ButtonSimulationResultShow" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('simulation_model.show', ['id' => 0]) }}">結果閲覧</button>
        <button type="button" name="ButtonStatusDetail" id="ButtonStatusDetail" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.status_detail', ['id' => 0]) }}">詳細確認</button>
    </div>
    <div>
        <button type="button" name="ButtonShare" id="ButtonShare" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.share', ['id' => 0]) }}">共有</button>
        <button type="button" name="ButtonPublish" id="ButtonPublish" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.publish', ['id' => 0]) }}">公開</button>
        <button type="button" name="ButtonPublishStop" id="ButtonPublishStop" class="btn btn-outline-secondary button-href-with-id multi-line-not-support" data-href="{{ route('simulation_model.publish_stop', ['id' => 0]) }}">公開停止</button>
    </div>
</div>
    {{-- 一覧のエリア --}}
    <div class="list-area mt-2">
        <table class="table table-hover" id="tblSimulationModel">
            <thead>
                <tr>
                    <th scope="col">シミュレーションモデル名</th>
                    <th scope="col">3D都市モデル名</th>
                    <th scope="col">最終更新日時</th>
                    <th scope="col">最終実行開始日時</th>
                    <th scope="col">実行ステータス</th>
                    <th scope="col">登録ユーザ</th>
                    <th scope="col">共有ユーザ</th>
                    <th scope="col">公開</th>


                </tr>
            </thead>
            <tbody class="table-group-divider">
                @foreach($simulationModelList as $simulationModel)
                <tr>
                    <td class="d-none" id="hiddensimulationModelIdTd">{{ $simulationModel->simulation_model_id}}</td>
                    <td>{{ $simulationModel->identification_name }}</td>
                    <td>{{ $simulationModel->city_model->identification_name }}</td>
                    <td>{{ App\Utils\DatetimeUtil::changeFormat($simulationModel->last_update_datetime) }}</td>
                    <td>{{ $simulationModel->last_sim_start_datetime ? App\Utils\DatetimeUtil::changeFormat($simulationModel->last_sim_start_datetime) : "" }}</td>
                    <td class="{{ $simulationModel->setTableTdColorByRunStatus() }}">{{ $simulationModel->getRunStatusName() }}</td>
                    <td class="d-none" id="hiddenRegisteredUserIdTd">{{ $simulationModel->registered_user_id }}</td>
                    <td>{{ $simulationModel->user_account->display_name }}</td>
                    <td>{{ $simulationModel->getUpdateUser(App\Commons\Constants::SHARE_MODE_SIMULATION_MODEL, $simulationModel->simulation_model_id) }}</td>
                    <td>{{ $simulationModel->getPublishStatus() }}</td>
                </tr>
                @endforeach
            </tbody>
          </table>
    </div>
</div>
@endsection

{{-- 個別js --}}
@section('js')
    <script src="{{ asset('/js/table.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script>
        $(function(){

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

                    if (code == "W1")
                    {
                        // シミュレーションモデル削除ボタンでのW1
                        $("div#messageModal [class='modal-footer']").html(
                    '<button type="button" class="btn btn-outline-secondary" id="ButtonDeleteOK">OK</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>');
                    }
                    else if (code == "W3")
                    {
                        // シミュレーションモデル中止ボタンでのW3
                        $("div#messageModal [class='modal-footer']").html(
                    '<button type="button" class="btn btn-outline-secondary" id="ButtonStopOK">OK</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>');
                    }

                }
                else if (msg_type == "I")
                {
                    // 情報メッセージダイアログを表示
                    $("div#messageModal [class='modal-body']").html(
                        '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/info.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                    $("div#messageModal [class='modal-footer']").html(
                        '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');
                }
                else if (msg_type == "Q")
                {
                    // 質疑メッセージダイアログを表示
                    $("div#messageModal [class='modal-body']").html(
                        '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/question.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                    if (code == "Q1")
                    {
                        // シミュレーション開始ボタンでのQ1
                        $("div#messageModal [class='modal-footer']").html(
                            '<button type="button" class="btn btn-outline-secondary" id="ButtonStartYes">Yes</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No</button>');
                    }
                    else if (code == "Q2")
                    {
                        // ステータス詳細ボタンでのQ2
                        $("div#messageModal [class='modal-footer']").html(
                        '<button type="button" class="btn btn-outline-secondary" id="ButtonStatusDetailYes">Yes</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No</button>');
                    }
                }

                $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
                $("div#messageModal [class='modal-body'] span#message").html(msg);
                $('#messageModal').modal('show');
            @endif

            // テーブルの行を選択(最大2行選択可能とする。)
            $("#tblSimulationModel tr").click(function(){

                if (!isHeader(this)) {
                    // 選択した行に背景色が設定されているかどうかを確認
                    if(isSettingBgTr(this)) {
                        removeBgTr(this);
                        NUM_SELECTED_TR -= 1;

                        // 複数行が選択されない限り、「複製、編集、削除、実行、中止、詳細確認、共有、公開、公開中止」ボタンを有効にする。
                        if ($(".multi-line-not-support").prop("disabled")) {
                            $(".multi-line-not-support").prop("disabled", false);
                        }
                    } else {
                        // 最大2行まで選択可能とする
                        if (NUM_SELECTED_TR < MAX_SELECT_TR) {
                            // 行の背景色を設定
                            setBgTr(this);
                            NUM_SELECTED_TR += 1;

                            if (NUM_SELECTED_TR == MAX_SELECT_TR) {
                                // 複数行(最大2行まで)が選択されている状態になった場合、「結果閲覧」と「新規作成」ボタンのみ有効にする。
                                // つまり、「複製、編集、削除、実行、中止、詳細確認、共有、公開、公開中止」ボタンを無効にする。
                                $(".multi-line-not-support").prop("disabled", true);
                            }
                        }
                    }
                }
            });


            // 「複製」「編集」「削除」「実行」「中止」「結果閲覧」「詳細確認」「共有」「公開」「公開停止」ボタンは押下後
            $("button.button-href-with-id").click(function(){

                // シミュレーションモデルIDを取得
                let $currentSimulationModelId = "";
                const $hiddensimulationModelIdTd = $("#tblSimulationModel tr.table-primary").find('td#hiddensimulationModelIdTd');
                if ($hiddensimulationModelIdTd.length == 1) {
                    // 一覧で1行のみを選択した場合
                    $currentSimulationModelId = $("#tblSimulationModel tr.table-primary").find('td#hiddensimulationModelIdTd').html();
                } else {
                    // 一覧で複数行を選択した場合
                    $hiddensimulationModelIdArr = [];
                    $hiddensimulationModelIdTd.toArray().forEach(element => {
                        $hiddensimulationModelIdArr.push($(element).html());
                    });
                    $currentSimulationModelId = $hiddensimulationModelIdArr.toString();
                }

                // 登録ユーザを取得する。
                const $registeredUserId = $("#tblSimulationModel tr.table-primary").find('td#hiddenRegisteredUserIdTd').html();

                // 初期のdata-hrefを取得
                const $iniHref = $(this).data('href');

                if ($currentSimulationModelId) {
                    // 選択した行用のhrefを設定
                    $changeHref = $iniHref.replace(/.$/, $currentSimulationModelId); // 最後の文字列を置換

                    location.href =  $changeHref + "?registered_user_id=" + $registeredUserId;
                } else {
                    location.href =  $iniHref;
                }
            });

            // 削除確認ダイアログでOKボタンを押した
            $("div#messageModal [class='modal-footer'] button#ButtonDeleteOK").click(function(){
                const $simulationModelId = "{{ $simulationModelId }}";
                const $changeHref = $("button#ButtonDelete").data('href').replace(/.$/, $simulationModelId); // 最後の文字列を置換
                location.href =  $changeHref + "?delete_flg=1";
            });

            // シミュレーション中止するか質疑ダイアログでYesボタンを押した
            $("div#messageModal [class='modal-footer'] button#ButtonStartYes").click(function(){
                const $simulationModelId = "{{ $simulationModelId }}";
                const $changeHref = $("button#ButtonSimulationStart").data('href').replace(/.$/, $simulationModelId); // 最後の文字列を置換
                location.href =  $changeHref + "?stop_flg=1";
            });

            // シミュレーション中止するか警告ダイアログでOKボタンを押した
            $("div#messageModal [class='modal-footer'] button#ButtonStopOK").click(function(){
                const $simulationModelId = "{{ $simulationModelId }}";
                const $changeHref = $("button#ButtonSimulationStop").data('href').replace(/.$/, $simulationModelId); // 最後の文字列を置換
                location.href =  $changeHref + "?stop_flg=1";
            });

            // ステータス詳細ボタン -> 熱流体解析エラーログファイルをダウンロードするか質疑ダイアログでYesボタンを押した
            $("div#messageModal [class='modal-footer'] button#ButtonStatusDetailYes").click(function(){
                const $simulationModelId = "{{ $simulationModelId }}";
                const $changeHref = $("button#ButtonStatusDetail").data('href').replace(/.$/, $simulationModelId); // 最後の文字列を置換
                location.href =  $changeHref + "?download_flg=1";
            });


        });
    </script>
@endsection


{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection

