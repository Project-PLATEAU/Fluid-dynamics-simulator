<?php

namespace App\Http\Controllers;

/**
 * ホーム画面用のコントロール
 */
class HomeController extends BaseController
{
    /**
     * ホーム画面の初期表示
     */
    public function index()
    {
        return view('home.index');
    }
}