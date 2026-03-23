<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\MessageLog;

class Message extends BaseController
{
    public function lists()
    {
        $user = $this->request->user;
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $list = MessageLog::where('user_id', $user->id)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = MessageLog::where('user_id', $user->id)->count();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }

    public function detail()
    {
        $id = $this->request->get('id');
        $user = $this->request->user;
        $msg = MessageLog::where('id', $id)->where('user_id', $user->id)->find();
        if (!$msg) return json_error('消息不存在');
        // 标记已读
        if (!$msg->is_read) {
            $msg->is_read = 1;
            $msg->save();
        }
        return json_success('ok', $msg);
    }

    public function read()
    {
        $id = $this->request->post('id');
        $user = $this->request->user;
        $msg = MessageLog::where('id', $id)->where('user_id', $user->id)->find();
        if ($msg) {
            $msg->is_read = 1;
            $msg->save();
        }
        return json_success('ok');
    }
}