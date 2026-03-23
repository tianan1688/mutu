<?php
declare (strict_types=1);

namespace app\service;

use app\model\MessageTemplate;
use app\model\MessageLog;
use app\model\MessageConfig;

class MessageService
{
    /**
     * 发送站内信
     * @param int $userId 接收用户ID
     * @param string $scene 场景标识（对应模板场景）
     * @param array $params 模板变量
     * @return bool
     */
    public static function send($userId, $scene, $params = [])
    {
        // 检查总开关
        if (!get_config('message_enable', 1)) {
            return false;
        }

        // 检查场景开关
        $config = MessageConfig::where('scene_key', $scene)->find();
        if ($config && $config->status == 0) {
            return false;
        }

        // 获取模板
        $template = MessageTemplate::where('name', $scene)->find();
        if (!$template) {
            // 自定义消息直接发送
            return self::insertLog($userId, $params['title'] ?? '系统消息', $params['content'] ?? '');
        }

        // 解析模板
        $title = $template->title;
        $content = $template->content;
        foreach ($params as $key => $value) {
            $title = str_replace('{' . $key . '}', $value, $title);
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return self::insertLog($userId, $title, $content);
    }

    private static function insertLog($userId, $title, $content)
    {
        MessageLog::create([
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'status' => 1,
            'send_time' => time(),
        ]);
        return true;
    }
}