<?php
namespace app\model;

use think\Model;

class ResourceMaterial extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
}