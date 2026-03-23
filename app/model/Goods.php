<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

class Goods extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    public function category()
    {
        return $this->belongsTo(GoodsCategory::class, 'cat_id', 'id');
    }

    // 减库存
    public function decStock($num = 1)
    {
        $this->stock = $this->stock - $num;
        $this->sales = $this->sales + $num;
        return $this->save();
    }
}
