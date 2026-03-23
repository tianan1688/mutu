<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\UserLevel;
use app\model\UserMemberOrder;
use app\model\User;

class Member extends BaseController
{
    // 会员等级列表
    public function level()
    {
        $list = UserLevel::order('sort', 'asc')->select();
        return json_success('ok', $list);
    }

    // 保存等级
    public function saveLevel()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        if ($id) {
            $level = UserLevel::find($id);
            if (!$level) return json_error('等级不存在');
        } else {
            $level = new UserLevel();
        }
        $level->save($data);
        return json_success('保存成功');
    }

    // 删除等级
    public function deleteLevel()
    {
        $id = $this->request->post('id');
        if (!$id) return json_error('参数错误');
        // 检查是否有用户在使用
        if (User::where('level_id', $id)->count() > 0) {
            return json_error('该等级下有用户，无法删除');
        }
        UserLevel::destroy($id);
        return json_success('删除成功');
    }

    // 会员订单列表
    public function order()
    {
        // 关键修复：将参数强制转换为整数（解决类型错误核心）
        $page = (int)$this->request->get('page', 1);
        $limit = (int)$this->request->get('limit', 20);
        $status = $this->request->get('pay_status', ''); // 0待支付 1已支付
        $keyword = $this->request->get('keyword', ''); // 订单号或用户名

        $query = UserMemberOrder::with(['user', 'level']);
        if ($status !== '') {
            // 额外优化：status也转换为整数，避免潜在类型问题
            $query->where('pay_status', (int)$status);
        }
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', "%$keyword%")
                  ->whereOr('user_id', 'in', User::where('username', 'like', "%$keyword%")->column('id'));
            });
        }
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select();

        return json_success('ok', ['total' => $total, 'list' => $list]);
    }

    // 手动调整会员
    public function adjust()
    {
        $userId = $this->request->post('user_id');
        $levelId = $this->request->post('level_id');
        $expireTime = $this->request->post('expire_time'); // 时间戳

        if (!$userId) return json_error('用户ID不能为空');
        // 优化：转换为整数，避免类型问题
        $userId = (int)$userId;
        $levelId = (int)$levelId;
        $expireTime = $expireTime ? (int)$expireTime : 0;

        $user = User::find($userId);
        if (!$user) return json_error('用户不存在');

        $user->level_id = $levelId ?: 0;
        if ($expireTime) {
            $user->expire_time = $expireTime;
        } else {
            // 如果等级是永久的，expire_time设为0
            if ($levelId > 0) {
                $level = UserLevel::find($levelId);
                if ($level && $level->duration_type == 'forever') {
                    $user->expire_time = 0;
                }
            }
        }
        $user->save();

        // 记录操作日志
        \app\model\OperationLog::create([
            'admin_id' => $this->request->adminId,
            'action' => "手动调整用户{$userId}会员等级为{$levelId}",
            'ip' => get_client_ip(),
            'create_time' => time(),
        ]);

        return json_success('调整成功');
    }
}