<?php
namespace app\model;

use think\Model;

class AdStat extends Model
{
    protected $autoWriteTimestamp = false;

    public function ad()
    {
        return $this->belongsTo(AdConfig::class, 'ad_id');
    }
}