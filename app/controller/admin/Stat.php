<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Stat extends BaseController
{
    /**
     * 导出月度对账数据
     */
    public function export()
    {
        $month = $this->request->get('month', date('Y-m'));
        $start = strtotime($month . '-01');
        $end = strtotime('+1 month', $start) - 1;

        // 获取数据
        $memberOrders = \app\model\UserMemberOrder::where('pay_status', 1)
            ->where('pay_time', 'between', [$start, $end])
            ->select();
        $mallOrders = \app\model\GoodsOrder::where('pay_status', 1)
            ->where('pay_time', 'between', [$start, $end])
            ->select();

        $spreadsheet = new Spreadsheet();

        // 会员订单工作表
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('会员订单');
        $sheet->fromArray(['订单号', '用户ID', '金额', '支付方式', '支付时间'], null, 'A1');
        $row = 2;
        foreach ($memberOrders as $order) {
            $sheet->fromArray([
                $order->order_no,
                $order->user_id,
                $order->price,
                $order->pay_type,
                date('Y-m-d H:i:s', $order->pay_time),
            ], null, 'A' . $row++);
        }

        // 商城订单工作表
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('商城订单');
        $sheet2->fromArray(['订单号', '用户ID', '实际支付', '积分抵扣', '支付方式', '支付时间'], null, 'A1');
        $row = 2;
        foreach ($mallOrders as $order) {
            $sheet2->fromArray([
                $order->order_no,
                $order->user_id,
                $order->actual_pay,
                $order->points_pay,
                $order->pay_type,
                date('Y-m-d H:i:s', $order->pay_time),
            ], null, 'A' . $row++);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'monthly_' . $month . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}