<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\GoodsOrder;
use app\model\UserMemberOrder;
use app\model\Order as ResourceOrder; // 资源订单（如下载付费记录）
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Order extends BaseController
{
    /**
     * 资源订单（下载单、会员单）
     */
    public function resource()
    {
        try {
            // 核心修复：数字型参数强制转为整数，字符串参数做trim处理
            $type = trim($this->request->get('type', 'member')); // member 或 download
            $page = (int)$this->request->get('page', 1);        // 修复第33行page参数类型（字符串→整数）
            $limit = (int)$this->request->get('limit', 20);     // limit同步转换
            $status = $this->request->get('pay_status', '');    // 支付状态（保留空值，非空时转整数）
            $keyword = trim($this->request->get('keyword', ''));// 关键词去空格

            // 非空时转换status为整数，避免字符串匹配
            if ($status !== '') $status = (int)$status;

            if ($type == 'member') {
                $query = UserMemberOrder::with(['user', 'level']);
                if ($status !== '') $query->where('pay_status', $status);
                if ($keyword) {
                    $query->where('order_no', 'like', "%$keyword%");
                }
                $total = $query->count();
                // 第33行：此时page/limit均为整数，无类型错误
                $list = $query->order('id', 'desc')->page($page, $limit)->select();
            } else {
                // 下载付费订单（需要创建对应模型）
                $query = ResourceOrder::with('user');
                // 类似处理
                $total = 0;
                $list = [];
            }

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            // 开发环境返回具体错误，便于排查
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 商城订单
     */
    public function mall()
    {
        try {
            // 核心修复：所有数字型参数转为整数
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $status = $this->request->get('pay_status', '');
            $shippingStatus = $this->request->get('shipping_status', '');
            $keyword = trim($this->request->get('keyword', ''));

            // 非空时转换为整数
            if ($status !== '') $status = (int)$status;
            if ($shippingStatus !== '') $shippingStatus = (int)$shippingStatus;

            $query = GoodsOrder::with('user');
            if ($status !== '') $query->where('pay_status', $status);
            if ($shippingStatus !== '') $query->where('shipping_status', $shippingStatus);
            if ($keyword) {
                $query->where('order_no', 'like', "%$keyword%")
                      ->whereOr('receive_name', 'like', "%$keyword%")
                      ->whereOr('receive_mobile', 'like', "%$keyword%");
            }
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 发货
     */
    public function ship()
    {
        // 优化：参数转为整数/去空格
        $id = (int)$this->request->post('id');
        $shippingCode = trim($this->request->post('shipping_code', ''));
        $shippingCompany = trim($this->request->post('shipping_company', ''));
        
        if ($id <= 0 || empty($shippingCode)) return json_error('参数错误：订单ID不能为空，物流单号不能为空');

        $order = GoodsOrder::find($id);
        if (!$order) return json_error('订单不存在');
        if ($order->pay_status != 1) return json_error('订单未支付，无法发货');
        if ($order->shipping_status != 0) return json_error('订单已发货，不可重复操作');

        $order->shipping_status = 1;
        $order->shipping_code = $shippingCode;
        $order->shipping_company = $shippingCompany;
        $order->save();

        // 发送站内信通知
        \app\service\MessageService::send($order->user_id, 'order_ship', [
            'order_no' => $order->order_no,
            'shipping_code' => $shippingCode,
            'shipping_company' => $shippingCompany,
        ]);

        return json_success('发货成功');
    }

    /**
     * 退款处理
     */
    public function refund()
    {
        // 优化：参数转为整数/去空格
        $id = (int)$this->request->post('id');
        $action = trim($this->request->post('action', '')); // agree / reject
        $remark = trim($this->request->post('remark', ''));

        if ($id <= 0 || !in_array($action, ['agree', 'reject'])) {
            return json_error('参数错误：订单ID不能为空，操作类型只能是agree或reject');
        }

        $order = GoodsOrder::find($id);
        if (!$order) return json_error('订单不存在');
        if ($order->pay_status != 1) return json_error('订单未支付或已退款，无法处理退款');

        if ($action == 'agree') {
            // 同意退款：原路退回逻辑（需对接支付）
            // 简化：修改订单状态为已退款
            $order->pay_status = 2; // 已退款
            $order->save();

            // 返还库存
            foreach ($order->details as $detail) {
                $goods = \app\model\Goods::find($detail->goods_id);
                if ($goods) {
                    $goods->stock += $detail->num;
                    $goods->save();
                }
            }

            // 发送通知
            \app\service\MessageService::send($order->user_id, 'refund_success', [
                'order_no' => $order->order_no,
                'amount' => $order->actual_pay,
            ]);

            return json_success('退款成功');
        } else {
            // 拒绝退款
            $order->refund_status = 2; // 拒绝
            $order->refund_remark = $remark;
            $order->save();

            \app\service\MessageService::send($order->user_id, 'refund_reject', [
                'order_no' => $order->order_no,
                'remark' => $remark,
            ]);

            return json_success('已拒绝退款');
        }
    }

    /**
     * 导出订单
     */
    public function export()
    {
        try {
            $type = trim($this->request->get('type', 'mall')); // mall / member
            $start = $this->request->get('start', '');
            $end = $this->request->get('end', '');

            if ($type == 'mall') {
                $query = GoodsOrder::with('user')->where('pay_status', 1);
            } else {
                $query = UserMemberOrder::with('user')->where('pay_status', 1);
            }

            if ($start) $query->where('pay_time', '>=', strtotime($start));
            if ($end) $query->where('pay_time', '<=', strtotime($end) + 86399);

            $list = $query->select();

            // 生成Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // 设置表头
            $headers = ['订单号', '用户名', '金额', '支付方式', '支付时间', '状态'];
            if ($type == 'mall') {
                array_splice($headers, 3, 0, ['收货人', '联系电话']);
            }
            $sheet->fromArray($headers, null, 'A1');

            $row = 2;
            foreach ($list as $item) {
                $data = [
                    $item->order_no,
                    $item->user->username ?? '',
                    $item->actual_pay ?? $item->price,
                    $item->pay_type,
                    date('Y-m-d H:i:s', $item->pay_time),
                    $item->pay_status == 1 ? '已支付' : '未支付',
                ];
                if ($type == 'mall') {
                    array_splice($data, 3, 0, [$item->receive_name, $item->receive_mobile]);
                }
                $sheet->fromArray($data, null, 'A' . $row++);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = $type . '_orders_' . date('YmdHis') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return json_error('导出失败：' . $e->getMessage(), [], 500);
        }
    }
}