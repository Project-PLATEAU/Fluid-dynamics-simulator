<?php

namespace App\Http\Controllers;

use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;

/**
 * CityGml関連用のコントロール
 */
class GmlController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     *
     * CityGMLファイル
     *
     * @param Request $request リクエスト
     * @param integer $id 都市モデルID
     *
     * @return [type]
     */
    public function store(Request $request, $id)
    {
        try {
            $errorMessage = [];

            // 識別名

            // 画面遷移
            if ($errorMessage) {
            } else {
                // 識別名更新
            }
            return redirect()->route('city_model.edit', ['id' => $id, 'registered_user_id' => request()->query('registered_user_id')])->with(['message' => $errorMessage]);
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('error'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
