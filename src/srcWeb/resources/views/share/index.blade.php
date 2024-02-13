@extends('layouts.app')

@section('title', 'モデル共有')

@section('css')
<style type="">
    .bg-cyan {
        background-color: cyan;
    }
</style>
@endsection

@section('model-kind-display-area')
    @if ($shareMode == App\Commons\Constants::SHARE_MODE_CITY_MODEL)
        <span>{{ App\Commons\Constants::MODEL_KIND_CITY }}</span>
    @else
        <span>{{ App\Commons\Constants::MODEL_KIND_SIMULATION }}</span>
    @endif
@endsection

@section('city-model-display-area')
    <span>{{ $model->identification_name }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container">
    <form id="frmShareMode" method="POST" action="{{ route('share.addnew', ['share_mode' => $shareMode, 'model_id' => $modelId]) }}">
        {{ csrf_field() }}
        <div class="mb-3 row">
            <label for="identification_name" class="col-sm-2 col-form-label"></label>
            <div class="col-sm-5">
            </div>
            <div class="col-sm-2">
                @if ($shareMode == App\Commons\Constants::SHARE_MODE_CITY_MODEL)
                    <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('city_model.index') }}'">一覧に戻る</button>
                @else
                    <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('simulation_model.index') }}'">一覧に戻る</button>
                @endif
            </div>
        </div>
        <div class="mb-3 row">
            <label for="identification_name" class="col-sm-2 col-form-label">共有ユーザID</label>
            <div class="col-sm-5">
                <input type="text" pattern="^[a-zA-Z0-9]+$" class="form-control" name="identification_name" id="identification_name" value="">
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-secondary" id="ButtonShare">共有</button>
            </div>
        </div>
        <div class="mb-3 row">
            <label for="identification_name" class="col-sm-2 col-form-label">共有済ユーザ</label>
        </div>
        <div class="mb-3 row">
            {{-- <label for="identification_name" class="col-sm-2 col-form-label">モデル識別名</label> --}}
            <div class="col-sm-7">
                <div class="d-flex flex-column border p-2" style="height: 100px;" id = "sharedUser">
                @foreach($userList as $user)
                <span data-user-id="{{ $user->user_id }}" onclick="togglebgTr(this)">{{ $user->user_account->display_name }} ({{ $user->user_id }})</span>
                @endforeach
                </div>
            </div>
            <div class="col-sm-2">
                <button type="button" class="btn btn-outline-secondary" id="ButtonCancel">解除</button>
            </div>
        </div>
    </form>
</div>
@endsection

{{-- 個別js --}}
@section('js')
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

            // 共有確認ダイアログでOKボタンを押した
            $("div#messageModal [class='modal-footer'] button#ButtonOK").click(function(){
                let $frmAction = "";

                // form action 設定
                const $shareMode = "{{ $shareMode }}";
                const $modelId =  "{{ $modelId }}";
                const $userId = "{{ $userId }}";

                $frmAction = "{{ route('share.addnew', ['share_mode' => $shareMode, 'model_id' => $modelId ]) }}";

                // パラメーター設定
                $frmAction += '?store_flg=1'+ '&user_id=' + $userId;

                const frmShareMode = $('#frmShareMode');

                // フォームサブミット
                submitFrm(frmShareMode, $frmAction);
            });

            // 「解除」ボタンを押下
            $("#ButtonCancel").click(function(){

				// 選択した行を取得
                let $chooseRows = [];
                let $userIds = '';
                $("#sharedUser span.bg-cyan").each(function() {
                    $chooseRows.push($(this).data('user-id'));
                });
                // 複数ユーザIDをハイフン区切りにする
                $userIds += $chooseRows.join('-');

                let $frmAction = "";

                // form action 設定
                const $shareMode = "{{ $shareMode }}";
                const $modelId =  "{{ $modelId }}";

                $frmAction = "{{ route('share.delete', ['share_mode' => $shareMode, 'model_id' => $modelId ]) }}";

                // パラメーター設定
                $frmAction += '?store_flg=1'+ '&user_ids=' + $userIds;

                const frmShareMode = $('#frmShareMode');

                // フォームサブミット
                submitFrm(frmShareMode, $frmAction);


            });
        });

        /**
         *
         * フォームサブミット
         * @param mixed frmId フォームID
         * @param mixed action フォームのアクション
         *
         * @return
         */
        function submitFrm(frmId, action, method = 'POST')
        {
            $(frmId).attr('action', action);
            $(frmId).attr('method', method);
            $(frmId).submit();
        }

        /**
         * 一覧の背景色設定/解除
         *
         * @return
         */
        function togglebgTr(target)
        {
            if ($(target).hasClass('bg-cyan')) {
                    // 既に選択されている場合は背景色を解除
                    removebgColor(target);
                } else {
                    // 選択されていない場合は背景色を設定
                    setbgColor(target);
                }
        }

        /**
         * 一覧の背景色設定
         *
         * @return
         */
        function setbgColor(target)
        {
            // 背景色を設定
            $(target).addClass('bg-cyan');
        }

        /**
         * 一覧の背景色解除
         *
         * @return
         */
        function removebgColor(target) {
            // 背景色を解除
            $(target).removeClass('bg-cyan');
        }


    </script>
@endsection


{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection

