<?php
namespace app\model;

use think\Model;

class MessageTemplate extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}