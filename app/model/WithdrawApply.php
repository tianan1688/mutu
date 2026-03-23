<?php
namespace app\model;

use think\Model;

class WithdrawApply extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function distributor()
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }
}