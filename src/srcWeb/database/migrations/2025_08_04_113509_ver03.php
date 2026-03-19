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
        // (IM)インフォメーション
        Schema::create('information', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(IM)インフォメーション');

            // PK:インフォメーションid
            $table->smallInteger('information_id')->primary()->comment('インフォメーションid');
            // インフォメーション
            $table->string('information', 1024)->nullable()->comment('インフォメーション');
        });

        // (TT)単独木分類
        Schema::create('tree_type', function (Blueprint $table) {

            // テーブルにコメント追加
            $table->comment('(TT)単独木分類');

            // PK:単独木分類ID
            $table->smallInteger('tree_type_id')->primary()->comment('単独木分類ID');
            // 単独木分類名
            $table->string('tree_type_name', 256)->nullable()->comment('単独木分類名');
            // 樹冠直径
            $table->float('canopy_diameter')->nullable()->comment('樹冠直径');
            // 高さ
            $table->float('height')->nullable()->comment('高さ');
        });

        // (PT) STLファイル種別
        Schema::table('stl_type', function (Blueprint $table) {
            // 単独木フラグ
            $table->boolean('tree_flag')->nullable()->after('ground_flag')->comment('単独木フラグ');
            // 植被フラグ
            $table->boolean('plant_cover_flag')->nullable()->after('tree_flag')->comment('植被フラグ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // (IM)インフォメーション
        Schema::dropIfExists('information');
        // (TT)単独木分類
        Schema::dropIfExists('tree_type');
        // (PT) STLファイル種別
        Schema::table('stl_type', function (Blueprint $table) {
            // 単独木フラグ
            $table->dropColumn('tree_flag');
            // 植被フラグ
            $table->dropColumn('plant_cover_flag');
        });

    }
};
