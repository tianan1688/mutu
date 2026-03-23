<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

class Resource extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $field = true;
    protected $append = ['is_member_only', 'view_count'];
    
    public function category()
    {
        return $this->belongsTo(ResourceCategory::class, 'cat_id', 'id');
    }

    // 获取最终售价（考虑会员折扣）
    public function getFinalPrice($user = null)
    {
        if ($this->type == 2) {
            return null; 
        }
        if ($user && $user->isMemberValid()) { 
            $level = UserLevel::find($user->level_id);
            if ($level) {
                return $level->getDiscountPrice($this->price);
            }
        }
        return $this->price;
    }

    // 是否会员专属
    public function getIsMemberOnlyAttr()
    {
        return $this->type == 2;
    }

    
    public function getViewCountAttr($value)
    {
        return $value ?? 0;
    }
}