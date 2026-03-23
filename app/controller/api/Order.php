<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\GoodsOrder;

class Order extends BaseController
{
    public function lists()
    {
        $user = $this->request->user;
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $status = $this->request->get('status', ''); // all, unpaid, shipped, received

        $query = GoodsOrder::with('details')->where('user_id', $user->id);
        switch ($status) {
            case 'unpaid':
                $query->where('pay_status', 0);
                break;
            case 'shipped':
                $query->where('pay_status', 1)->where('shipping_status', 1);
                break;
            case 'received':
                $query->where('pay_status', 1)->where('shipping_status', 2);
                break;
            default:
                // all
                break;
        }
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }

    public function detail()
    {
        $id = $this->request->get('id');
        $user = $this->request->user;
        $order = GoodsOrder::with('details')->where('id', $id)->where('user_id', $user->id)->find();
        if (!$order) return json_error('订单不存在');
        return json_success('ok', $order);
    }

    public function refund()
    {
        $id = $this->request->post('id');
        $reason = $this->request->post('reason');
        $user = $this->request->user;
        $order = GoodsOrder::where('id', $id)->where('user_id', $user->id)->find();
        if (!$order) return json_error('订单不存在');
        if ($order->pay_status != 1) return json_error('订单未支付');
        if ($order->refund_status != 0) return json_error('已申请退款');

        $order->refund_status = 1; // 申请中
        $order->refund_reason = $reason;
        $order->save();

        return json_success('退款申请已提交');
    }

    public function logistics()
    {
        $id = $this->request->get('id');
        $user = $this->request->user;
        $order = GoodsOrder::where('id', $id)->where('user_id', $user->id)->find();
        if (!$order) return json_error('订单不存在');
        if ($order->shipping_status == 0) return json_error('未发货');
        // 这里可以对接物流查询API
        $data = [
            'shipping_code' => $order->shipping_code,
            'shipping_company' => $order->shipping_company,
            'status' => $order->shipping_status == 1 ? '已发货' : '已收货',
        ];
        return json_success('ok', $data);
    }
}