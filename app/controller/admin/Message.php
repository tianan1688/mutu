<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\MessageTemplate;
use app\model\MessageConfig;
use app\model\MessageLog;
use app\service\MessageService;

class Message extends BaseController
{
    /**
     * 模板列表
     */
    public function template()
    {
        try {
            $list = MessageTemplate::select();
            return json_success('ok', $list);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    public function saveTemplate()
    {
        try {
            $data = $this->request->post();
            // 核心修复：id转为整数
            $id = (int)($data['id'] ?? 0);
            if ($id) {
                $template = MessageTemplate::find($id);
                if (!$template) return json_error('模板不存在');
            } else {
                $template = new MessageTemplate();
            }
            $template->save($data);
            return json_success('保存成功');
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    public function deleteTemplate()
    {
        try {
            // 核心修复：id转为整数+参数校验
            $id = (int)$this->request->post('id');
            if ($id <= 0) return json_error('参数错误：模板ID不能为空');
            
            MessageTemplate::destroy($id);
            return json_success('删除成功');
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 场景开关配置
     */
    public function config()
    {
        try {
            if ($this->request->isPost()) {
                $data = $this->request->post();
                foreach ($data as $scene => $status) {
                    // 核心修复：status转为整数
                    $status = (int)$status;
                    $config = MessageConfig::where('scene_key', $scene)->find();
                    if (!$config) {
                        MessageConfig::create(['scene_key' => $scene, 'status' => $status]);
                    } else {
                        $config->status = $status;
                        $config->save();
                    }
                }
                return json_success('保存成功');
            }

            $list = MessageConfig::select();
            $config = [];
            foreach ($list as $item) {
                $config[$item->scene_key] = $item->status;
            }
            // 默认值
            $defaultScenes = [
                'login_sms' => 1,
                'member_renew' => 1,
                'order_pay' => 1,
                'order_ship' => 1,
                'commission_arrival' => 1,
                'withdraw_audit' => 1,
                'point_expire' => 1,
                'marketing' => 1,
            ];
            $config = array_merge($defaultScenes, $config);
            return json_success('ok', $config);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 发送日志（修复第99行page参数类型错误）
     */
    public function log()
    {
        try {
            // 核心修复：所有数字型参数强制转为整数
            $page = (int)$this->request->get('page', 1);        // 原字符串"1"→整数1（解决第99行报错）
            $limit = (int)$this->request->get('limit', 20);     // limit同步转换
            $userId = (int)$this->request->get('user_id', 0);   // 用户ID转为整数（空字符串→0）
            $start = $this->request->get('start', '');          // 时间参数保留字符串
            $end = $this->request->get('end', '');

            $query = MessageLog::with('user');
            // 优化：加>0判断，避免userId=0时无效筛选
            if ($userId > 0) $query->where('user_id', $userId);
            if ($start) $query->where('send_time', '>=', strtotime($start));
            if ($end) $query->where('send_time', '<=', strtotime($end) + 86399);
            
            $total = $query->count();
            // 第99行：此时page/limit均为整数，无类型错误
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            // 返回前端预期的格式（code/datas/message）
            return json([
                'code' => 1,
                'datas' => [],
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 手动发送站内信
     */
    public function send()
    {
        try {
            // 核心修复：参数转为整数/去空格+校验
            $userId = (int)$this->request->post('user_id');
            $title = trim($this->request->post('title', ''));
            $content = trim($this->request->post('content', ''));

            if ($userId <= 0 || empty($title) || empty($content)) {
                return json_error('参数不完整：用户ID、标题、内容不能为空');
            }

            $res = MessageService::send($userId, 'custom', ['title' => $title, 'content' => $content]);
            if ($res) {
                return json_success('发送成功');
            } else {
                return json_error('发送失败');
            }
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }
}