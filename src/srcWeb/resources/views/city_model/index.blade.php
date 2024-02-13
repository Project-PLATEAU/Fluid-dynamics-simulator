@extends('layouts.app')

@section('title', '3D都市モデル一覧')

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_CITY }}</span>
@endsection

@section('city-model-display-area')
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_BLANK }}</span>
@endsection

@section('content')
<div class="d-flex flex-column">
    {{-- ボタン配置のエリア --}}
    <div class="button-area">
        <button type="button" name="ButtonCreate" id="ButtonCreate" class="btn btn-outline-secondary" data-href="{{ route('city_model.create') }}" onclick="location.href='{{ route('city_model.create') }}'">追加</button>
        <button type="button" name="ButtonShow" id="ButtonShow" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('city_model.show',['id' => 0]) }}">閲覧</button>
        <button type="button" name="ButtonEdit" id="ButtonEdit" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('city_model.edit',['id' => 0]) }}">編集</button>
        <button type="button" name="ButtonDelete" id="ButtonDelete" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('city_model.delete',['id' => 0]) }}">削除</button>
        <button type="button" name="ButtonShare" id="ButtonShare" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('city_model.share', ['id' => 0]) }}">共有</button>
        <button type="button" name="ButtonSimulationCreate" id="ButtonSimulationCreate" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('city_model.simulationCreate', ['id' => 0]) }}">シミュレーションモデル作成</button>
    </div>

    {{-- 一覧のエリア --}}
    <div class="list-area mt-2">
        <table class="table table-hover" id="tblCityModel">
            <thead>
                <tr>
                    <th scope="col">3D都市モデル名</th>
                    <th scope="col">最終更新日時</th>
                    <th scope="col">登録ユーザ</th>
                    <th scope="col">共有ユーザ</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                @foreach($cityModelList as $cityModel)
                <tr>
                    <td class="d-none" id="hiddenCityModelIdTd">{{ $cityModel->city_model_id }}</td>
                    <td>{{ $cityModel->identification_name }}</td>
                    <td>{{ App\Utils\DatetimeUtil::changeFormat($cityModel->last_update_datetime) }}</td>
                    <td class="d-none" id="hiddenRegisteredUserIdTd">{{ $cityModel->registered_user_id }}</td>
                    <td>{{ $cityModel->user_account->display_name }}</td>
                    <td>{{ $cityModel->getUpdateUser(App\Commons\Constants::SHARE_MODE_CITY_MODEL, $cityModel->city_model_id) }}</td>
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
                const msg = "{{ $message['msg'] }}";

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

                $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
                $("div#messageModal [class='modal-body'] span#message").html(msg);
                $('#messageModal').modal('show');
            @endif

            // テーブルの行を選択
            $("#tblCityModel tr").click(function(){

                // 行の背景色を設定
                resetBgTr('#tblCityModel');
                setBgTr(this);
            });


            // 「閲覧」「編集」「削除」ボタンは押下後
            $("button.button-href-with-id").click(function(){

                // 3D都市モデルIDを取得
                const $currentCityModelId = $("#tblCityModel tr.table-primary").find('td#hiddenCityModelIdTd').html();


                // 登録ユーザを取得する。
                const $registeredUserId = $("#tblCityModel tr.table-primary").find('td#hiddenRegisteredUserIdTd').html();

                // 初期のdata-hrefを取得
                const $iniHref = $(this).data('href');

                if ($currentCityModelId) {
                    // 選択した行用のhrefを設定
                    $changeHref = $iniHref.replace(/.$/, $currentCityModelId); // 最後の文字列を置換

                    location.href =  $changeHref + "?registered_user_id=" + $registeredUserId;
                } else {
                    location.href =  $iniHref;
                }
            });

            // 削除確認ダイアログでOKボタンを押した
            $("div#messageModal [class='modal-footer'] button#ButtonOK").click(function(){
                const $cityModelId = "{{ $cityModelId }}";
                const $changeHref = $("button#ButtonDelete").data('href').replace(/.$/, $cityModelId); // 最後の文字列を置換
                location.href =  $changeHref + "?delete_flg=1";
            });
        });
    </script>
@endsection


{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection

