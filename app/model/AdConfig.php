<?php
namespace app\model;

use think\Model;

class AdConfig extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}