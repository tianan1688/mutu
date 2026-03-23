<?php
declare (strict_types=1);

namespace app\service;

use app\model\GoodsOrder;
use app\model\GoodsOrderDetail;
use app\model\Goods;
use think\facade\Db;

class OrderService
{
    /**
     * 创建商城订单
     */
    public static function createMallOrder($userId, $goodsId, $num = 1, $address = [])
    {
        $goods = Goods::find($goodsId);
        if (!$goods || $goods->status != 1) {
            return ['code' => 0, 'msg' => '商品不存在或已下架'];
        }
        if ($goods->stock < $num) {
            return ['code' => 0, 'msg' => '库存不足'];
        }

        $totalPrice = $goods->price * $num;
        $orderNo = GoodsOrder::generateOrderNo();

        // 开启事务
        Db::startTrans();
        try {
            // 创建订单
            $order = GoodsOrder::create([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'total_price' => $totalPrice,
                'actual_pay' => $totalPrice,
                'pay_status' => 0,
                'receive_name' => $address['name'] ?? '',
                'receive_mobile' => $address['mobile'] ?? '',
                'receive_address' => $address['address'] ?? '',
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 订单详情
            GoodsOrderDetail::create([
                'order_id' => $order->id,
                'goods_id' => $goodsId,
                'goods_title' => $goods->title,
                'goods_cover' => $goods->cover,
                'price' => $goods->price,
                'num' => $num,
            ]);

            // 减库存
            $goods->decStock($num);

            Db::commit();
            return ['code' => 1, 'order' => $order];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => '订单创建失败：' . $e->getMessage()];
        }
    }

    /**
     * 支付成功处理
     */
    public static function paySuccess($orderNo, $payType, $payData = [])
    {
        $order = GoodsOrder::where('order_no', $orderNo)->find();
        if (!$order || $order->pay_status == 1) {
            return false;
        }

        $order->pay_status = 1;
        $order->pay_time = time();
        $order->pay_type = $payType;
        $order->save();

        // 发送站内信
        MessageService::send($order->user_id, 'order_pay', [
            'order_no' => $orderNo,
            'amount' => $order->actual_pay,
        ]);

        // 处理分销佣金
        self::handleCommission($order);

        return true;
    }

    /**
     * 处理分销佣金
     */
    private static function handleCommission($order)
    {
        // 查找下单用户是否有上级（邀请人）
        $user = \app\model\User::find($order->user_id);
        if (!$user || !$user->pid) {
            return;
        }
        // 检查上级是否是分销商
        $distributor = \app\model\Distributor::where('user_id', $user->pid)->where('status', 1)->find();
        if (!$distributor) {
            return;
        }

        // 计算佣金
        $rate = get_config('commission_mall_rate', 5); // 百分比
        $commission = round($order->actual_pay * $rate / 100, 2);
        if ($commission <= 0) {
            return;
        }

        // 创建分销订单（待结算）
        \app\model\DistributorOrder::create([
            'order_no' => $order->order_no,
            'order_type' => 'mall',
            'order_id' => $order->id,
            'distributor_id' => $distributor->id,
            'user_id' => $order->user_id,
            'amount' => $order->actual_pay,
            'commission_rate' => $rate,
            'commission' => $commission,
            'status' => 0, // 待结算
            'create_time' => time(),
        ]);

        // 增加冻结佣金
        $distributor->addCommission($commission, true);
    }
}