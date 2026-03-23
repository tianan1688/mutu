<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

class Order extends Model
{
    // 对应数据库表名（根据实际表名调整）
    protected $name = 'resource_order';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}