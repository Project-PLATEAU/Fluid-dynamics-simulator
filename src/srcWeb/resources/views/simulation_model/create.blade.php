@extends('layouts.app')

@section('title', 'シミュレーションモデル追加')

@section('css')
<style type="">
    .bg-cyan {
        background-color: cyan;
    }
</style>
@endsection

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_SIMULATION }}</span>
@endsection

@section('city-model-display-area')
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_ADD_NEW }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container">
    <form id="frmSimulation" method="POST" action="">
        {{ csrf_field() }}
        <div class="row">
            <div class="col-sm-7">
                <div class="mb-3 row">
                    <label for="identification_name" class="col-sm-2 col-form-label">3D都市モデル</label>
                    <div class="col-sm-8">
                        <label for="identification_name" class="col-form-label">{{ $cityModel->identification_name }}</label>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="identification_name" class="col-sm-2 col-form-label">モデル識別名</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="identification_name" id="identification_name">
                    </div>
                </div>
            </div>

             <div class="col-sm-5">
                <label for="identification_name" class="col-sm-8 col-form-label">解析対象地域</label>
                <div class="form-control d-flex flex-column border mx-4" style="height: 100px;" id="region-list">
                    @foreach($cityModel->regions()->get() as $region)
                        <span class="region" data-region-id="{{ $region->region_id }}" onclick="setbgColor(this)">{{ $region->region_name }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="button-area mt-5">
            <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('city_model.index') }}'">戻る</button>
            <button type="button" class="btn btn-outline-secondary" id="ButtonAddNew" onclick="submitFrmAddNewSimulation()">追加</button>

        </div>
    </form>
</div>
@endsection

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
        });

        /**
         * 一覧の背景色設定
         *
         * @return
         */
        function setbgColor(target)
        {
            const parent = $(target).parent();
            // 背景色をリセット
            parent.each(
                function(index){
                    $(parent[index].children).removeClass('bg-cyan');
                }
            );
            // 背景色を設定
            $(target).addClass('bg-cyan');
        }

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
         * 解析対象地域を削除
         * @return
         */
        function submitFrmAddNewSimulation()
        {
            const frmId = "#frmSimulation";
            let action = "{{route('simulation_model.addnew', ['city_model_id' => $cityModel->city_model_id])}}";

            //選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            if (regionId)
            {
                action += '?region_id=' + regionId;
            }

            // フォームサブミット
            submitFrm(frmId, action);
        }
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
