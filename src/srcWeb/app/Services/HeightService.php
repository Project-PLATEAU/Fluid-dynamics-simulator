<?php


namespace App\Services;

use App\Models\Db\Height;

/**
 * 相対高さサービス
 */
class HeightService extends BaseService
{
    /**
     * 相対高さを全て取得
     * @return '\App\Models\DB\Height
     */
    public static function getAll()
    {
        return Height::orderBy('height', 'asc')->get();
    }
}
