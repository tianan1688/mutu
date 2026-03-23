<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

class UserLevel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    protected $default = [
        'resource_discount' => 100 // 默认无折扣
    ];
    // 获取会员折扣后价格
    public function getDiscountPrice($price)
    {
        $discount = $this->resource_discount ?? 100;
        if ($this->resource_discount < 100) {
            return round($price * $this->resource_discount / 100, 2);
        }
        
        return $price;
    }
}