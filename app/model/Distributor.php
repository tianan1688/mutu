<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

class Distributor extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // 增加佣金（冻结）
    public function addCommission($amount, $frozen = true)
    {
        if ($frozen) {
            $this->frozen_commission = $this->frozen_commission + $amount;
        } else {
            $this->total_commission = $this->total_commission + $amount;
        }
        return $this->save();
    }

    // 解冻佣金
    public function unfreezeCommission($amount)
    {
        $this->frozen_commission = $this->frozen_commission - $amount;
        $this->total_commission = $this->total_commission + $amount;
        return $this->save();
    }
}