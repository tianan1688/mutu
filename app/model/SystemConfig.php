<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

class SystemConfig extends Model
{
    protected $autoWriteTimestamp = true;

    // 获取值，自动转换类型
    public function getValueAttr($value)
    {
        if (is_numeric($value)) {
            return $value + 0; // 转数字
        }
        return $value;
    }
}
