<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

class GoodsOrder extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    public function details()
    {
        return $this->hasMany(GoodsOrderDetail::class, 'order_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // 生成订单号
    public static function generateOrderNo()
    {
        return 'G' . date('YmdHis') . mt_rand(1000, 9999);
    }
}