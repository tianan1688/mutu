<?php
namespace app\model;

use think\Model;
use app\model\User;

class PointRecord extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'updateTime';


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}