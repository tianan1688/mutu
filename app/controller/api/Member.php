<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\UserLevel;
use app\model\UserMemberOrder;
use app\model\User;

class Member extends BaseController
{
    /**
     * 会员套餐列表
     */
    public function levels()
    {
        $list = UserLevel::where('status', 1)->order('sort', 'asc')->select();
        return json_success('ok', $list);
    }

    /**
     * 购买会员
     */
    public function buy()
    {
        $levelId = $this->request->post('level_id');
        $payType = $this->request->post('pay_type', 'balance'); // balance, wechat, alipay
        $user = $this->request->user;

        $level = UserLevel::find($levelId);
        if (!$level || $level->status != 1) {
            return json_error('套餐不存在');
        }

        $price = $level->price;
        $orderNo = generate_order_no('M');

        // 创建订单
        $order = UserMemberOrder::create([
            'order_no' => $orderNo,
            'user_id' => $user->id,
            'level_id' => $levelId,
            'price' => $price,
            'pay_type' => $payType,
            'pay_status' => 0,
            'expire_time' => $level->duration_type == 'forever' ? 0 : (time() + $level->days * 86400),
            'create_time' => time(),
            'update_time' => time(),
        ]);

        if ($payType == 'balance') {
            // 余额支付
            if ($user->balance < $price) {
                return json_error('余额不足');
            }
            // 扣除余额
            $user->balance -= $price;
            $user->save();

            // 更新订单状态
            $order->pay_status = 1;
            $order->pay_time = time();
            $order->save();

            // 更新用户会员
            $this->updateUserMember($user, $level, $order->expire_time);

            // 记录余额流水
            \app\model\BalanceLog::create([
                'user_id' => $user->id,
                'before' => $user->balance + $price,
                'after' => $user->balance,
                'amount' => -$price,
                'remark' => '购买会员',
                'create_time' => time(),
            ]);

            return json_success('支付成功', ['order_no' => $orderNo]);
        } else {
            // 微信/支付宝支付，返回支付参数（需对接支付）
            // 这里简化，返回订单号
            return json_success('下单成功', ['order_no' => $orderNo, 'pay_type' => $payType]);
        }
    }

    private function updateUserMember($user, $level, $expireTime)
    {
        $user->level_id = $level->id;
        $user->expire_time = $expireTime;
        $user->save();

        // 发送站内信
        \app\service\MessageService::send($user->id, 'member_renew', [
            'level_name' => $level->name,
            'expire_time' => $expireTime ? date('Y-m-d', $expireTime) : '永久',
        ]);
    }
}