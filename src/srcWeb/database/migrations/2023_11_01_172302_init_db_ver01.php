<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // (UA) ユーザアカウント
        Schema::create('user_account', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(UA) ユーザアカウント');

            // PK:ユーザID
            $table->string('user_id', 32)->primary()->comment('ユーザID');
            // パスワード
            $table->string('password', 32)->nullable()->comment('パスワード');
            // 表示名
            $table->string('display_name', 32)->nullable()->comment('表示名');
            // 備考
            $table->string('note', 256)->nullable()->comment('備考');
            // 最終更新日時
            $table->timestamp('last_update_datetime')->nullable()->comment('最終更新日時');
        });

        // (CM) 都市モデル
        Schema::create('city_model', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(CM) 都市モデル');

            // PK:都市モデルID
            $table->uuid('city_model_id')->default(DB::raw('gen_random_uuid()'))->primary()->comment('都市モデルID');
            // 識別名
            $table->string('identification_name', 32)->nullable()->comment('識別名');
            // 登録ユーザID
            $table->string('registered_user_id', 32)->comment('登録ユーザID');
            // 最終更新日時
            $table->timestamp('last_update_datetime')->nullable()->comment('最終更新日時');
            // プリセットフラグ
            $table->boolean('preset_flag')->nullable()->comment('プリセットフラグ');
            // URL
            $table->string('url', 256)->nullable()->comment('URL');

            // 外部キー(ユーザID)の制約を追加
            $table->foreign('registered_user_id')->references('user_id')->on('user_account')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        // (CR) 都市モデル参照権限
        Schema::create('city_model_reference_authority', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(CR) 都市モデル参照権限');

            // 都市モデルID
            $table->uuid('city_model_id')->comment('都市モデルID');
            // ユーザID
            $table->string('user_id', 32)->comment('ユーザID');
            // 登録日時
            $table->timestamp('registered_datetime')->nullable()->comment('登録日時');

            // PK設定
            $table->primary(['city_model_id', 'user_id']);

            // 外部キー(都市モデルID)の制約を追加
            $table->foreign('city_model_id')->references('city_model_id')->on('city_model')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            // 外部キー(ユーザID)の制約を追加
            $table->foreign('user_id')->references('user_id')->on('user_account')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        // (PC) 平面直角座標系
        Schema::create('coordinate', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(PC) 平面直角座標系');

            // 平面直角座標系ID
            $table->smallInteger('coordinate_id')->comment('平面直角座標系ID');
            // 平面直角座標系名
            $table->string('coordinate_name', 256)->nullable()->comment('平面直角座標系名');
            // 原点緯度：double precision
            $table->double('origin_latitude')->nullable()->comment('原点緯度');
            // 原点経度：double precision
            $table->double('origin_longitude')->nullable()->comment('原点経度');

            // PK設定
            $table->primary('coordinate_id');
        });

        // (CA) 解析対象地域
        Schema::create('region', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(CA) 解析対象地域');

            // PK:解析対象地域ID
            $table->uuid('region_id')->default(DB::raw('gen_random_uuid()'))->primary()->comment('解析対象地域ID');
            // FK:都市モデルID
            $table->uuid('city_model_id')->comment('都市モデルID');
            // 対象地域識別名
            $table->string('region_name', 32)->nullable()->comment('対象地域識別名');
            // FK:平面直角座標系ID
            $table->smallInteger('coordinate_id')->comment('平面直角座標系ID');

            // 南端緯度: double precision
            $table->double('south_latitude')->nullable()->comment('南端緯度');
            // 北端緯度: double precision
            $table->double('north_latitude')->nullable()->comment('北端緯度');
            // 西端経度: double precision
            $table->double('west_longitude')->nullable()->comment('西端経度');
            // 東端経度: double precision
            $table->double('east_longitude')->nullable()->comment('東端経度');
            // 地面高度
            $table->float('ground_altitude')->nullable()->comment('地面高度');
            // 上空高度
            $table->float('sky_altitude')->nullable()->comment('上空高度');

            // 外部キー(都市モデルID)の制約を追加
            $table->foreign('city_model_id')->references('city_model_id')->on('city_model')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(平面直角座標系ID)の制約を追加
            $table->foreign('coordinate_id')->references('coordinate_id')->on('coordinate')
            ->onUpdate('restrict')
            ->onDelete('restrict');
        });

        // (SC)熱流体解析ソルバ
        Schema::create('solver', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(SC)熱流体解析ソルバ');

            // PK:ソルバID
            $table->uuid('solver_id')->default(DB::raw('gen_random_uuid()'))->primary()->comment('ソルバID');
            // 識別名
            $table->string('solver_name', 64)->nullable()->comment('識別名');
            // ソルバ一式圧縮ファイル
            $table->string('solver_compressed_file', 256)->nullable()->comment('ソルバ一式圧縮ファイル');
            // FK:登録ユーザID
            $table->string('user_id', 32)->comment('登録ユーザID');
            // 登録日時
            $table->timestamp('upload_datetime')->nullable()->comment('登録日時');
            // プリセットフラグ
            $table->boolean('preset_flag')->nullable()->comment('プリセットフラグ');
            // 公開フラグ
            $table->boolean('disclosure_flag')->nullable()->comment('公開フラグ');

            // 外部キー(登録ユーザID)の制約を追加
            $table->foreign('user_id')->references('user_id')->on('user_account')
            ->onUpdate('restrict')
            ->onDelete('restrict');
        });

        // (SM) シミュレーションモデル
        Schema::create('simulation_model', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(SM) シミュレーションモデル');

            // PK:シミュレーションモデルID
            $table->uuid('simulation_model_id')->default(DB::raw('gen_random_uuid()'))->primary()->comment('シミュレーションモデルID');
            // 識別名
            $table->string('identification_name', 32)->nullable()->comment('識別名');
            // FK:都市モデルID
            $table->uuid('city_model_id')->comment('都市モデルID');
            // FK:解析対象地域ID
            $table->uuid('region_id')->comment('解析対象地域ID');
            // 登録ユーザID
            $table->string('registered_user_id', 32)->comment('登録ユーザID');
            // 最終更新日時
            $table->timestamp('last_update_datetime')->nullable()->comment('最終更新日時');
            // プリセットフラグ
            $table->boolean('preset_flag')->nullable()->comment('プリセットフラグ');
            // 外気温
            $table->float('temperature')->nullable()->comment('外気温');
            // 風速
            $table->float('wind_speed')->nullable()->comment('風速');
            // 風向き
            $table->smallInteger('wind_direction')->nullable()->comment('風向き');
            // 日付
            $table->date('solar_altitude_date')->nullable()->comment('日付');
            // 時刻
            $table->smallInteger('solar_altitude_time')->nullable()->comment('時刻');
            // 南端緯度: double precision
            $table->double('south_latitude')->nullable()->comment('南端緯度');
            // 北端緯度: double precision
            $table->double('north_latitude')->nullable()->comment('北端緯度');
            // 西端経度: double precision
            $table->double('west_longitude')->nullable()->comment('西端経度');
            // 東端経度: double precision
            $table->double('east_longitude')->nullable()->comment('東端経度');
            // 地面高度
            $table->float('ground_altitude')->nullable()->comment('地面高度');
            // 上空高度
            $table->float('sky_altitude')->nullable()->comment('上空高度');
            // FK:ソルバID
            $table->uuid('solver_id')->comment('ソルバID');
            // メッシュ粒度
            $table->smallInteger('mesh_level')->nullable()->comment('メッシュ粒度');
            // 実行ステータス
            $table->smallInteger('run_status')->nullable()->comment('実行ステータス');
            // 実行ステータス詳細
            $table->string('run_status_details', 1024)->nullable()->comment('実行ステータス詳細');
            // 熱流体解析エラーログファイル
            $table->string('cfd_error_log_file', 256)->nullable()->comment('熱流体解析エラーログファイル');
            // 最終シミュレーション開始日時
            $table->timestamp('last_sim_start_datetime')->nullable()->comment('最終シミュレーション開始日時');
            // 最終シミュレーション完了日時
            $table->timestamp('last_sim_end_datetime')->nullable()->comment('最終シミュレーション完了日時');

            // 外部キー(都市モデルID)の制約を追加
            $table->foreign('city_model_id')->references('city_model_id')->on('city_model')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(解析対象地域ID)の制約を追加
            $table->foreign('region_id')->references('region_id')->on('region')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(登録ユーザID)の制約を追加
            $table->foreign('registered_user_id')->references('user_id')->on('user_account')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(ソルバID)の制約を追加
            $table->foreign('solver_id')->references('solver_id')->on('solver')
            ->onUpdate('restrict')
            ->onDelete('restrict');
        });

        // (SR) シミュレーションモデル参照権限
        Schema::create('simulation_model_reference_authority', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(SR) シミュレーションモデル参照権限');

            // シミュレーションモデルID
            $table->uuid('simulation_model_id')->comment('シミュレーションモデルID');
            // FK:ユーザID
            $table->string('user_id', 32)->comment('ユーザID');
            // 最終更新日時
            $table->timestamp('last_update_datetime')->nullable()->comment('最終更新日時');

            // PK設定
            $table->primary(['simulation_model_id', 'user_id']);

            // 外部キー(シミュレーションモデルID)の制約を追加
            $table->foreign('simulation_model_id')->references('simulation_model_id')->on('simulation_model')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(ユーザID)の制約を追加
            $table->foreign('user_id')->references('user_id')->on('user_account')
            ->onUpdate('restrict')
            ->onDelete('restrict');
        });

        // (PH) 相対高さ
        Schema::create('height', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(PH) 相対高さ');

            // 相対高さID
            $table->smallInteger('height_id')->primary()->comment('相対高さID');
            // 相対高さ
            $table->float('height')->nullable()->comment('相対高さ');
        });

        // (PT) STLファイル種別
        Schema::create('stl_type', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(PT) STLファイル種別');

            // PK:STLファイル種別ID
            $table->smallInteger('stl_type_id')->primary()->comment('STLファイル種別ID');
            // 種別名
            $table->string('stl_type_name', 32)->nullable()->comment('種別名');
            // 必須フラグ
            $table->boolean('required_flag')->nullable()->comment('必須フラグ');
            // 地面フラグ
            $table->boolean('ground_flag')->nullable()->comment('地面フラグ');
        });

        // (CS) STLファイル
        Schema::create('stl_model', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(CS) STLファイル');

            // 解析対象地域ID
            $table->uuid('region_id')->comment('解析対象地域ID');
            // STLファイル種別ID
            $table->smallInteger('stl_type_id')->comment('STLファイル種別ID');
            // STLファイル
            $table->string('stl_file', 256)->nullable()->comment('STLファイル');
            // アップロード日時
            $table->timestamp('upload_datetime')->nullable()->comment('アップロード日時');

            // PK:解析対象地域IDとSTLファイル種別ID
            $table->primary(['region_id', 'stl_type_id']);

            // 外部キー(解析対象地域ID)の制約を追加
            $table->foreign('region_id')->references('region_id')->on('region')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(STLファイル種別ID)の制約を追加
            $table->foreign('stl_type_id')->references('stl_type_id')->on('stl_type')
            ->onUpdate('restrict')
            ->onDelete('restrict');

        });

        // (SV) 可視化ファイル
        Schema::create('visualization', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(SV) 可視化ファイル');

            // シミュレーションモデルID
            $table->uuid('simulation_model_id')->comment('シミュレーションモデルID');
            // 可視化種別
            $table->smallInteger('visualization_type')->comment('可視化種別');
            // 相対高さID
            $table->smallInteger('height_id')->comment('相対高さID');
            // 可視化ファイル
            $table->string('visualization_file', 256)->nullable()->comment('可視化ファイル');
            // シミュレーション結果（GeoJSON）ファイル
            $table->string('geojson_file', 256)->nullable()->comment('シミュレーション結果（GeoJSON）ファイル');
            // 凡例上端値
            $table->string('legend_label_higher', 16)->nullable()->comment('凡例上端値');
            // 凡例下端値
            $table->string('legend_label_lower', 16)->nullable()->comment('凡例下端値');

            // PK:シミュレーションモデルIDと可視化種別と相対高さID
            $table->primary(['simulation_model_id','visualization_type', 'height_id']);

            // 外部キー(シミュレーションモデルID)の制約を追加
            $table->foreign('simulation_model_id')->references('simulation_model_id')->on('simulation_model')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(相対高さID)の制約を追加
            $table->foreign('height_id')->references('height_id')->on('height')
            ->onUpdate('restrict')
            ->onDelete('restrict');
        });

        // (SA)日射吸収率
        Schema::create('solar_absorptivity', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(SA)日射吸収率');

            // シミュレーションモデルID
            $table->uuid('simulation_model_id')->comment('シミュレーションモデルID');
            // STLファイル種別ID
            $table->smallInteger('stl_type_id')->comment('STLファイル種別ID');
            // 日射吸収率
            $table->float('solar_absorptivity')->nullable()->comment('日射吸収率');

            // PK:シミュレーションモデルIDとSTLファイル種別ID
            $table->primary(['simulation_model_id', 'stl_type_id']);

            // 外部キー(シミュレーションモデルID)の制約を追加
            $table->foreign('simulation_model_id')->references('simulation_model_id')->on('simulation_model')
            ->onUpdate('restrict')
            ->onDelete('restrict');
            // 外部キー(STLファイル種別ID)の制約を追加
            $table->foreign('stl_type_id')->references('stl_type_id')->on('stl_type')
            ->onUpdate('restrict')
            ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_account');
        Schema::dropIfExists('city_model');
        Schema::dropIfExists('city_model_reference_authority');
        Schema::dropIfExists('gml');
        Schema::dropIfExists('coordinate');
        Schema::dropIfExists('region');
        Schema::dropIfExists('solver');
        Schema::dropIfExists('simulation_model');
        Schema::dropIfExists('simulation_model_reference_authority');
        Schema::dropIfExists('height');
        Schema::dropIfExists('stl_type');
        Schema::dropIfExists('stl_model');
        Schema::dropIfExists('visualization');
        Schema::dropIfExists('solar_absorptivity');
    }
};
