<?php
namespace app\model;

use think\Model;

class InviteRecord extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invite_user_id');
    }

    public function invited()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }
}