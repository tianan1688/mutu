<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use app\model\UserMemberOrder;
use app\model\Resource;
use app\model\GoodsOrder;
use app\model\Distributor;
use app\model\WithdrawApply;
use app\model\Complaint;
use think\facade\Db;

class Index extends BaseController
{
    /**
     * 控制台统计数据
     */
    public function stat()
    {
        $today = strtotime(date('Y-m-d'));
        $yesterday = $today - 86400;
        $monthStart = strtotime(date('Y-m-01'));

        // 营收统计
        $resourceIncome = UserMemberOrder::whereIn('pay_status', [1])->where('create_time', '>=', $monthStart)->sum('price');
        $mallIncome = GoodsOrder::where('pay_status', 1)->where('create_time', '>=', $monthStart)->sum('actual_pay');
        $memberIncome = UserMemberOrder::where('pay_status', 1)->where('create_time', '>=', $monthStart)->sum('price'); // 会员也算在资源里？按需求分开

        // 今日新增用户
        $todayNewUser = User::where('create_time', '>=', $today)->count();
        // 今日新增会员
        $todayNewMember = User::where('level_id', '>', 0)->where('create_time', '>=', $today)->count();
        // 今日资源下载量
        $todayDownload = Db::name('user_download_record')->where('create_time', '>=', $today)->count();

        // 广告预估（暂不从数据库统计，返回0）
        $adImpression = 0;

        // 分销佣金总待结算
        $pendingCommission = Distributor::sum('frozen_commission');

        // 待处理事项
        $todo = [
            'presell_order' => GoodsOrder::where('pay_status', 1)->where('is_presell', 1)->where('shipping_status', 0)->count(),
            'refund_apply' => GoodsOrder::where('pay_status', 1)->where('refund_status', 1)->count(), // 需要退款表
            'complaint_pending' => Complaint::where('status', 0)->count(),
            'withdraw_pending' => WithdrawApply::where('status', 0)->count(),
        ];

        // 超时提醒
        $reminders = [
            'withdraw_over_24h' => WithdrawApply::where('status', 0)->where('create_time', '<', time() - 86400)->count(),
            'refund_over_24h' => GoodsOrder::where('refund_status', 1)->where('update_time', '<', time() - 86400)->count(), // 简化
            'ship_over_48h' => GoodsOrder::where('pay_status', 1)->where('shipping_status', 0)->where('pay_time', '<', time() - 172800)->count(),
        ];

        return json_success('ok', [
            'income' => [
                'resource' => $resourceIncome,
                'mall' => $mallIncome,
                'member' => $memberIncome,
            ],
            'today_new_user' => $todayNewUser,
            'today_new_member' => $todayNewMember,
            'today_download' => $todayDownload,
            'ad_impression' => $adImpression,
            'pending_commission' => $pendingCommission,
            'todo' => $todo,
            'reminders' => $reminders,
        ]);
    }
}