<?php
declare (strict_types=1);

namespace app\service;

class PaymentService
{
    /**
     * 微信支付统一下单
     */
    public static function wechatPay($orderNo, $amount, $openid = '')
    {
        // 需接入微信支付SDK，这里返回模拟数据
        return [
            'code' => 1,
            'data' => [
                'appId' => 'wx...',
                'timeStamp' => (string)time(),
                'nonceStr' => uniqid(),
                'package' => 'prepay_id=...',
                'signType' => 'MD5',
                'paySign' => '...',
            ]
        ];
    }

    /**
     * 支付宝支付
     */
    public static function alipay($orderNo, $amount, $subject = '商品')
    {
        // 需接入支付宝SDK
        return [
            'code' => 1,
            'data' => 'alipay_sdk...'
        ];
    }
}
