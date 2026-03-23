<?php
namespace app\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'mobile' => 'require|mobile',
        'password' => 'require|length:6,20',
        'code' => 'require|length:6',
    ];

    protected $message = [
        'mobile.require' => '手机号不能为空',
        'mobile.mobile' => '手机号格式错误',
        'password.require' => '密码不能为空',
        'password.length' => '密码长度6-20位',
        'code.require' => '验证码不能为空',
    ];

    protected $scene = [
        'login' => ['mobile', 'password'],
        'register' => ['mobile', 'password', 'code'],
        'forget' => ['mobile', 'password', 'code'],
    ];
}