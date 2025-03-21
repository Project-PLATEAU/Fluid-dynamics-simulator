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
    <form id="frmSimulation" method="POST" action="{{ route('simulation_model.addnew', ['city_model_id' => 0]) }}">
        {{ csrf_field() }}
        <div class="row">
            <div class="col-sm-7">
                <div class="mb-3 row">
                    <label for="identification_name" class="col-sm-4 col-form-label">シミュレーションモデル名</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="identification_name" id="identification_name">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="city_model_id" class="col-sm-4 col-form-label">3D都市モデル</label>
                    <div class="col-sm-6">
                        <select class="form-select" name="city_model_id" id="city_model_id" onchange="updateRegions()">
                            <option value="0">未選択</option>
                            @foreach ($cityModelList as $cityModel)
                                <option value="{{ $cityModel->city_model_id }}">{{ $cityModel->identification_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="d-flex flex-column">
                    <label for="identification_name" class="col-form-label mb-2">解析対象地域選択</label>
                    <div class="form-control d-flex flex-column border" style="height: 250px; overflow: auto;" id="region-list">
                        <!-- 選択された3D都市モデルによって表示される -->
                    </div>
                </div>
                <div class="button-area mt-3 d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-secondary me-2" id="ButtonAddNew" onclick="submitFrmAddNewSimulation()">作成</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('simulation_model.index') }}'">キャンセル</button>
                </div>
            </div>
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
            const cityModelId = $('#city_model_id').val();
            let action = "{{ route('simulation_model.addnew', ['city_model_id' => ':city_model_id']) }}".replace(':city_model_id', cityModelId);

            let regionId = $("#region-list span.bg-cyan").data('region-id');
            if (regionId) {
                action += '?region_id=' + regionId;
            }

            submitFrm(frmId, action);
        }

        /**
         * 3D都市モデルより解析対象地域を取得
         * @return
         */
        function updateRegions()
        {
            const cityModelId = $('#city_model_id').val();
            $.ajax({
                url: '{{ route("city_model.getRegionsByCityModelId") }}',
                type: 'GET',
                data: { city_model_id: cityModelId },
                success: function(response) {
                    $('#region-list').html('');
                    response.regions.forEach(function(region) {
                        $('#region-list').append(
                            `<span class="region" data-region-id="${region.region_id}" onclick="setbgColor(this)">${region.region_name}</span>`
                        );
                    });
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        }
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
