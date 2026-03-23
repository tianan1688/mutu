<?php
namespace app\model;

use think\Model;

class IpBlacklist extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
}