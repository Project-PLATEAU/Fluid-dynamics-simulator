@extends('layouts.app')

@section('title', '3D都市モデル付帯情報編集')

@section('css')
<link href="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
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
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_EDIT }}</span>
@endsection

@section('content')
<div class="d-flex flex-column container">
    <form id="formCitymodel" method="POST" action="{{ request()->fullUrl() }}">
        {{ csrf_field() }}
        <div class="row mb-4">
            <div class="col-sm-6">
                <div class="row">
                    <label for="identification_name" class="col-sm-4 col-form-label">3D都市モデル名</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="identification_name" id="identification_name" value="{{ $cityModel->identification_name }}">
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="row">
                    <label for="_3dtiles" class="col-sm-4 col-form-label">3D Tiles</label>
                    <div class="col-sm-6">
                        <select class="form-select" id="_3dtiles" name="_3dtiles">
                            @foreach ($_3dTilesOptions as $index => $_3dTilesOption)
                                <option value="{{ $index }}" @if($_3dTilesOption['url'] == $cityModel->url) selected @endif>{{ $_3dTilesOption['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id="frmRegion" method="POST" action="">
        {{ csrf_field() }}
        <div class="row mb-4">
            <label class="col-sm-2 col-form-label">解析対象地域</label>
            <div class="col-sm-3">
                <p class="text-muted mb-1 small text-nowrap">
                    <span class="d-inline-block text-truncate small">
                    下記リストから地域の選択または削除が可能です。新規追加は右側で実施してください。
                    </span>
                </p>
                <div id="region-list" class="d-flex flex-column border p-2 rounded" style="height: 130px; overflow: hidden; overflow-y:auto;">
                    @foreach($cityModel->regions()->get() as $region)
                    <span class="region" data-region-id="{{ $region->region_id }}" onclick="selectRegion(this, true)" data-user-id="{{ $region->user_account->user_id }}">{{ $region->region_name }} ({{ $region->user_account->display_name }})</span>
                    @endforeach
                </div>
            </div>
            <div class="col-sm-1 d-flex align-items-end">
                <div class="d-flex flex-column">
                    <button type="button" class="btn btn-outline-secondary mb-1" id="ButtonCopyRegion" onclick="submitFrmCopyRegion()">複製</button>
                    <button type="button" class="btn btn-outline-secondary" id="ButtonDeleteRegion" onclick="submitFrmDeleteRegion()">削除</button>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="row mb-2">
                    <label for="region_name" class="col-sm-4 col-form-label">解析対象地域名</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="region_name" id="region_name" value="">
                    </div>
                </div>
                <div class="row mb-2">
                    <label for="coordinate_id" class="col-sm-4 col-form-label">平面角直角座標系</label>
                    <div class="col-sm-6">
                        <select class="form-select" id="coordinate_id" name="coordinate_id">
                            <option value="0">未選択</option>
                            @foreach ($coordinateOptions as $coordinate)
                                <option value="{{ $coordinate->coordinate_id }}">{{ $coordinate->coordinate_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-10 d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" id="ButtonAddRegion" onclick="submitFrmAddRegion()">追加</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id="frmStl" method="POST" action="" enctype="multipart/form-data">
        {{ csrf_field() }}
        <div class="row mb-4">
            <label class="col-sm-2 col-form-label">解析対象地域上下限</label>
            <div class="col-sm-10">
                <div class="row" id="stlDefinitionArea">
                    @include('city_model/partial_stl.parital_stl_definition', ['region' => null])
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <label class="col-sm-2 col-form-label">STLファイル</label>
            <div class="col-sm-10">
                <div class="row">
                    <div class="col-sm-4">
                        <p class="text-muted mb-1 small text-nowrap">
                            <span class="d-inline-block text-truncate small">
                            下記リストからファイルの削除が可能です。新規追加は右側で実施してください。
                            </span>
                        </p>
                        <div class="form-control d-flex flex-column border overflow-auto" style="height: 180px;" id="stlFileListArea">
                            {{-- 解析対象地域により、更新される。 --}}
                        </div>
                    </div>
                    <div class="col-sm-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary edit-disabled" id="ButtonDeleteStlFile" onclick="submitFrmDeleteStlFile()">削除</button>
                    </div>
                    <div class="col-sm-6">
                        <input class="form-control form-control-sm mb-2 edit-disabled" id="stl_file" name="stl_file" type="file" accept=".stl,.obj">
                        <div class="row mb-2">
                            <div class="col-sm-3"><label for="stl_type_id" class="col-form-label">種類</label></div>
                            <div class="col-sm-6">
                                <select class="form-select edit-disabled" id="stl_type_id" name="stl_type_id" onchange="onchangeStlType(this)">
                                    @foreach ($stlTypeOptions as $stlType)
                                    <option value="{{ $stlType->stl_type_id }}">{{ $stlType->stl_type_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3"><label for="solar_absorptivity" class="col-form-label">日射吸収率</label></div>
                            <div class="col-sm-4">
                                <input type="text" class="form-control form-control-sm edit-disabled" id="solar_absorptivity" name="solar_absorptivity">
                            </div>
                            <div class="col-sm-4">
                                <small class="form-text text-muted d-inline-block w-100">(0以上1以下の実数)</small>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3"><label for="heat_removal" class="col-form-label">排熱量初期値</label></div>
                            <div class="col-sm-4">
                                <input type="text" class="form-control form-control-sm edit-disabled" id="heat_removal" name="heat_removal">
                            </div>
                            <div class="col-sm-3">
                                <small class="form-text text-muted">(W/m2)</small>
                            </div>
                            <div class="col-sm-2 text-end">
                                <button type="button" class="btn btn-outline-secondary edit-disabled" id="ButtonUploadStl" onclick="submitFrmUploadStl()">追加</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-sm-2 col-form-label">建物・樹木の追加・削除</label>
            <div class="col-sm-10">
                <div class="row" id="objSTLfileEditArea">
                    @include('city_model/partial_stl.parital_obj_stl_file_edit', ['stlTypeOptions' => $stlTypeOptions])
                </div>
            </div>
        </div>
    </form>

    <div class="row mt-4 mb-3">
        <div class="col-12 d-flex justify-content-end">
            <button type="button" class="btn btn-outline-secondary me-3" onclick="submitFrmUpdateAll()">保存</button>
            <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('city_model.index') }}'">キャンセル</button>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script src="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Cesium.js"></script>
    <script src="{{ asset('/js/3d_map.js') }}?ver={{ config('const.ver_js') }}"></script>
    <script>

        // 3D地図描画
        let viewer = null;

        // 建物描画ようの座標
        let hierarchy = [];
        let positions = [];

        // プレビューボタンは押したかどうか
        let previewActivity = false;

        // 地図をクリックするイベント
        let handler = null;

        // 現在のロングポーリング対象の解析対象地域IDを保持
        let currentLongPollingRegionId = null;
        // ロングポーリングの状態を管理
        let longPollingActive = false;

        // 削除しようとするモデル
        let deleteModels = [];

        // 選択した建物を表示するかどうか
        let showBuildingActive = false;

        $(function(){
            @if ($regionId)
                // STLファイルアップロード・削除後に解析対象地域の行選択状態を維持するようにします。
                const regionId = "{{ $regionId }}";
                selectRegion($("#region-list span.region[data-region-id='" + regionId +"']"), false);
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
                else if (msg_type == "C")
                {
                    // 解析対象地域複製ダイアログを表示
                    $("div#messageModal [class='modal-body']").html(
                        '<div class="d-flex flex-column"><span class="ms-4" id="message"></span><div class="mt-1 ms-4"><input class="form-control" type="text" name="replicate_to_region_name" id="replicate_to_region_name"></div></div>');
                    $("div#messageModal [class='modal-footer']").html(
                        '<button type="button" id="ButtonCopyOK" class="btn btn-outline-secondary" data-bs-dismiss="modal">追加</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">キャンセル</button>');

                    // 解析対象地域複製イアログで追加ボタンを押した
                    $("div#messageModal [class='modal-footer'] button#ButtonCopyOK").click(function() {
                        const frmId = "#frmRegion";
                        const iniUrl = "{{ route('region.copy', ['city_model_id' => $cityModel->city_model_id, 'region_id' => isset($regionId) ? $regionId : 0]) }}";
                        const replicateToRegionName = $("div#messageModal [class='modal-body'] input#replicate_to_region_name").val();
                        const copyAction =  iniUrl + "?registered_user_id=" + "{{ $registeredUserId }}" + "&copy_flg=1&replicate_to_region_name=" + replicateToRegionName;
                        // フォームサブミット
                        submitFrm(frmId, copyAction);
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
         *
         * @param mixed target
         *
         * @return
         */
        function selectRegion(target, isAlert)
        {
            setbgColor(target);
            setMapActive(target);
            ajaxUpdateStlInfo(isAlert);
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
         * フォームサブミット
         *
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
         * 解析対象地域を複製
         *
         * @return
         */
        function submitFrmCopyRegion()
        {
            const frmId = "#frmRegion";
            let iniUrl = "{{ route('region.copy', ['city_model_id' => $cityModel->city_model_id, 'region_id' => 0]) }}";

            // 選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            let action = "";
            if (regionId)
            {
                // 最後の文字列を置換
                action = iniUrl.replace(/.$/, regionId);
            }
            else
            {
                action = iniUrl;
            }

            // パラメータ設定
            action += '?registered_user_id=' + '{{ $registeredUserId }}' + '&copy_flg=0';

            // フォームサブミット
            submitFrm(frmId, action);
        }

        /**
         * 解析対象地域を削除
         *
         * @return
         */
        function submitFrmDeleteRegion()
        {
            const frmId = "#frmRegion";
            let iniUrl = "{{ route('region.delete', ['city_model_id' => $cityModel->city_model_id, 'region_id' => 0]) }}";

            // 選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');
            let action = "";
            if (regionId)
            {
                // 最後の文字列を置換
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
         *
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
         * STLファイルをアップロード
         *
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
        function ajaxUpdateStlInfo(isAlert)
        {
            // 3D地図をリセット
            reset3DMap(viewer);

            // 3D地図がロードされるまで、建物・樹木の追加・削除モードラジオボタンは無効にする。
            $(".disabled-all").prop("disabled", true);

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
                cache: false,
                success: function (response) {

                    // STLファイル一覧を更新する。
                    let paritalViewStlFile = response['paritalViewStlFile'];
                    updateStlFileList(paritalViewStlFile);

                    // STL定義上下限を更新する。
                    let paritalViewStlDefinition = response['paritalViewStlDefinition'];
                    updateStlDefination(paritalViewStlDefinition);

                    // CZMLファイル
                    let czmlFiles = response['czmlFiles'];
                    if (czmlFiles.length > 0) {
                        const czmlFileNull = czmlFiles.indexOf("");
                        if (czmlFileNull == -1) {
                            // ロード中画面が既に表示されている場合、閉じる
                            hide("#loadingDiv");
                            show("#cesiumContainer");

                            longPollingActive = false; // ロングポーリング終了

                            // 3D地図初期表示
                            viewer = show3DMap("cesiumContainer", czmlFiles);
                        } else {
                            // 一つでもczmlファイル=nullであるレコードがあった場合、ロード中画面を表示する。
                            // 3D地図非表示
                            hide("#cesiumContainer");
                            // ロード中画面を表示する。
                            show("#loadingDiv");

                            // ==3D地図描画に必要な全てCZMLファイルが出来上がるまで、繰り返す。(※ロングポーリング)===
                            // 既存のロングポーリングを停止し、新しいロングポーリングを開始
                            if (currentLongPollingRegionId !== regionId) {
                                longPollingActive = false;
                                waitCzmlFile(regionId);
                            }
                            // ==3D地図描画に必要な全てCZMLファイルが出来上がるまで、繰り返す。(※ロングポーリング) //===
                        }
                    } else {
                        // レコードが存在しない場合は空白とする。
                        hide("#loadingDiv");
                        show("#cesiumContainer");

                        // 既存のロングポーリングを停止する。(※レコードが存在しない場合は空白とするため。)
                        currentLongPollingRegionId = regionId;
                    }

                    // 選択した解析対象地域を編集できるかどうかの状況を取得する。
                    let regionEditIsOK = response['regionEditIsOK'];
                    if (!regionEditIsOK && isAlert) {
                        // 編集不可にする。
                        $(".edit-disabled").prop("disabled", true);
                        $("#stlFileListArea span").css("pointer-events", "none")
                        showDialog("I9", '{{ App\Commons\Message::$I9 }}', "I");
                    } else {
                        $(".edit-disabled").prop("disabled", false);
                        $("#stlFileListArea span").css("pointer-events", "");

                        // 解析対象地域一覧で選択された地域の登録者がログインユーザでない場合は、
                        // 建物・樹木の追加・削除モードラジオボタンは有効にする。
                        let buildingEditIsOk = response['buildingEditIsOk'];
                        if (!buildingEditIsOk) {
                            $("input[name='map_activity']").prop("disabled", true);
                        }
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
        * 選択した解析対象地域により、STLファイル一覧を更新する。
        *
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
        *
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
        *
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
        * 3D都市モデル名、3D Tiles、解析対象地域を更新する関数
        *
        * @return
        */
        function submitFrmUpdateAll()
        {
            const frmId = "#formCitymodel";
            let cityModelId = "{{ $cityModel->city_model_id }}";
            let action = "{{ route('city_model.update', ['id' => 0]) }}".replace(/.$/, cityModelId);

            //選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');

            action += '?registered_user_id=' + '{{ $registeredUserId }}';
            if (regionId)
            {
                action += '&region_id=' + regionId;
            }

            let stlDefinitionData = {};
            $('#stlDefinitionArea input').each(function() {
                stlDefinitionData[$(this).attr('name')] = $(this).val();
            });

            // フォームにデータを追加
            for (let key in stlDefinitionData) {
                $(frmId).append($('<input>').attr('type', 'hidden').attr('name', key).val(stlDefinitionData[key]));
            }

            // フォームサブミット
            submitFrm(frmId, action);
        }

        /**
        * STLファイル種類のonchangeイベント
        *
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
        *
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

                    // [STLファイル種別]の単独木フラグまたは植被フラグがTrueの場合は
                    // 日射吸収率入力欄が非アクティブとし、値は空白とする。
                    if (!stl_type['solar_absorptivity']) {
                        $('#solar_absorptivity').prop("disabled", true);
                    } else {
                        $('#solar_absorptivity').prop("disabled", false);
                    }

                    // [STLファイル種別]の単独木フラグまたは植被フラグがTrueの場合は
                    //　排熱量入力欄が非アクティブとし、値は空白とする。
                    if (!stl_type['heat_removal']) {
                        $('#heat_removal').prop("disabled", true);
                    } else {
                        $('#heat_removal').prop("disabled", false);
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
        * 建物・樹木の追加・削除モードをアクティブに設定
        *
        * @param region 選択した「解析対象地域」
        */
        function setMapActive(region)
        {
            let registerUserId = $(region).data('user-id');
            let loginUserId = "{{ $loginUserId }}";

            // 建物・樹木の追加・削除用の各項目をリセットする。
            resetObjStlSetting();

            // 解析対象地域一覧]で選択されたレコードの登録者がログインユーザでない状態の場合は非アクティブとする
            $("input[name='map_activity']").prop('checked', false); // 「建物・樹木の追加・削除モード」の状態をクリア
            if (loginUserId == registerUserId) {
                $("input[name='map_activity']").prop('disabled', false);
            } else {
                $("input[name='map_activity']").prop('disabled', true);
            }
        }

        /**
        * 建物・樹木の追加・削除用の各項目をリセットする。
        */
        function resetObjStlSetting()
        {
            // 建物作成の各項目をリセット
            $("input[name='building_height']").val("");                     //「高さ」の状態をクリア
            $("select[name='obj_stl_type_id']").prop("selectedIndex", 0);   // 「建物種別」の状態をクリア

            // 植被作成の各項目をリセット
            $("input[name='plant-cover-height']").val("");                  //「高さ」の状態をクリア

            // 単独木作成の各項目をリセット
            $("select[name='tree_type_id']").prop("selectedIndex", 0);      //「分類」の状態をクリア
            $("input[name='canopy_height']").val("");                       //「樹高」の状態をクリア
            $("input[name='canopy_diameter']").val("");                     //「樹冠直径」の状態をクリア
            $("input[name='add_tree_to_line']").prop('checked', false);     //「直線上に追加」の状態をクリア
            $("input[name='tree_interval']").val("");                       //「間隔」の状態をクリア

            // 「選択モデルを非表示」の状態をクリア
            $("input[name='model_hidden']").prop('checked', false);
        }

        /**
        * 建物・樹木の追加・削除モードをクリックする。
        *
        * @param target クリックした建物・樹木の追加・削除モード
        */
        function onClickMapActivity(target)
        {
            // 建物・樹木の追加・削除用の各項目をリセットする。
            resetObjStlSetting();

            $("#ButtonPreviewModel").prop('disabled', false);   // プレビューボタンを無効にしておく。
            $("#ButtonReset").prop('disabled', true);           // 元に戻すボタンを無効にする。
            $("#ButtonAddNewModel").prop('disabled', true);     // 作成ボタンを無効にする。

            mapActivityMode = Number($(target).val());
            if (mapActivityMode == {{ App\Commons\Constants::DELETE_MODEL }}) {
                // 「モデル削除」ラジオボタン押下した場合、3D地図上で建物データ削除が可能とする。
                onClickMapModelDel();
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }}) {
                // 建物作成
                onClickMapAddnewBuilding();
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }}) {
                // 植被作成
                onClickMapAddnewPlantCover();
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_TREE }}) {
                // 単独木作成
                onClickMapAddnewTree();
            }
        }

        /**
         * モデル削除を選択する
         * @param id 削除対象モデルID
         */
        function onClickMapModelDel(id)
        {
            $(".activity-del").prop('disabled', false);
            $(".activity-addnew-building").prop('disabled', true);
            $(".activity-addnew-plant-cover").prop('disabled', true);
            $(".activity-addnew-tree").prop('disabled', true);
            $("#ButtonPreviewModel").prop('disabled', true);

            // 地面をクリックするイベントの設定を無効にする。
            if (handler) {
                removeActionFromHandler(handler);
            }

            // 前回で削除手続き中に解析対象地域一覧より別の解析対象地域を選択しまう可能性があるため、
            // 念のため、前回で使用したデータをリセットする。
            _resetMapActivity();

            // 削除手続き中(モデル選択までや 非表示までなど)のモデルが存在していたら、クリアする。
            _resetDeleteModel();

            // 建物選択イベントの設定を有効にする。
            if (viewer) {
                handler = modelClickEventSetting(viewer, deleteModels);
            }
        }

        /**
         * 建物作成を選択する。
         */
        function onClickMapAddnewBuilding()
        {
            // 「建物作成」ラジオボタン押下した場合、3D地図上で新規建物の地表面上の頂点の選択および新規建物の作成が可能とする。
            $(".activity-addnew-building").prop('disabled', false);
            $(".activity-addnew-plant-cover").prop('disabled', true);
            $(".activity-addnew-tree").prop('disabled', true);
            $(".activity-del").prop('disabled', true);

            // 前回でプレビュー中に解析対象地域一覧より別の解析対象地域を選択しまう可能性があるため、
            // 念のため、前回で使用したデータをリセットする。
            _resetMapActivity();

            // 削除手続き中(モデル選択までや 非表示までなど)のモデルが存在していたら、クリアする。
            _resetDeleteModel();

            // 建物選択イベントの設定を無効にする。
            if (handler) {
                removeActionFromHandler(handler);
            }

            // 地面をクリックするイベントの設定を有効にする。
            if (viewer) {
                handler = mapClickEventSetting(viewer, hierarchy, positions);
            }
        }

        /**
         * 植被作成を選択する。
         */
        function onClickMapAddnewPlantCover()
        {
            // 「植被作成」ラジオボタン押下した場合、3D地図上で新規植被の地表面上の頂点の選択および新規植被の作成が可能とする。
            $(".activity-addnew-plant-cover").prop('disabled', false);
            $(".activity-addnew-building").prop('disabled', true);
            $(".activity-addnew-tree").prop('disabled', true);
            $(".activity-del").prop('disabled', true);

            // 前回でプレビュー中に解析対象地域一覧より別の解析対象地域を選択しまう可能性があるため、
            // 念のため、前回で使用したデータをリセットする。
            _resetMapActivity();

            // 削除手続き中(モデル選択までや 非表示までなど)のモデルが存在していたら、クリアする。
            _resetDeleteModel();

            // 建物選択イベントの設定を無効にする。
            if (handler) {
                removeActionFromHandler(handler);
            }

            // 地面をクリックするイベントの設定を有効にする。
            if (viewer) {
                handler = mapClickEventSetting(viewer, hierarchy, positions);
            }
        }

        /**
         * 単独木作成を選択する。
         * @param integer 建物・樹木の追加・削除モード(1: モデル削除; 2: 新規建物作成; 3: 植被作成; 4: 単独木作成)
         */
        function onClickMapAddnewTree()
        {
            // 「単独木作成」ラジオボタン押下した場合、3D地図上で新規単独木の地表面上の頂点の選択および新規単独木の作成が可能とする。
            $(".activity-addnew-tree").prop('disabled', false);
            $(".activity-addnew-plant-cover").prop('disabled', true);
            $(".activity-addnew-building").prop('disabled', true);
            $(".activity-del").prop('disabled', true);

            // 分類が未選択の場合、「樹高」と「樹冠直径」を入力不可にする
            $('#canopy_height').prop('disabled', true);
            $('#canopy_diameter').prop('disabled', true);
            // 「間隔」を入力不可にする
            $('#tree_interval').prop('disabled', true);

            // 前回でプレビュー中に解析対象地域一覧より別の解析対象地域を選択しまう可能性があるため、
            // 念のため、前回で使用したデータをリセットする。
            _resetMapActivity();

            // 削除手続き中(モデル選択までや 非表示までなど)のモデルが存在していたら、クリアする。
            _resetDeleteModel();

            // 建物選択イベントの設定を無効にする。
            if (handler) {
                removeActionFromHandler(handler);
            }

            // 地面をクリックするイベントの設定を有効にする。
            if (viewer) {
                handler = mapClickEventSetting(viewer, hierarchy, positions);
            }
        }

        /**
        * 正の数のみを許可する
        *
        * @param target 高さなどのinput
        */
        function validatePositiveNumber(target)
        {
            const height = $(target).val();
            // input type numberは、 「-」負のみ入力した場合は、空文字になる。
            if (height != "") {
                const value = parseFloat(height);
                if (value < 0) {
                    $(target).val(""); // リセット
                }
            } else {
                $(target).val(""); // リセット
            }
        }

        /**
         * エレメントを表示する。
         *
         * @param targetId エレメントID
         *
         * @return
         */
        function show(targetId)
        {
            if ($(targetId).hasClass("d-none")) {
                $(targetId).removeClass("d-none");
            }
        }

        /**
         * エレメントを非表示する。
         *
         * @param targetId エレメントID
         *
         * @return
         */
        function hide(targetId)
        {
            if (!$(targetId).hasClass("d-none")) {
                $(targetId).addClass("d-none");
            }
        }

        /**
        * 3D地図描画に必要な全てCZMLファイルが出来上がったか定期的に確認する。(ロングポーリング)
        *
        * @param string regionId 選択した解析対象地域
        * @param number retry エラー時のリトライカウンタ
        */
        function waitCzmlFile(regionId, retry)
        {
            //「解析対象地域」一覧より別の「解析対象地域」が選択された場合、前のロングポーリングを停止
            if (longPollingActive && currentLongPollingRegionId !== regionId) {
                longPollingActive = false;
                return; // 前のロングポーリングを停止
            }

            // ロングポーリングを開始
            longPollingActive = true;
            currentLongPollingRegionId = regionId;

            // リトライー
            retry = retry ? retry : 3;

            let url = "{{ route('region.wait_czml_file', ['region_id' => 0]) }}";
            // 選択した解析対象地域を取得
            if (regionId) {
                url = url.replace(/.$/, regionId); // 最後の文字列を置換
            }

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                cache: false,
                data: {},

                // 成功時(3D地図描画に必要な全てCZMLファイルが出来上がった)
                success: function (response) {

                    // 発火
                    if (response.type == 'fire')
                    {
                        // ロード中画面が既に表示されている場合、閉じる
                        hide("#loadingDiv");
                        show("#cesiumContainer");

                        longPollingActive = false; // ロングポーリング終了

                        // 3D地図初期表示
                        viewer = show3DMap("cesiumContainer", response.czmlFiles);

                        // 新規建物作成や削除のAPIを呼び出したあと、連続的に操作できるようにこちらで、建物描画や建物にクリックのイベントを再設定する必要がある。
                        reSettingHandler();
                    } else {
                        // 「解析対象地域」一覧より別の「解析対象地域」が選択されていないか確認し、
                        // 3D地図描画に必要な全てCZMLファイルが出来上がるまで、繰り返す。
                        if (longPollingActive && currentLongPollingRegionId === regionId) {
                            waitCzmlFile(regionId, 3);
                        }
                    }
                },

                // エラー
                error: function (response) {

                    // 「解析対象地域」一覧より別の「解析対象地域」が選択されていないか確認し、
                    // 3D地図描画に必要な全てCZMLファイルが出来上がるまで、繰り返す。
                    if (longPollingActive && currentLongPollingRegionId === regionId) {

                        // サーバ側でエラーが発生した場合には、
                        // 無限に呼出されるのを防ぐために、リトライカウンタを使用する
                        retry--;
                        if (retry > 0)
                        {
                            waitCzmlFile(regionId, retry);
                        }
                    }
                },
            });
        }

        /**
         * 「選択モデルを非表示チェックボックス」にチェック入れ・外しをする。
         *
         * @param object target 選択モデルを非表示チェックボックス
         *
         * @return
         */
        function hideOrShowBuilding(target)
        {
            let isChecked = $(target).prop("checked");
            if (isChecked) {
                if (deleteModels.length > 0) {
                    // 選択モデルを非表示する。(※透明度をゼロに設定)
                    setEntityColor(deleteModels, HIDE_MODEL, Cesium.Color.TRANSPARENT);
                } else {
                    // エラーメッセージダイアログを表示
                    showDialog("E31", '{{ App\Commons\Message::$E31 }}');
                    // 選択モデルを非表示チェックボックスよりチェックを外す。
                    $(target).prop("checked", false);
                }
            } else {
                // 選択モデルを再表示する。(※元の色に設定する。)
                setEntityColor(deleteModels, SHOW_MODEL);
            }
        }

        /**
         * 削除しようとするモデルをリセットする。
         *
         * @return
         */
        function _resetDeleteModel()
        {
            if (deleteModels.length > 0) {
                // 選択モデルの元の色に戻す
                resetEntityColor(deleteModels);
                deleteModels = [];
            }
        }

        /**
         *「モデルを削除」ボタン押下
         *
         * @param object target 「モデルを削除」ボタン
         *
         * @returns
         */
        function deleteModel(target)
        {

            // プレビューボタンはまだ押されていない場合、エラーを出す。
            if (deleteModels.length == 0) {
                // エラーメッセージダイアログを表示
                showDialog("E31", '{{ App\Commons\Message::$E31 }}');
                return;
            }

            // 選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');

            // 削除しようとするモデルIDの配列
            let buildingId = deleteModels.map(element => {
                return element.entity.id;
            });

            // リクエストパラメータ
            let params = {
                "region_id": regionId,
                "building_id": buildingId
            }

            // 建物削除する用のAPIを呼び出す。
            modelProcessRequest(params, {{ App\Commons\Constants::DELETE_MODEL }});

            // 削除を行った建物リストをクリアする。
            resetEntityColor(deleteModels);
            deleteModels = [];
            $("input[name='model_hidden']").prop('checked', false); // 「選択モデルを非表示」の状態を強制にクリア
        }

        /**
         * 作成しようとするモデルのプレビュ
         *
         * @param object target プレビューボタン
         *
         * @returnss {Promise<void>}
         */
        async function previewModel(target)
        {
            var errorMsg = "";
            if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }}) {
                // 建物作成の場合
                errorMsg = previewBuilding();
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }}) {
                // 植被作成の場合
                errorMsg = previewPlantCover();
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_TREE }}) {
                // 単独木作成の場合
                errorMsg = await previewTree();
            }

            if (errorMsg == "" && previewActivity) {
                $(target).prop("disabled", true);                   // プレビューボタン自体は無効にする。
                $("#ButtonReset").prop("disabled", false);          //「元に戻す」ボタンは有効にする。
                $("#ButtonAddNewModel").prop("disabled", false);    //「作成」ボタンは有効にする。
            } else if (errorMsg != ""){
                // エラーメッセージダイアログを表示
                showDialog("E32", errorMsg);
            }
        }

        /**
         * 作成しようとする建物のプレビュー
         *
         * @returns string エラーメッセージ
         */
        function previewBuilding()
        {
            let errorMsg = "";

            let extrudedHeight = Number($("#building_height").val());
            // 建物作成は最低4点指定(クリック)が必要（三角の建物は対象外）
            if ((hierarchy.length >= 4) && (extrudedHeight > 0)) {
                drawBuilding(viewer, hierarchy, extrudedHeight);

                // プレビュー後に作成操作できないようにするため、
                // 地図をクリックするイベントの設定を無効にする。
                if (handler) {
                    removeActionFromHandler(handler);
                }

                // プレビューボタンは押した(フラグをtrueにする。)
                previewActivity = true;
                // 建物作成用の各項目を無効にする。
                $(".activity-addnew-building").prop("disabled", true);

            } else {
                errorMsg = '{{ sprintf(App\Commons\Message::$E32, "建物の頂点と高さ") }}';
            }

            return errorMsg;
        }

        /**
         * 作成しようとする植被のプレビュー
         *
         * @returns string エラーメッセージ
         */
        function previewPlantCover()
        {
            let errorMsg = "";

            let plantCoverHeight = Number($("#plant-cover-height").val());
            // 建物作成は最低4点指定(クリック)が必要（三角の建物は対象外）
            if ((hierarchy.length >= 4) && (plantCoverHeight > 0)) {
                drawBuilding(viewer, hierarchy, plantCoverHeight);

                // プレビュー後に作成操作できないようにするため、
                // 地図をクリックするイベントの設定を無効にする。
                if (handler) {
                    removeActionFromHandler(handler);
                }

                // プレビューボタンは押した(フラグをtrueにする。)
                previewActivity = true;
                // 植被作成用の各項目を無効にする。
                $(".activity-addnew-plant-cover").prop("disabled", true);
            } else {
                errorMsg = '{{ sprintf(App\Commons\Message::$E32, "植被の頂点と高さ") }}';
            }

            return errorMsg;
        }

        /**
         * 作成しようとする単独木のプレビュー
         *
         * @returnss {Promise<string>} 正常の場合、空文字列を返す。異常の場合はエラーメッセージを返す。
         */
        async function previewTree()
        {
            let errorMsg = '{{ sprintf(App\Commons\Message::$E32, "単独木の分類、頂点、樹冠直径、高さ、間隔（直線上に追加する場合のみ）") }}';

            let treeTypeId = Number($("#tree_type_id").val());         // 分類
            if (treeTypeId >= 0) {
                // 念のため、樹高は基準値より小さいかをチェック
                if (checkBelowMinimum("#canopy_height") || checkBelowMinimum("#canopy_diameter")) {
                    return "";
                }

                let canopyHeight = Number($("#canopy_height").val());       // 樹高
                let canopyDiameter = Number($("#canopy_diameter").val());   // 樹冠直径
                let treeInterval = Number($("#tree_interval").val());        // 間隔

                // 「直線上に追加」の場合(選択２点に制限)
                if ((createTreeOnLineFlg && treeInterval > 0 && hierarchy.length == 2)) {
                    try {
                        await drawCylindersOnLine(viewer, hierarchy, canopyHeight, canopyDiameter, treeInterval);
                        errorMsg = "";
                    } catch (error) {
                        console.error("「単独木」「直線上に追加」のプレビューに失敗しました。:", error);
                    }
                } else if (!createTreeOnLineFlg && hierarchy.length >= 1) {
                    // 通常の単独木作成は最低1点指定(クリック)が必要
                    drawCylinder(viewer, hierarchy, canopyHeight, canopyDiameter);
                    errorMsg = "";
                }

                if (errorMsg == "") {
                    // プレビュー後に作成操作できないようにするため、
                    // 地図をクリックするイベントの設定を無効にする。
                    if (handler) {
                        removeActionFromHandler(handler);
                    }

                    // プレビューボタンは押した(フラグをtrueにする。)
                    previewActivity = true;
                    // 単独木作成用の各項目を無効にする。
                    $(".activity-addnew-tree").prop("disabled", true);
                }
            }
            return errorMsg;
        }

        /**
         * 「元に戻す」ボタン押下
         *
         * @param object target 「元に戻す」ボタン
         *
         * @returns
         */
        function resetMapActivity(target)
        {
            // 元に戻す処理
            _resetMapActivity();

            // 建物・樹木の追加・削除用の各項目をリセットする。
            resetObjStlSetting();

            // 地図をクリックするイベントの設定を有効にする。(プレビュー後に無効にしたため)
            handler = mapClickEventSetting(viewer, hierarchy, positions);

            if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }}) {
                // 建物作成の場合
                $(".activity-addnew-building").prop("disabled", false);
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }}) {
                // 植被作成の場合
                $(".activity-addnew-plant-cover").prop("disabled", false);
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_TREE }}) {
                // 単独木作成の場合、単独木作成用の各項目を有効にする。
                $(".activity-addnew-tree").prop("disabled", false);
                //「樹高」と「樹冠直径」と「間隔」を入力不可にする
                $('#canopy_height').prop('disabled', true);
                $('#canopy_diameter').prop('disabled', true);
                $('#tree_interval').prop('disabled', true);
            }

            $("#ButtonPreviewModel").prop("disabled", false);    // プレビューボタンは有効にする。
            $(target).prop("disabled", true);                    // 「元に戻す」ボタン自体は無効にする。
            $("#ButtonAddNewModel").prop("disabled", true);      // 「作成」ボタンは無効にする。
        }

        /**
         * 元に戻す処理(サブ処理)
         *
         * @returns
         */
        function _resetMapActivity() {

            // プレビューフラグを無効にする。
            previewActivity = false;

            // 3D地図上に描画中するモデル(建物・植被・単独木)をリセットする。
            if (viewer) {
                // 単独木の場合、追加した円柱をリセットする。
                clearTree(viewer);
                // 建物と植被の場合、追加したpolygonをリセットする。
                clearPolygon(viewer);
            }

            // プレビューで保存した建物描画ようの座標をリセットする。
            hierarchy = [];
            positions = [];
        }

        /**
         * 作成ボタン押下
         *
         * @param object target 作成ボタン
         *
         * @returns
         */
        function addNewModel(target)
        {
            // 建物・樹木の追加・削除モードを取得
            let msg = "";
            if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }}) {
                // 建物作成の場合
                if (!previewActivity) {
                    // プレビューボタンはまだ押されていない場合、エラーを出す。
                    msg = '{{ sprintf(App\Commons\Message::$E33, "建物") }}';
                } else {
                    addNewBuilding();
                }
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }}) {
                // 植被作成の場合
                if (!previewActivity) {
                    // プレビューボタンはまだ押されていない場合、エラーを出す。
                    msg = '{{ sprintf(App\Commons\Message::$E33, "植被") }}';
                } else {
                   addNewPlantCover();
                }
            } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_TREE }}) {
                // 単独木作成の場合
                if (!previewActivity) {
                    // プレビューボタンはまだ押されていない場合、エラーを出す。
                    msg = '{{ sprintf(App\Commons\Message::$E33, "単独木") }}';
                } else {
                    addNewTree();
                }
            }

            // エラーメッセージダイアログを表示
            if (msg != "") {
                showDialog("E33", msg);
            } else {
                $("#ButtonPreviewModel").prop("disabled", false);   // プレビューボタンは有効にする。
                $(target).prop("disabled", true);                   // 作成ボタン自体は無効にする。
                $("#ButtonReset").prop("disabled", true);           // 「元に戻す」ボタンは無効にする。

                // 建物・樹木の追加・削除用の各項目をリセットする。
                resetObjStlSetting();
                // 元に戻す処理
                _resetMapActivity();
            }
        }

        /**
         * 建物作成を行う
         */
        function addNewBuilding()
        {
            let height = Number($("#building_height").val());                  // 高さ
            let regionId = $("#region-list span.bg-cyan").data('region-id');   // 選択した解析対象地域
            let stlTypeId = Number($("#obj_stl_type_id").val());               // 建物種別

            // リクエストパラメータ
            let params = {
                "coordinates": positions,   // ユーザが入力した建物の底面の点(※入力した順にリストに格納される情報)
                "height": height,
                "region_id": regionId,
                "stl_type_id": stlTypeId
            }
            // 新規建物を作成する用のAPIを呼び出す。
            modelProcessRequest(params, {{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }});

            // 建物作成用の各項目を有効にする。
            $(".activity-addnew-building").prop("disabled", false);
        }

        /**
         * 植被作成を行う
         */
        function addNewPlantCover()
        {
            let height = Number($("#plant-cover-height").val());                  // 高さ
            let regionId = $("#region-list span.bg-cyan").data('region-id');   // 選択した解析対象地域

            // リクエストパラメータ
            let params = {
                "coordinates": positions,   // ユーザが入力した建物の底面の点(※入力した順にリストに格納される情報)
                "height": height,
                "region_id": regionId,
            }
            // 植被作成する用のAPIを呼び出す。
            modelProcessRequest(params, {{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }});

            // 植被作成用の各項目を有効にする。
            $(".activity-addnew-plant-cover").prop("disabled", false);
        }

        /**
         * 単独木作成を行う
         */
        function addNewTree()
        {
            let canopyHeight = Number($("#canopy_height").val());              // 樹高
            let canopyDiameter = Number($("#canopy_diameter").val());          // 樹冠直径
            let regionId = $("#region-list span.bg-cyan").data('region-id');   // 選択した解析対象地域

            // リクエストパラメータ
            let params = {
                "coordinates": positions,   // ユーザが入力した建物の底面の点(※入力した順にリストに格納される情報)
                "height": canopyHeight,
                "canopy_diameter": canopyDiameter,
                "region_id": regionId,
            }
            // 単独木作成する用のAPIを呼び出す。
            modelProcessRequest(params, {{ App\Commons\Constants::ADDNEW_MODEL_TREE }});

            // 単独木作成用の各項目を有効にする。
            $(".activity-addnew-tree").prop("disabled", false);

            //「樹高」と「樹冠直径」と「間隔」を入力不可にする
            $('#tree_interval').prop('disabled', true);
            $('#canopy_height').prop('disabled', true);
            $('#canopy_diameter').prop('disabled', true);
        }

        /**
         * イベントハンドラを再設定する。
         *
         * @returns
         */
        function reSettingHandler()
        {
            // 建物・樹木の追加・削除モード:
            // 1: モデル削除
            // 2: 新規建物作成
            // 3: 植被作成
            // 4: 単独木作成
            if (viewer && handler) {
                removeActionFromHandler(handler);
                if (mapActivityMode == {{ App\Commons\Constants::DELETE_MODEL }}) {
                    // 建物をクリックするイベントの設定を有効にする。
                    handler = modelClickEventSetting(viewer, deleteModels);
                } else if (mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }} ||
                            mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }} ||
                            mapActivityMode == {{ App\Commons\Constants::ADDNEW_MODEL_TREE }}) {
                        // 地面をクリックするイベントの設定を有効にする。
                        handler = mapClickEventSetting(viewer, hierarchy, positions);
                }
            }
        }

        /**
         * 架空建物の操作リクエストを送る。
         *
         * @param array params リクエストパラメータ
         * @param int model_type 操作タイプ（(1: モデル削除; 2:建物作成; 3:植被作成; 4:単独木作成)
         *
         * @returns
         */
        function modelProcessRequest(params, model_type = {{ App\Commons\Constants::DELETE_MODEL }})
        {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })

            // リクエスト先
            let url = "";
            if (model_type == {{ App\Commons\Constants::DELETE_MODEL }}) {
                // モデル削除
                url = "{{ route('map_activity.delete', ['model_type' => ':model_type']) }}".replace(':model_type', model_type);
            } else if (model_type == {{ App\Commons\Constants::ADDNEW_MODEL_BUILDING }} ||
                        model_type == {{ App\Commons\Constants::ADDNEW_MODEL_PLANT_COVER }} ||
                        model_type == {{ App\Commons\Constants::ADDNEW_MODEL_TREE }}) {
                // 建物の作成
                url = "{{ route('map_activity.create', ['model_type' => ':model_type']) }}".replace(':model_type', model_type);
            }

            // リクエストを送る
            $.ajax({
                url: url,
                type: 'POST',
                data: JSON.stringify(params),
                contentType: 'application/json; charset=UTF-8',
                dataType: 'json',
                success: function (response) {
                    let error = (response && response['error'] !== undefined) ? response['error'] : [];
                    if (error.length == 0) {
                        // 3D地図非表示
                        hide("#cesiumContainer");
                        // ロード中画面を表示する。
                        show("#loadingDiv");

                        // 3D地図をリセット
                        reset3DMap(viewer);
                        // 3D地図再描画をリクエスト(※必要なczmlファイルを取得する。)
                        waitCzmlFile(params['region_id']);
                    } else {
                        // 3D地図再描画を行わずにエラーメッセージダイアログを表示
                        showDialog(error['code'], error['msg']);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.log(xhr, textStatus, errorThrown);
                    showDialog("エラー", "サーバーでエラーが発生しました。");
                },
                complete: function(xhr, textStatus, errorThrown) {
                    // 新規建物作成や削除のAPIを呼び出したあと、連続的に操作できるようにこちらで、建物描画等にクリックのイベントを再設定する必要がある。
                    reSettingHandler();
                }
            })
        }

        /**
         * ダイアログを表示する。
         *
         * @param string code コード
         * @param string msg メッセージ
         * @param string type ダイアログ種類（※デフォルト：E(エラー)）
         *
         * @returns
         */
        function showDialog(code, msg, type="E")
        {
            $("div#messageModal").children().removeClass('modal-lg');

            if (type == "E")
            {
                // エラーメッセージダイアログを表示
                $("div#messageModal [class='modal-body']").html(
                    '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/error.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                $("div#messageModal [class='modal-footer']").html('<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');
            }
            else if (type == "W")
            {
                // 警告メッセージダイアログを表示
                $("div#messageModal [class='modal-body']").html(
                    '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/warning.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                $("div#messageModal [class='modal-footer']").html(
                    '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-outline-secondary" id="ButtonOK">OK</button>');
            }
            else if (type == "I")
            {
                // 情報メッセージダイアログを表示
                $("div#messageModal [class='modal-body']").html(
                    '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/info.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                $("div#messageModal [class='modal-footer']").html(
                    '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');
            } else {
                // インフォメーションを表示する。
                $("div#messageModal [class='modal-body']").html(
                    '<div class="d-flex flex-row"><span class="ms-4" id="message"></span></div>');
                $("div#messageModal [class='modal-footer']").html(
                    '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');
                $("div#messageModal [class='modal-dialog']").addClass('modal-lg');
            }

            $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
            $("div#messageModal [class='modal-body'] span#message").html(msg);
            $('#messageModal').modal('show');
        }

         /**
         * インフォメーションを表示する。
         * @param event イベントオブジェクト
         * @returns
         */
        function displayInfo(event)
        {
            event.preventDefault();
            var code = '<i class="bi bi-info-circle-fill"></i> 【参考】単独木の分類定義';
            var message = '{{ $infomation->information }}'.replace(/\n/g, '<br>');
            showDialog(code, message, null);
        }

        /**
        * 単独木の分類のonchangeイベント
        *
        * @return
        */
        function onchangeTreeType(target)
        {
            // 選択した分類
            const treeTypeId = Number($(target).val());
            if (treeTypeId < 0) {
                // 未選択の場合
                $('#canopy_height').val("");    // 樹高をリセット
                $('#canopy_diameter').val("");  // 樹冠直径をリセット
                //「樹高」と「樹冠直径」を入力不可にする
                $('#canopy_height').prop('disabled', true);
                $('#canopy_diameter').prop('disabled', true);
                return;
            } else if (treeTypeId == 0) {
                // 「手動入力」が選択された場合
                $('#canopy_height').val("");    // 樹高をリセット
                $('#canopy_diameter').val("");  // 樹冠直径をリセット
                //「樹高」と「樹冠直径」を入力可能にする
                $('#canopy_height').prop('disabled', false);
                $('#canopy_diameter').prop('disabled', false);
                return;
            } else {
                const treeTypeOptionsJson = '<?= json_encode($treeTypeOptions, JSON_UNESCAPED_UNICODE) ?>';
                const treeTypeOptions = JSON.parse(treeTypeOptionsJson);
                treeTypeOptions.forEach(element => {
                    if (treeTypeId == element.tree_type_id) {
                        $('#canopy_height').val(element.height);            // 樹高を更新
                        $('#canopy_diameter').val(element.canopy_diameter); // 樹冠直径を更新
                        //「樹高」と「樹冠直径」を入力不可にする
                        $('#canopy_height').prop('disabled', true);
                        $('#canopy_diameter').prop('disabled', true);
                        return;
                    }
                });
            }
        }

        /**
        * 最小値を下回っているかをチェックする
        *
        * @param target 高さなどのinput
        *
        * @returns boolean エラーダイアログを表示するかどうか
        */
        function checkBelowMinimum(target)
        {
            var isShowError = true;

            var targetVal = $(target).val();
            if (targetVal != "") {
                const value = parseFloat($(target).val());
                const data = $(target).data();
                var defaultValue = 0;

                if (data.type == 1) {
                    // 高さの基準値
                    defaultValue = {{ App\Commons\Constants::CANOPY_HEIGHT_DEFAULT }};
                } else if (data.type == 2) {
                    // 樹冠直径の基準値
                    defaultValue = {{ App\Commons\Constants::CANOPY_DIAMETER_DEFAULT }};
                }

                if (value >= defaultValue) {
                    // 入力された値は基準値以上である場合、W6は表示しない。
                   isShowError = false;
                }
            }

            if (isShowError) {
                showDialog("E39", '{!! App\Commons\Message::$E39 !!}', 'E');
            }

            return isShowError;
        }

        /**
        * 「直線上に追加」チェックにより単独木作成モードを変える
        * 　　チェックを入れた場合、任意2点間に単独木を作成する。
        * 　　チェックを外た場合、任意の場所に複数の「点」に単独木を作成する。
        *
        * @param target 「直線上に追加」チェックボックス
        *
        * @returns
        */
        function onChangeAddTreeMode(target) {
            _resetMapActivity();

            if (viewer && handler) {
                removeActionFromHandler(handler);
                handler = mapClickEventSetting(viewer, hierarchy, positions);
            }
            // 「直線上に追加」チェックの状態
            createTreeOnLineFlg = $(target).prop("checked");
            if (createTreeOnLineFlg) {
                // 「直線上に追加」チェックを入れた場合
                // 「間隔」を入力可能にする
                $('#tree_interval').prop('disabled', false);
            } else {
                // 「直線上に追加」チェックを外た場合
                // 「間隔」を入力不可にする
                $('#tree_interval').prop('disabled', true);
                $('#tree_interval').val(""); // 間隔をリセット
            }
            createTreeInterval = Number($("#tree_interval").val()); // 間隔
        }
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
