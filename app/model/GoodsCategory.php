<?php
namespace app\model;

use think\Model;

class GoodsCategory extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function parent()
    {
        return $this->belongsTo(GoodsCategory::class, 'pid');
    }

    public function children()
    {
        return $this->hasMany(GoodsCategory::class, 'pid');
    }

    public function goods()
    {
        return $this->hasMany(Goods::class, 'cat_id');
    }
}