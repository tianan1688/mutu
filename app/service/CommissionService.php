<?php
declare (strict_types=1);

namespace app\service;

use app\model\DistributorOrder;
use app\model\Distributor;

class CommissionService
{
    /**
     * 结算佣金（订单确认收货后）
     */
    public static function settle($orderId, $orderType)
    {
        $distOrder = DistributorOrder::where('order_id', $orderId)
            ->where('order_type', $orderType)
            ->where('status', 0)
            ->find();
        if (!$distOrder) {
            return false;
        }

        $distOrder->status = 1;
        $distOrder->settle_time = time();
        $distOrder->save();

        $distributor = Distributor::find($distOrder->distributor_id);
        $distributor->frozen_commission -= $distOrder->commission;
        $distributor->total_commission += $distOrder->commission;
        $distributor->save();

        MessageService::send($distributor->user_id, 'commission_arrival', [
            'commission' => $distOrder->commission,
            'total_commission' => $distributor->total_commission,
        ]);

        return true;
    }
}