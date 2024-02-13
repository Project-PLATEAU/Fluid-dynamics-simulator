<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

// アクションの前後に開始・終了ログを自動で挟むようにmiddleware log groupを設定
Route::middleware(['log'])->group(function () {

    /**
     * ログイン画面
     */
    Route::get('/', 'App\Http\Controllers\LoginController@index')->name('login.index');
    Route::post('/login', 'App\Http\Controllers\LoginController@login')->name('login.login');
    Route::get('/logout', 'App\Http\Controllers\LoginController@logout')->name('login.logout');

    // ログインせずに直接URLアクセスすると、強制的にログイン画面を求める。
    Route::middleware(['check_login'])->group(function () {

        /**
         * ホーム画面(index)
         */
        Route::get('/home', 'App\Http\Controllers\HomeController@index')->name('home.index');

        /**
         * 都市モデル一覧画面(index)
         */
        Route::get('/city_model', 'App\Http\Controllers\CityModelController@index')->name('city_model.index');

        /**
         * 都市モデル追加画面
         */
        Route::get('/city_model/create', 'App\Http\Controllers\CityModelController@create')->name('city_model.create');
        Route::post('/city_model/create', 'App\Http\Controllers\CityModelController@store')->name('city_model.addnew');

        /**
         * 都市モデル閲覧画面
         */
        Route::get('/city_model/show/{id}', 'App\Http\Controllers\CityModelController@show')->name('city_model.show');

        /**
         * 都市モデル一覧画面(削除)
         */
        Route::get('/city_model/delete/{id}', 'App\Http\Controllers\CityModelController@destroy')->name('city_model.delete');

        /**
         * 都市モデル画面(共有)
         */
        Route::get('/city_model/share/{id}', 'App\Http\Controllers\CityModelController@share')->name('city_model.share');

        /**
         * 都市モデル画面(シミュレーションモデル作成)
         */
        Route::get('/city_model/simulationCreate/{id}', 'App\Http\Controllers\CityModelController@simulationCreate')->name('city_model.simulationCreate');

        /**
         * 都市モデル付帯情報編集画面
         */
        Route::get('/city_model/edit/{id}', 'App\Http\Controllers\CityModelController@edit')->name('city_model.edit');
        Route::post('/city_model/edit/{id}', 'App\Http\Controllers\CityModelController@update')->name('city_model.update');
        Route::post('/city_model/upload/gml_file/{id}', 'App\Http\Controllers\CityModelController@uploadGmlFile')->name('city_model.uploadGmlFile');
        Route::post('/city_model/delete/gml_file/{id}', 'App\Http\Controllers\CityModelController@deleteGmlFile')->name('city_model.deleteGmlFile');

        Route::post('/region/create/{city_model_id}', 'App\Http\Controllers\RegionController@store')->name('region.addnew');
        Route::post('/region/delete/{city_model_id}/{region_id}', 'App\Http\Controllers\RegionController@destroy')->name('region.delete');
        Route::post('/region/stl/upload/{city_model_id}/{region_id}', 'App\Http\Controllers\RegionController@uploadStlFile')->name('region.upload_stl');
        Route::post('/region/stl/load/{region_id}', 'App\Http\Controllers\RegionController@updateStlInfo')->name('region.update_stl_info');
        Route::post('/region/stl/delete/{city_model_id}/{region_id}', 'App\Http\Controllers\RegionController@destroyStlFile')->name('region.delete_stl_file');
        Route::post('/region/update/{city_model_id}/{region_id}', 'App\Http\Controllers\RegionController@update')->name('region.update');

        /**
         * シミュレーションモデル作成画面(index)
         */
        Route::get('/simulation_model/create/{city_model_id}', 'App\Http\Controllers\SimulationModelController@create')->name('simulation_model.create');

        /**
         * シミュレーションモデル作成画面(追加)
         */
        Route::post('/simulation_model/create/{city_model_id}', 'App\Http\Controllers\SimulationModelController@store')->name('simulation_model.addnew');

        /**
         * シミュレーションモデル一覧画面(index)
         */
        Route::get('/simulation_model', 'App\Http\Controllers\SimulationModelController@index')->name('simulation_model.index');
        /**
         * シミュレーションモデル複製
         */
        Route::get('/simulation_model/copy/{id}', 'App\Http\Controllers\SimulationModelController@copy')->name('simulation_model.copy');
        /**
         * シミュレーションモデル編集画面
         */
        Route::get('/simulation_model/edit/{id}', 'App\Http\Controllers\SimulationModelController@edit')->name('simulation_model.edit');
        /**
         * シミュレーションモデル編集画面(保存)
         */
        Route::post('/simulation_model/edit/{id}', 'App\Http\Controllers\SimulationModelController@update')->name('simulation_model.update');
        /**
         * シミュレーションモデル一覧画面(削除)
         */
        Route::get('/simulation_model/delete/{id}', 'App\Http\Controllers\SimulationModelController@destroy')->name('simulation_model.delete');
        /**
         * シミュレーションモデル一覧画面(共有)
         */
        Route::get('/simulation_model/share/{id}', 'App\Http\Controllers\SimulationModelController@share')->name('simulation_model.share');
        /**
         * シミュレーションモデル一覧画面(シミュレーション開始)
         */
        Route::get('/simulation_model/start/{id}', 'App\Http\Controllers\SimulationModelController@start')->name('simulation_model.start');
        /**
         * シミュレーションモデル一覧画面(ステータス詳細)
         */
        Route::get('/simulation_model/status_detail/{id}', 'App\Http\Controllers\SimulationModelController@statusDetail')->name('simulation_model.status_detail');
        /**
         * シミュレーションモデル一覧画面(中止)
         */
        Route::get('/simulation_model/stop/{id}', 'App\Http\Controllers\SimulationModelController@stop')->name('simulation_model.stop');
        /**
         * シミュレーション結果閲覧画面(初期表示)
         */
        Route::get('/simulation_model/show/{id}', 'App\Http\Controllers\SimulationModelController@show')->name('simulation_model.show');
        /**
         * シミュレーション結果閲覧画面(可視化種別により表示)
         */
        Route::get('/simulation_model/show/change_mode/{id}', 'App\Http\Controllers\SimulationModelController@changeShowMode')->name('simulation_model.change_mode');
        /**
         * シミュレーション結果閲覧画面(ダウンロード)
         */
        Route::get('/simulation_model/show/download/{id}', 'App\Http\Controllers\SimulationModelController@download')->name('simulation_model.download');

        /*
         * モデル共有画面
         */
        Route::get('/share_model', 'App\Http\Controllers\ShareModelController@index')->name('share.index');
        /**
         * モデル共有画面(追加)
         */
        Route::post('/share_model/addnew/{share_mode}/{model_id}', 'App\Http\Controllers\ShareModelController@store')->name('share.addnew');
        /**
         * モデル共有画面(解除)
         */
        Route::post('/share_model/delete/{share_mode}/{model_id}', 'App\Http\Controllers\ShareModelController@destroy')->name('share.delete');

        /**
         * 熱流体解析ソルバ一覧画面(index)
         */
        Route::get('/solver', 'App\Http\Controllers\SolverController@index')->name('solver.index');
        /**
         * 熱流体解析ソルバ一覧画面(追加)
         */
        Route::post('/solver/addnew', 'App\Http\Controllers\SolverController@store')->name('solver.addnew');
        /**
         * 熱流体解析ソルバ一覧画面(更新)
         */
        Route::post('/solver/update/{id}', 'App\Http\Controllers\SolverController@update')->name('solver.update');
        /**
         * 熱流体解析ソルバ一覧画面(公開)
         */
        Route::post('/solver/public/{id}', 'App\Http\Controllers\SolverController@public')->name('solver.public');
        /**
         * 熱流体解析ソルバ一覧画面(削除)
         */
        Route::post('/solver/delete/{id}', 'App\Http\Controllers\SolverController@destroy')->name('solver.delete');
        /**
         * 熱流体解析ソルバ一覧画面(ダウンロード)
         */
        Route::post('/solver/download/{id}', 'App\Http\Controllers\SolverController@download')->name('solver.download');
    });
});
