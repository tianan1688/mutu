<?php
namespace app\model;

use think\Model;

class User extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 关联会员等级
    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'level_id');
    }

    // 修复：获取会员等级名称（关键）
    public function getLevelNameAttr($value, $data)
    {
        // 1. 有等级ID且关联了等级数据
        if ($data['level_id'] > 0 && $this->level) {
            // 2. 判断是否为永久会员或有效期内
            if ($this->isMemberValid()) {
                return $this->level->name;
            }
        }
        return '普通用户';
    }

    // 密码自动加密
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    // 修复：检查会员是否有效（包含永久会员逻辑）
    public function isMemberValid()
    {
        // level_id>0 且（expire_time=0（永久） 或 expire_time>当前时间）
        return $this->level_id > 0 && ($this->expire_time == 0 || $this->expire_time > time());
    }

    // 关联邀请人
    public function inviter()
    {
        return $this->belongsTo(User::class, 'pid');
    }
}