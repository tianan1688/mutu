<?php
namespace app\model;

use think\Model;

class GoodsOrderDetail extends Model
{
    protected $autoWriteTimestamp = false;

    public function order()
    {
        return $this->belongsTo(GoodsOrder::class, 'order_id');
    }

    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }
}