<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

class GoodsValidate extends Validate
{
    protected $rule = [
        'title' => 'require|max:200',
        'cat_id' => 'require|number',
        // 优化1：用自定义规则兼容字符串转浮点数，替代原生float规则
        'price' => 'require|checkPrice|>=0',
        'stock' => 'require|integer|>=0',
    ];

    protected $message = [
        'title.require' => '商品名称不能为空',
        'title.max' => '商品名称不能超过200个字符',
        'cat_id.require' => '请选择分类',
        'cat_id.number' => '分类ID必须为数字',
        'price.require' => '请输入价格',
        // 优化2：补充price规则的具体错误提示（关键！）
        'price.checkPrice' => '价格必须为有效的数字（如 0.01、99.9）',
        'price.>=' => '价格不能小于0',
        'stock.require' => '请输入库存',
        'stock.integer' => '库存必须为整数',
        'stock.>=' => '库存不能小于0',
    ];

    // 自定义price校验规则：兼容字符串转浮点数
    protected function checkPrice($value): bool
    {
        // 1. 先过滤空值（已由require规则校验，此处兜底）
        if (empty($value)) return false;
        // 2. 尝试将字符串转为浮点数（兼容前端传入的"99.9"）
        $price = is_numeric($value) ? (float)$value : -1;
        // 3. 验证是否为有效浮点数（排除NaN、Infinity）
        return is_float($price) && !is_nan($price) && !is_infinite($price);
    }
}
