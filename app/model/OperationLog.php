<?php
namespace app\model;

use think\Model;

class OperationLog extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}