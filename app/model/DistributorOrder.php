<?php
namespace app\model;

use think\Model;

class DistributorOrder extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function distributor()
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}