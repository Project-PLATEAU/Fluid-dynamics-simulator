@extends('layouts.app')

@section('title', '熱流体解析ソルバ一覧')

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_SOLVER }}</span>
@endsection

@section('city-model-display-area')
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_BLANK }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container">
    <form id="frmSolver" method="POST" action="{{route('solver.addnew')}}" enctype="multipart/form-data">
        {{ csrf_field() }}
        <div class="mb-3 row">
            <label for="solver_name" class="col-sm-2 col-form-label">ソルバ識別名</label>
            <div class="col-sm-5">
                <input type="text" class="form-control" name="solver_name" id="solver_name">
            </div>
        </div>
        <div class="mb-3 row">
            <label for="" class="col-sm-2 col-form-label"></label>
            <div class="col-sm-5">
                <input class="form-control form-control-sm" id="solver_compressed_file" name="solver_compressed_file" type="file" accept=".tar">
            </div>
        </div>

        <div class="button-area mt-3 mb-3">
            <button type="submit" class="btn btn-outline-secondary">追加</button>
        </div>

        <div class="d-flex flex-column">
            {{-- ボタン配置のエリア --}}
            <div class="button-area">
                <button type="button" name="ButtonUpdate" id="ButtonUpdate" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('solver.update', ['id' => 0]) }}">更新</button>
                <button type="button" name="ButtonPublic" id="ButtonPublic" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('solver.public', ['id' => 0]) }}">公開</button>
                <button type="button" name="ButtonDelete" id="ButtonDelete" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('solver.delete', ['id' => 0]) }}">削除</button>
                <button type="button" name="ButtonDownload" id="ButtonDownload" class="btn btn-outline-secondary button-href-with-id" data-href="{{ route('solver.download', ['id' => 0]) }}">ダウンロード</button>
            </div>

            {{-- 一覧のエリア --}}
            <div class="list-area mt-2">
                <table class="table table-hover" id="tblSolver">
                    <thead>
                        <tr>
                            <th scope="col">識別名</th>
                            <th scope="col">公開状況</th>
                            <th scope="col">登録ユーザ</th>
                            <th scope="col">登録日時</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                        @foreach($solverList as $solver)
                        <tr>
                            <td class="d-none" id="hiddenSolverIdTd">{{ $solver->solver_id}}</td>
                            <td class="d-none" id="hiddenRegisteredUserIdTd">{{ $solver->user_id }}</td>
                            <td>{{ $solver->solver_name }}</td>
                            <td>{{ $solver->getPublicStatus() }}</td>
                            <td>{{ $solver->getUserName() }}</td>
                            <td>{{ $solver->upload_datetime ? App\Utils\DatetimeUtil::changeFormat($solver->upload_datetime) : "" }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

@endsection

{{-- 個別js --}}
@section('js')
    <script src="{{ asset('/js/table.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script>

        const solverForm = "#frmSolver";

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
                    $("div#messageModal [class='modal-footer']").html(
                        '<button type="button" class="btn btn-outline-secondary" id="ButtonOK">OK</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>');
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
                        // シミュレーションモデル開始ボタンでのQ1
                        $("div#messageModal [class='modal-footer']").html(
                            '<button type="button" class="btn btn-outline-secondary" id="ButtonStartYes">Yes</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>');
                    }
                    else if (code == "Q2")
                    {
                        // ステータス詳細ボタンでのQ2
                        $("div#messageModal [class='modal-footer']").html(
                        '<button type="button" class="btn btn-outline-secondary" id="ButtonStatusDetailYes">Yes</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>');
                    }
                }

                $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
                $("div#messageModal [class='modal-body'] span#message").html(msg);
                $('#messageModal').modal('show');
            @endif

            // テーブルの行を選択
            $("#tblSolver tr").click(function(){

                // 行の背景色を設定
                resetBgTr('#tblSolver');
                setBgTr(this);
            });

            // 「更新」「公開」「削除」「ダウンロード」ボタンは押下後
            $("button.button-href-with-id").click(function(){

                // ソルバIDを取得
                const $currentSolverId = $("#tblSolver tr.table-primary").find('td#hiddenSolverIdTd').html();

                // 登録ユーザを取得する。
                const $registeredUserId = $("#tblSolver tr.table-primary").find('td#hiddenRegisteredUserIdTd').html();

                // 初期のdata-hrefを取得
                const $iniHref = $(this).data('href');

                let $frmAction = "";

                if ($currentSolverId) {
                    // 選択した行用のhrefを設定
                    $changeHref = $iniHref.replace(/.$/, $currentSolverId); // 最後の文字列を置換

                    $frmAction = $changeHref + "?registered_user_id=" + $registeredUserId;
                } else {
                    $frmAction =  $iniHref;
                }
                // フォームサブミット
                submitFrm(solverForm, $frmAction);
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

    </script>
@endsection


{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection

