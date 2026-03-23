<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Complaint;

class Complaint extends BaseController
{
    /**
     * 投诉列表（修复page/limit/status参数类型）
     */
    public function lists()
    {
        try {
            // 核心修复：数字型参数强制转为整数
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $status = $this->request->get('status', ''); // 保留空值，非空时转整数

            // 非空时转换status为整数，避免字符串匹配
            if ($status !== '') $status = (int)$status;

            $query = Complaint::with('user');
            if ($status !== '') $query->where('status', $status);
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 处理投诉（修复id参数类型+校验）
     */
    public function handle()
    {
        try {
            // 核心修复：参数转为整数/去空格+校验
            $id = (int)$this->request->post('id');
            $reply = trim($this->request->post('reply', ''));
            $action = trim($this->request->post('action', 'resolve')); // resolve 或 reject

            if ($id <= 0) return json_error('参数错误：投诉ID不能为空');

            $complaint = Complaint::find($id);
            if (!$complaint) return json_error('投诉不存在');

            $complaint->status = 1; // 已处理
            $complaint->reply = $reply;
            $complaint->handle_time = time();
            $complaint->save();

            // 发送站内信通知用户（可选）
            // \app\service\MessageService::send($complaint->user_id, 'complaint_handle', ['reply' => $reply]);

            return json_success('处理完成');
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 投诉统计（添加异常捕获）
     */
    public function stat()
    {
        try {
            $total = Complaint::count();
            $pending = Complaint::where('status', 0)->count();
            $today = Complaint::where('create_time', '>=', strtotime(date('Y-m-d')))->count();
            return json_success('ok', [
                'total' => $total,
                'pending' => $pending,
                'today' => $today,
            ]);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }
}