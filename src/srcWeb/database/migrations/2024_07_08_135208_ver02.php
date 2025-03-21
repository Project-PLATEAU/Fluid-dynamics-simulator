<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // (CA) 解析対象地域
        Schema::table('region', function (Blueprint $table) {
            // ユーザID(※「対象地域識別名」の後ろにする。)
            $table->string('user_id', 32)->default('testuser')->after('region_name')->comment('ユーザID');

            // 外部キー(ユーザID)の制約を追加
            $table->foreign('user_id')->references('user_id')->on('user_account')
            ->onUpdate('restrict')
            ->onDelete('restrict');
        });

        // (CS) STLファイル
        Schema::table('stl_model', function (Blueprint $table) {
            // czmlファイル(※「排熱量」の後ろにする。)
            $table->string('czml_file', 256)->nullable()->after('heat_removal')->comment('czmlファイル');
        });

        // (SM) シミュレーションモデル
        Schema::table('simulation_model', function (Blueprint $table) {
            // 湿度(※「風向き」の後ろにする。)
            $table->float('humidity')->default(50)->nullable()->after('wind_direction')->comment('湿度');
        });

        // (SV) 可視化ファイル
        Schema::table('visualization', function (Blueprint $table) {
            // 凡例種別(※「相対高さID」の後ろにする。)
            $table->smallInteger('legend_type')->default(1)->after('height_id')->comment('凡例種別');
            // 「凡例種別」も主キーに入れるため、既存の主キーを削除してから、新しい複合主キーを設定
            $table->dropPrimary(['simulation_model_id', 'visualization_type', 'height_id']); // 現在の複合主キーのカラム名
            $table->primary(['simulation_model_id', 'visualization_type', 'height_id', 'legend_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // (CA) 解析対象地域
        Schema::table('region', function (Blueprint $table) {

            // 外部キー制約を削除
            $table->dropForeign(['user_id']);
            // 「ユーザID」カラムを削除
            $table->dropColumn('user_id');
        });

        // (CS) STLファイル
        Schema::table('stl_model', function (Blueprint $table) {

            //「czmlファイル」カラムを削除
            $table->dropColumn('czml_file');
        });

        // (SM) シミュレーションモデル
        Schema::table('simulation_model', function (Blueprint $table) {

            // 「湿度」カラムを削除
            $table->dropColumn('humidity');
        });

        // (SV) 可視化ファイル
        Schema::table('visualization', function (Blueprint $table) {

            // 新しい複合主キーを削除
            $table->dropPrimary(['simulation_model_id', 'visualization_type', 'height_id', 'legend_type']);

            // 「凡例種別」カラムを削除
            $table->dropColumn('legend_type');

            // 元の複合主キーを再設定
            $table->primary(['simulation_model_id', 'visualization_type', 'height_id']);
        });
    }
};
