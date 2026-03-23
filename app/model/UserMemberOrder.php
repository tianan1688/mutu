<?php
namespace app\model;

use think\Model;

class UserMemberOrder extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'level_id');
    }
}