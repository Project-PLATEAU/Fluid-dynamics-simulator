<?php
namespace App\Traits;

use Exception;

/**
 * LaravelのORMでは複合PKは推奨されておらず、保存できないため、
 * モデルを複合主キーで適切に動作させるには、該当のModelファイル内で下記のTraitの特性を使って読み込む
 * ※参考：https://qiita.com/derasado/items/ff692411ef50f222af32
 */
trait HasCompositePrimaryKeyTrait {

    // Get the value indicating whether the IDs are incrementing.
    public function getIncrementing()
    {
        return false;
    }

    //Set the keys for a save update query.
    protected function setKeysForSaveQuery($query)
    { //edit Builder $query to $query
        foreach ($this->getKeyName() as $key) {
                // UPDATE: Added isset() per devflow's comment.
            if (isset($this->$key)) {
                $query->where($key, '=', $this->$key);
            } else {
                throw new Exception(__METHOD__ . 'Missing part of the primary key: ' . $key);
            }
        }
        return $query;
    }


    protected function getKeyForSaveQuery($keyName = null)
    {

        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }


    // Execute a query for a single record by ID.
    public static function find($ids, $columns = ['*'])
    {
        $me = new self;
        $query = $me->newQuery();

        foreach ($me->getKeyName() as $key) {
            $query->where($key, '=', $ids[$key]);
        }

        return $query->first($columns);
    }
}
