@extends('layouts.app')

@section('title', '3D都市モデル付帯情報編集')

@section('css')
<style type="">
    .bg-cyan {
        background-color: cyan
    }
</style>
@endsection

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_CITY }}</span>
@endsection

@section('city-model-display-area')
<span>{{ $cityModel->identification_name }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container">
    <form method="POST" action="{{ request()->fullUrl() . '&update_mode=1' }}">
        {{ csrf_field() }}
        <div class="mb-3 row">
            <label for="identification_name" class="col-sm-2 col-form-label">モデル識別名</label>
            <div class="col-sm-5">
                <input type="text" class="form-control" name="identification_name" id="identification_name" value="{{ $cityModel->identification_name }}">
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-secondary" id="ButtonUpdateName">保存</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ request()->fullUrl() . '&update_mode=2' }}">
        {{ csrf_field() }}
        <div class="mb-3 row">
            <label for="identification_name" class="col-sm-2 col-form-label">3D Tiles</label>
            <div class="col-sm-5">
                <select class="form-select mx-1" id="_3dtiles" name="_3dtiles">
                    @foreach ($_3dTilesOptions as $index => $_3dTilesOption)
                        <option value="{{ $index }}" @if($_3dTilesOption['url'] == $cityModel->url) selected @endif>{{ $_3dTilesOption['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-secondary" id="ButtonUpdateTile">保存</button>
            </div>
        </div>
    </form>



    <form id="frmRegion" method="POST" action="">
        {{ csrf_field() }}
        <div class="mb-3 row mt-5">
            <label for="identification_name" class="col-sm-2 col-form-label">解析対象地域</label>
            <div class="col-sm-3">
                <div id="region-list" class="d-flex flex-column border p-2" style="height: 130px;">
                    @foreach($cityModel->regions()->get() as $region)
                    <span class="region" data-region-id="{{ $region->region_id }}" onclick="selectRegion(this)">{{ $region->region_name }}</span>
                    @endforeach
                </div>
            </div>
            <div class="col-sm-1">
                <button type="button" class="btn btn-outline-secondary" id="ButtonDeleteRegion" onclick="submitFrmDeleteRegion()">削除</button>
            </div>
            <div class="col-sm-5">
                <div class="d-flex flex-column">
                    <div class="d-flex flex-row">
                        <label for="region_name" class="col-form-label col-sm-4">対象地域識別名</label>
                        <input type="text" class="form-control" name="region_name" id="region_name" value="">
                    </div>

                    <div class="d-flex flex-row mt-1">
                        <label for="identification_name" class="col-form-label col-sm-4">平面角直角座標系</label>
                        <select class="form-select" id="coordinate_id" name="coordinate_id">
                            <option value="0">未選択</option>
                            @foreach ($coordinateOptions as $coordinate)
                                <option value="{{ $coordinate->coordinate_id }}">{{ $coordinate->coordinate_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-1">
                        <button type="button" class="btn btn-outline-secondary" id="ButtonAddRegion" style="float: right" onclick="submitFrmAddRegion()">追加</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id ="frmStl"  method="POST" action="" enctype="multipart/form-data">
        {{ csrf_field() }}
        <div class="mb-3 mt-4 row">
            <label for="" class="col-sm-2 col-form-label">STLファイル</label>
        </div>
        <div class="mb-3 row">
            <label for="" class="col-sm-2 col-form-label"></label>
            <div class="col-sm-5">
                <input class="form-control form-control-sm" id="stl_file" name="stl_file" type="file" accept=".stl,.obj">
                <div class="row mt-1">
                    <div class="col-sm-3"><label for="stl_type_id" class="col-form-label me-2">種類</label></div>
                    <div class="col-sm-9">
                        <select class="form-select me-2" style="width: 53%;" id="stl_type_id" name="stl_type_id" onchange="onchangeStlType(this)">
                            @foreach ($stlTypeOptions as $stlType)
                            <option value="{{ $stlType->stl_type_id }}">{{ $stlType->stl_type_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-sm-3"><label for="solar_absorptivity" class="col-form-label me-2">日射吸収率</label></div>
                    <div class="col-sm-5"><input type="text" class="form-control form-control-sm" id="solar_absorptivity" name="solar_absorptivity"></div>
                    <div class="col-sm-4"><label for="solar_absorptivity" class="col-form-label me-2 float-right">(0以上1以下の実数)</label></div>
                </div>
                <div class="row mt-1">
                    <div class="col-sm-3"><label for="heat_removal" class="col-form-label me-2">排熱量初期値</label></div>
                    <div class="col-sm-5"><input type="text" class="form-control form-control-sm" id="heat_removal" name="heat_removal"></div>
                    <div class="col-sm-4"><label for="heat_removal" class="col-form-label me-2">(W/m2)</label></div>
                </div>
                <div class="row">
                    <div class="col">
                        <button type="button" class="btn btn-outline-secondary mt-2 float-end" id="ButtonUploadStl" onclick="submitFrmUploadStl()">アップロード</button>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-control d-flex flex-column border" style="height: 210px;" id="stlFileListArea">
                    {{-- 解析対象地域により、更新される。 --}}
                </div>
            </div>
            <div class="col-sm-2">
                <button type="button" class="btn btn-outline-secondary" id="ButtonDeleteStlFile" onclick="submitFrmDeleteStlFile()">削除</button>
            </div>
        </div>

        <div class="mb-3 mt-4 row">
            <label for="" class="col-sm-2 col-form-label">STL定義上下限</label>
        </div>
        <div class="mb-3 row">
            {{-- 解析対象地域により、更新される。 --}}
            <div class="col-sm-10">
                <div class="row" id="stlDefinitionArea">
                    @include('city_model/partial_stl.parital_stl_definition', ['region' => null])
                </div>
            </div>

            <div class="col-sm-2">
                <button type="button" class="btn btn-outline-secondary" id="ButtonUpdateRegion" onclick="submitFrmUpdateRegion()">保存</button>
            </div>
        </div>
    </form>

    <div class="button-area mt-1 mb-3">
        <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('city_model.index') }}'">戻る</button>
    </div>
</div>
@endsection

@section('js')
    <script>
        $(function(){
            @if ($regionId)
                // STLファイルアップロード・削除後に時解析対象地域の行選択状態を維持するようにします。
                const regionId = "{{ $regionId }}";
                selectRegion($("#region-list span.region[data-region-id='" + regionId +"']"));
            @endif

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
                        '<button type="button" class="btn btn-outline-secondary" id="{{ $stlTypeId ? "ButtonDelStlOK" : "ButtonDelRegionOK"  }}">OK</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>');

                    // 解析対象地域を削除確認ダイアログでOKボタンを押した
                    $("div#messageModal [class='modal-footer'] button#ButtonDelRegionOK").click(function() {
                        const frmId = "#frmRegion";
                        const iniUrl = "{{ route('region.delete', ['city_model_id' => $cityModel->city_model_id, 'region_id' => isset($regionId) ? $regionId : 0]) }}";
                        const delAction =  iniUrl + "?registered_user_id=" + "{{ $registeredUserId }}" + "&delete_flg=1";
                        // フォームサブミット
                        submitFrm(frmId, delAction);
                    });

                    // STLファイルを削除確認ダイアログでOKボタンを押した
                    $("div#messageModal [class='modal-footer'] button#ButtonDelStlOK").click(function() {
                        const frmId = "#frmStl";
                        const iniUrl = "{{ route('region.delete_stl_file', ['city_model_id' => $cityModel->city_model_id, 'region_id' => isset($regionId) ? $regionId : 0]) }}";
                        const delAction =  iniUrl + "?registered_user_id=" + "{{ $registeredUserId }}" + "&stl_type_id=" + "{{ $stlTypeId }}" + "&delete_flg=1";
                        // フォームサブミット
                        submitFrm(frmId, delAction);
                    });
                }

                $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
                $("div#messageModal [class='modal-body'] span#message").html(msg);
                $('#messageModal').modal('show');
            @endif



            // 初期のSTLファイル種別により、日射吸収率と排熱量の値を表示します。
            ajaxUpdateStlType($("#stl_type_id").val());
        });


        /**
         * 解析対象地域を選択するイベント
         * @param mixed target
         *
         * @return
         */
        function selectRegion(target)
        {
            setbgColor(target);

            ajaxUpdateStlInfo();
        }

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
        function submitFrmDeleteRegion()
        {
            const frmId = "#frmRegion";
            let iniUrl = "{{ route('region.delete', ['city_model_id' => $cityModel->city_model_id, 'region_id' => 0]) }}";

            //選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            let action = "";
            if (regionId)
            {
                // 最後の文字[]を置換
                action = iniUrl.replace(/.$/, regionId);
            }
            else
            {
                action = iniUrl;
            }

            // パラメータ設定
            action += '?registered_user_id=' + '{{ $registeredUserId }}';

            // フォームサブミット
            submitFrm(frmId, action);
        }

        /**
         * 解析対象地域を追加
         * @return
         */
        function submitFrmAddRegion()
        {
            const frmId = "#frmRegion";
            let action = "{{ route('region.addnew', ['city_model_id' => $cityModel->city_model_id]) }}";

            // パラメータ設定
            action += '?registered_user_id=' + '{{ $registeredUserId }}';

            // フォームサブミット
            submitFrm(frmId, action);
        }

        /**
         * 解析対象地域を追加
         * @return
         */
        function submitFrmUploadStl()
        {
            const frmId = "#frmStl";
            let action = "{{route('region.upload_stl', ['city_model_id' => $cityModel->city_model_id, 'region_id' => 0])}}";

            // 選択した解析対象地域を取得
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            if (regionId) {
                action = action.replace(/.$/, regionId); // 最後の文字列を置換
            }

            // パラメータ設定
            action += '?registered_user_id=' + '{{ $registeredUserId }}';
            // フォームサブミット
            submitFrm(frmId, action);
        }


        /**
        * STL情報更新のリクエスト
        *
        * @return
        */
        function ajaxUpdateStlInfo()
        {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })

            let url = "{{ route('region.update_stl_info', ['region_id' => 0]) }}";
            // 選択した解析対象地域を取得
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            if (regionId) {
                url = url.replace(/.$/, regionId); // 最後の文字列を置換
            }

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                success: function (response) {

                    // STLファイル一覧を更新する。
                    let paritalViewStlFile = response['paritalViewStlFile'];
                    updateStlFileList(paritalViewStlFile);

                    // STL定義上下限を更新する。
                    let paritalViewStlDefinition = response['paritalViewStlDefinition'];
                    updateStlDefination(paritalViewStlDefinition);
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
        * 選択した解析対象地域により、STLファイル一覧を更新する。
        * @param string html
        *
        * @return
        */
        function updateStlFileList(html)
        {
            $('#stlFileListArea').html('');
            $('#stlFileListArea').html(html);
        }

        /**
        * 選択した解析対象地域により、STL定義上下限を更新する。
        * @param string html
        *
        * @return
        */
        function updateStlDefination(html)
        {
            $('#stlDefinitionArea').html('');
            $('#stlDefinitionArea').html(html);
        }

        /**
        * Stlファイルを削除
        * @return
        */
        function submitFrmDeleteStlFile()
        {
            const frmId = "#frmStl";
            let iniUrl = "{{ route('region.delete_stl_file', ['city_model_id' => $cityModel->city_model_id, 'region_id' => 0]) }}";

            //選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            let action = "";
            if (regionId)
            {
                // 最後の文字を置換
                action = iniUrl.replace(/.$/, regionId);
            }
            else
            {
                action = iniUrl;
            }

            // STLファイル種別ID
            let stlTypeId = $("#stlFileListArea span.bg-cyan").data('stl-type-id');
            stlTypeId = stlTypeId ? stlTypeId : 0;
            // パラメータ設定
            action += '?registered_user_id=' + '{{ $registeredUserId }}' + '&stl_type_id=' + stlTypeId;

            // フォームサブミット
            submitFrm(frmId, action);
        }

        /**
        * 解析対象地域を更新
        * @return
        */
        function submitFrmUpdateRegion()
        {
            const frmId = "#frmStl";
            let iniUrl = "{{ route('region.update', ['city_model_id' => $cityModel->city_model_id, 'region_id' => 0]) }}";

            //選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            let action = "";
            if (regionId)
            {
                // 最後の文字を置換
                action = iniUrl.replace(/.$/, regionId);
            }
            else
            {
                action = iniUrl;
            }

            // パラメータ設定
            action += '?registered_user_id=' + '{{ $registeredUserId }}';

            // フォームサブミット
            submitFrm(frmId, action);
        }

        /**
        * STLファイル種類のonchangeイベント
        * @return
        */
        function onchangeStlType(target)
        {
            // ajax処理で特定のSTLファイル種別情報を更新
            const stlTypeId = $(target).val();
            ajaxUpdateStlType(stlTypeId);
        }

        /**
        * 特定のSTLファイル種別を更新
        * @param integer $stl_type_id $stl_type_id STLファイル種別ID
        *
        * @return
        */
        function ajaxUpdateStlType($stl_type_id)
        {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })

            let url = "{{ route('stl_type.change') }}";
            $.ajax({
                url: url,
                type: 'POST',
                data: {stl_type_id: $stl_type_id},
                dataType: 'json',
                success: function (response) {

                    // レスポンスした特定のSTLファイル種別情報
                    const stl_type = response['stl_type'];

                    // 特定のSTLファイル種別の日射吸収率と排熱量を更新
                    $('#solar_absorptivity').val(stl_type['solar_absorptivity']);
                    $('#heat_removal').val(stl_type['heat_removal']);
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
