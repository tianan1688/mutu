<?php
namespace app\model;

use think\Model;

class BalanceLog extends Model
{
    // 对应数据库表名（根据实际表名调整）
    protected $name = 'balance_log';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false; // 余额日志不更新
    
    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}