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
            <label class="col-sm-2 col-form-label">OBJ・STLファイル編集</label>
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

        // 削除しようとする建物
        let deleteBuildings = [];

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
            setBuildingActive(target);
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

            // 3D地図がロードされるまで、建物データ編集モードラジオボタンは無効にする。
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
                        // 建物データ編集モードラジオボタンは有効にする。
                        let buildingEditIsOk = response['buildingEditIsOk'];
                        if (!buildingEditIsOk) {
                            $("input[name='building_activity']").prop("disabled", true);
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
        * 建物データ編集モードをアクティブに設定
        *
        * @param region 選択した「解析対象地域」
        */
        function setBuildingActive(region)
        {
            let registerUserId = $(region).data('user-id');
            let loginUserId = "{{ $loginUserId }}";

            // OBJ・STLファイル編集用の各項目をリセットする。
            resetObjStlSetting();

            // 解析対象地域一覧]で選択されたレコードの登録者がログインユーザでない状態の場合は非アクティブとする
            $("input[name='building_activity']").prop('checked', false); // 「建物データ編集モード」の状態をクリア
            if (loginUserId == registerUserId) {
                $("input[name='building_activity']").prop('disabled', false);
            } else {
                $("input[name='building_activity']").prop('disabled', true);
            }
        }

        /**
        * OBJ・STLファイル編集用の各項目をリセットする。
        */
        function resetObjStlSetting()
        {
            $("input[name='building_hidden']").prop('checked', false); // 「選択建物を非表示」の状態をクリア
            $("input[name='building_height']").val(""); // 「高さ」の状態をクリア
            $("select[name='obj_stl_type_id']").prop("selectedIndex", 0);; // 「建物種別」の状態をクリア
        }

        /**
        * 建物データ編集モードをクリックする。
        *
        * @param target クリックした建物データ編集モード
        */
        function onClickBuildingActivity(target)
        {
            // OBJ・STLファイル編集用の各項目をリセットする。
            resetObjStlSetting();

            let $buildingActivity = $(target).val();
            if ($buildingActivity == "1") {
                // 「既存建物削除」ラジオボタン押下した場合、3D地図上で建物データ削除が可能とする。
                $(".building-activity-del").prop('disabled', false);
                $(".building-activity-addnew").prop('disabled', true);
                $("#ButtonResetBuilding").prop('disabled', true);

                // 地面をクリックするイベントの設定を無効にする。
                if (handler) {
                    removeActionFromHander(handler);
                }

                // 前回で削除手続き中に解析対象地域一覧より別の解析対象地域を選択しまう可能性があるため、
                // 念のため、前回で使用したデータをリセットする。
                _resetDeleteBuilding();

                // レビュー中の建物が存在していたら、「元に戻す」ボタン押下時の処理も呼び出す。
                if (previewActivity) {
                    _resetAddNewBuilding();
                }

                // 建物選択イベントの設定を有効にする。
                if (viewer) {
                    handler = buildingClickEventSetting(viewer, deleteBuildings);
                }
            } else if ($buildingActivity == "2") {
                // 「新規建物作成」ラジオボタン押下した場合、3D地図上で新規建物の地表面上の頂点の選択および新規建物の作成が可能とする。
                $(".building-activity-addnew").prop('disabled', false);
                $(".building-activity-del").prop('disabled', true);

                // 前回でプレビュー中に解析対象地域一覧より別の解析対象地域を選択しまう可能性があるため、
                // 念のため、前回で使用したデータをリセットする。
                _resetAddNewBuilding();

                // 削除手続き中(建物選択までや 非表示までなど)の建物が存在していたら、クリアする。
                _resetDeleteBuilding();

                // 建物選択イベントの設定を無効にする。
                if (handler) {
                    removeActionFromHander(handler);
                }

                // 地面をクリックするイベントの設定を有効にする。
                if (viewer) {
                    handler = mapClickEventSetting(viewer, hierarchy, positions);
                }
            }
        }

        /**
        * 高さ入力チェック（負の数を入力できないようにする）
        *
        * @param target 「高さ」input
        */
        function validateBuildingHeight(target)
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
         * 「選択建物を非表示チェックボックス」にチェック入れ・外しをする。
         *
         * @param object target 選択建物を非表示チェックボックス
         *
         * @return
         */
        function hideOrShowBuilding(target)
        {
            let isChecked = $(target).prop("checked");
            if (isChecked) {
                if (deleteBuildings.length > 0) {
                    // 選択建物を非表示する。(※透明度をゼロに設定)
                    setPolygonColor(deleteBuildings, MODE_HIDE_BUILDING, Cesium.Color.TRANSPARENT);
                } else {
                    // エラーメッセージダイアログを表示
                    showDialog("E31", '{{ App\Commons\Message::$E31 }}');
                    // 選択建物を非表示チェックボックスよりチェックを外す。
                    $(target).prop("checked", false);
                }
            } else {
                // 選択建物を再表示する。(※元の色に設定する。)
                setPolygonColor(deleteBuildings, MODE_SHOW_BUILDING);
            }
        }

        /**
         * 削除しようとする建物をリセットする。
         *
         * @return
         */
        function _resetDeleteBuilding()
        {
            if (deleteBuildings.length > 0) {
                // 選択建物を表示する。(※元の色に設定する。)
                resetPolygonColor(deleteBuildings);
                deleteBuildings = [];
            }
        }

        /**
         *「選択建物を削除」ボタン押下
         *
         * @param object target 「選択建物を削除」ボタン
         *
         * @return
         */
        function deleteBuilding(target)
        {

            // プレビューボタンはまだ押されていない場合、エラーを出す。
            if (deleteBuildings.length == 0) {
                // エラーメッセージダイアログを表示
                showDialog("E31", '{{ App\Commons\Message::$E31 }}');
                return;
            }

            // 選択した解析対象地域
            let regionId = $("#region-list span.bg-cyan").data('region-id');

            // 削除しようとする建物IDの配列
            let buildingId = deleteBuildings.map(element => {
                return element.entity.id;
            });

            // リクエストパラメータ
            let params = {
                "region_id": regionId,
                "building_id": buildingId
            }

            // 建物削除する用のAPIを呼び出す。
            buildingProcessRequest(params, 1);

            // 削除を行った建物リストをクリアする。
            deleteBuildings = [];
            $("input[name='building_hidden']").prop('checked', false); // 「選択建物を非表示」の状態を強制にクリア
        }

        /**
         * 作成しようとする建物をプレビュー
         *
         * @param object target プレビューボタン
         *
         * @return
         */
        function previewBuilding(target)
        {
            let extrudedHeight = Number($("#building_height").val());
            // 建物作成は最低4点指定(クリック)が必要（三角の建物は対象外）
            if ((hierarchy.length >= 4) && (extrudedHeight > 0)) {
                drawBuilding(viewer, hierarchy, extrudedHeight);

                // プレビュー後に作成操作できないようにするため、
                // 地図をクリックするイベントの設定を無効にする。
                if (handler) {
                    removeActionFromHander(handler);
                }

                // プレビューボタンは押した(フラグをtrueにする。)
                previewActivity = true;

                $(target).prop("disabled", true);                   // プレビューボタン自体は無効にする。
                $("#building_height").prop("disabled", true);       // 高さは無効にする。
                $("#obj_stl_type_id").prop("disabled", true);       // 建物種別は無効にする。
                $("#ButtonResetBuilding").prop("disabled", false);  //「元に戻す」ボタンは有効にする。
                $("#ButtonAddNewBuilding").prop("disabled", false); //「新規建物を作成」ボタンは有効にする。
            } else {
                // エラーメッセージダイアログを表示
                showDialog("E32", '{{ App\Commons\Message::$E32 }}');
            }
        }

        /**
         * 「元に戻す」ボタン押下
         *
         * @param object target 「元に戻す」ボタン
         *
         * @return
         */
        function resetAddNewBuilding(target)
        {
            // 元に戻す処理
            _resetAddNewBuilding();

            // OBJ・STLファイル編集用の各項目をリセットする。
            resetObjStlSetting();

            // 地図をクリックするイベントの設定を有効にする。(プレビュー後に無効にしたため)
            handler = mapClickEventSetting(viewer, hierarchy, positions);

            $("#building_height").prop("disabled", false);          // 高さは有効にする。
            $("#obj_stl_type_id").prop("disabled", false);          // 建物種別は有効にする。
            $("#ButtonPreviewBuilding").prop("disabled", false);    // プレビューボタンは有効にする。
            $(target).prop("disabled", true);                       // 「元に戻す」ボタン自体は無効にする。
            $("#ButtonAddNewBuilding").prop("disabled", true);      // 「新規建物を作成」ボタンは無効にする。
        }

        /**
         * 元に戻す処理(サブ処理)
         *
         * @return
         */
        function _resetAddNewBuilding() {

            // プレビューフラグを無効にする。
            previewActivity = false;

            // プレビューボタンで追加表示されていた新規建物を非表示にして建物データを3D地図上に描画する。
            if (viewer) {
                clearBuilding(viewer);
            }

            // プレビューで保存した建物描画ようの座標をリセットする。
            hierarchy = [];
            positions = [];
        }

        /**
         * 「新規建物を作成」ボタン押下
         *
         * @param object target 「新規建物を作成」ボタン
         *
         * @return
         */
        function addNewBuilding(target)
        {
            // プレビューボタンはまだ押されていない場合、エラーを出す。
            if (!previewActivity) {
                // エラーメッセージダイアログを表示
                showDialog("E33", '{{ App\Commons\Message::$E33 }}');
                return;
            }

            $("#building_height").prop("disabled", false);          // 高さは有効にする。
            $("#obj_stl_type_id").prop("disabled", false);          // 建物種別は有効にする。
            $("#ButtonPreviewBuilding").prop("disabled", false);    // プレビューボタンは有効にする。
            $(target).prop("disabled", true);                       // 「新規建物を作成」ボタン自体は無効にする。
            $("#ButtonResetBuilding").prop("disabled", true);       // 「元に戻す」ボタンは無効にする。

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
            buildingProcessRequest(params, 2);

            // OBJ・STLファイル編集用の各項目をリセットする。
            resetObjStlSetting();
            // 元に戻す処理
            _resetAddNewBuilding();
        }

        /**
         * イベントハンドラを再設定する。
         *
         * @return
         */
        function reSettingHandler()
        {
            // 建物データ編集モードを取得
            const buildingEditMode = Number($("input[name='building_activity']:checked").val());

            // 建物データ編集モード: 1: 既存建物削除；2: 新規建物作成
            if (buildingEditMode == 1) {
                // 建物をクリックするイベントの設定を有効にする。
                if (viewer) {
                    handler = buildingClickEventSetting(viewer, deleteBuildings);
                }
            } else if (buildingEditMode == 2) {
                // 地面をクリックするイベントの設定を有効にする。
                if (viewer) {
                    handler = mapClickEventSetting(viewer, hierarchy, positions);
                }
            }
        }

        /**
         * 架空建物の操作リクエストを送る。
         *
         * @param array params リクエストパラメータ
         * @param int type 操作タイプ（削除:1; 新規追加：2）
         *
         * @return
         */
        function buildingProcessRequest(params, type = 1)
        {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })

            // リクエスト先
            let url = "";
            if (type == 1) {
                // 架空建物の削除
                url = "{{ route('building.delete') }}";
            } else if (type == 2) {
                // 架空建物の新規作成
                url = "{{ route('building.create') }}";
            }

            // リクエストを送る
            $.ajax({
                url: url,
                type: 'POST',
                data: params,
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
         * @return
         */
        function showDialog(code, msg, type="E")
        {
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
            }

            $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
            $("div#messageModal [class='modal-body'] span#message").html(msg);
            $('#messageModal').modal('show');
        }
    </script>
@endsection

{{-- モーダル配置のエリア --}}
@section('modal-area')
    @include('layouts.message_dialog')
@endsection
