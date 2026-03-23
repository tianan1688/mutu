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

public function stat()
{
    $today = strtotime(date('Y-m-d'));
    $yesterday = $today - 86400;
    $monthStart = strtotime(date('Y-m-01'));

    // 营收统计
    $resourceIncome = UserMemberOrder::where('pay_status', 1)->where('create_time', '>=', $monthStart)->sum('price');
    $mallIncome = GoodsOrder::where('pay_status', 1)->where('create_time', '>=', $monthStart)->sum('actual_pay');
    $memberIncome = $resourceIncome; // 会员订单已在资源中统计，这里分开展示可以单独计算会员订单收入

    // 今日新增用户
    $todayNewUser = User::where('create_time', '>=', $today)->count();
    // 今日开通会员数
    $todayNewMember = UserMemberOrder::where('pay_status', 1)->where('pay_time', '>=', $today)->count();
    // 今日资源下载量
    $todayDownload = \think\facade\Db::name('user_download_record')->where('create_time', '>=', $today)->count();

    // 广告预估（暂无数据）
    $adImpression = 0;
    // 分销佣金待结算
    $pendingCommission = \app\model\Distributor::sum('frozen_commission');

    // 待处理事项（简化版本，避免使用不存在的字段）
    $todo = [
        'presell_order' => 0, // 暂不统计预售订单，可后续完善
        'refund_apply' => 0,   // 暂不统计退款申请
        'complaint_pending' => \app\model\Complaint::where('status', 0)->count(),
        'withdraw_pending' => \app\model\WithdrawApply::where('status', 0)->count(),
    ];

    // 超时提醒
    $reminders = [
        'withdraw_over_24h' => \app\model\WithdrawApply::where('status', 0)->where('create_time', '<', time() - 86400)->count(),
        'refund_over_24h' => 0, // 暂不统计
        'ship_over_48h' => GoodsOrder::where('pay_status', 1)
            ->where('shipping_status', 0)
            ->where('pay_time', '<', time() - 172800)
            ->count(),
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