<?php
namespace app\model;

use think\Model;

class ResourceCategory extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function parent()
    {
        return $this->belongsTo(ResourceCategory::class, 'pid');
    }

    public function children()
    {
        return $this->hasMany(ResourceCategory::class, 'pid');
    }

    public function resources()
    {
        return $this->hasMany(Resource::class, 'cat_id');
    }
}