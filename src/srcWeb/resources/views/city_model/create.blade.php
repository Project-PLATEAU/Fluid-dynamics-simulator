@extends('layouts.app')

@section('title', '3D都市モデル追加')

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_CITY }}</span>
@endsection

@section('city-model-display-area')
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_ADD_NEW }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container">
    <form method="POST" action="{{route('city_model.addnew')}}">
        {{ csrf_field() }}
        <div class="mb-3 row">
            <label for="identification_name" class="col-sm-2 col-form-label">3D都市モデル名</label>
            <div class="col-sm-5">
                <input type="text" class="form-control" name="identification_name" id="identification_name">
            </div>
        </div>
        <div class="mb-3 row">
            <label for="identification_name" class="col-sm-2 col-form-label">3D Tiles</label>
            <div class="col-sm-5">
                <select class="form-select mx-1" id="_3dtiles" name="_3dtiles">
                    @foreach ($_3dTilesOptions as $index => $_3dTilesOption)
                        <option value="{{ $index }}" >{{ $_3dTilesOption['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-5">
            <div class="button-area me-5">
                <button type="submit" class="btn btn-outline-secondary">登録</button>
                <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('city_model.index') }}'">キャンセル</button>
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
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
