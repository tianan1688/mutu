<?php
namespace app\model;

use think\Model;

class MessageLog extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'send_time';
    protected $updateTime = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}