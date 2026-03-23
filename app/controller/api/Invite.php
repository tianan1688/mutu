<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\InviteRecord;

class Invite extends BaseController
{
    public function record()
    {
        $user = $this->request->user;
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $list = InviteRecord::with('invited')
            ->where('invite_user_id', $user->id)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = InviteRecord::where('invite_user_id', $user->id)->count();
        $stats = [
            'total' => $total,
            'completed' => InviteRecord::where('invite_user_id', $user->id)->where('status', 1)->count(),
        ];
        return json_success('ok', ['stats' => $stats, 'list' => $list]);
    }
}