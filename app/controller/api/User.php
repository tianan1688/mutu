<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use app\model\BalanceLog;
use app\model\PointRecord;

class User extends BaseController
{
    /**
     * 获取用户信息
     */
    public function info()
    {
        $user = $this->request->user;
        // 刷新会员状态
        if ($user->expire_time > 0 && $user->expire_time < time()) {
            $user->level_id = 0;
            $user->expire_time = 0;
            $user->save();
        }
        return json_success('ok', [
            'id' => $user->id,
            'username' => $user->username,
            'mobile' => $user->mobile,
            'avatar' => $user->avatar,
            'level_id' => $user->level_id,
            'level_name' => $user->level_name,
            'expire_time' => $user->expire_time,
            'balance' => $user->balance,
            'points' => $user->points,
            'invite_code' => $user->invite_code,
        ]);
    }

    /**
     * 修改资料
     */
    public function update()
    {
        $user = $this->request->user;
        $data = $this->request->post();
        $allowFields = ['username', 'avatar'];
        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $user->$field = $data[$field];
            }
        }
        $user->save();
        return json_success('修改成功');
    }

    /**
     * 余额流水
     */
    public function balanceLog()
    {
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $list = \app\model\BalanceLog::where('user_id', $this->request->user->id)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = \app\model\BalanceLog::where('user_id', $this->request->user->id)->count();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }

    /**
     * 积分流水
     */
    public function pointLog()
    {
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $list = \app\model\PointRecord::where('user_id', $this->request->user->id)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = \app\model\PointRecord::where('user_id', $this->request->user->id)->count();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }
}