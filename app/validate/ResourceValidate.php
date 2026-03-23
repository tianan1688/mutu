<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

class ResourceValidate extends Validate
{
    protected $rule = [
        'title' => 'require|max:200',
        'cat_id' => 'require|number',
        'file_path' => 'require',
        'price' => 'requireIf:type,1|float|>=0',
    ];

    protected $message = [
        'title.require' => '标题不能为空',
        'cat_id.require' => '请选择分类',
        'file_path.require' => '请上传文件',
        'price.requireIf' => '付费资源请填写价格',
    ];
}