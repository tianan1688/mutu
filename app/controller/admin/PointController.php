<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\PointRecord;

class PointController extends BaseController
{
    public function rule()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 优化：配置项转为整数，避免存储字符串
            set_config('point_expire_days', (int)($data['point_expire_days'] ?? 30));
            set_config('point_expire_notice_days', (int)($data['point_expire_notice_days'] ?? 7));
            set_config('point_sign', (int)($data['point_sign'] ?? 5));
            set_config('point_invite_inviter', (int)($data['point_invite_inviter'] ?? 100));
            set_config('point_invite_invited', (int)($data['point_invite_invited'] ?? 50));
            set_config('point_consume_rate', (int)($data['point_consume_rate'] ?? 1));
            return json_success('保存成功');
        }

        $config = [
            'point_expire_days' => get_config('point_expire_days', 30),
            'point_expire_notice_days' => get_config('point_expire_notice_days', 7),
            'point_sign' => get_config('point_sign', 5),
            'point_invite_inviter' => get_config('point_invite_inviter', 100),
            'point_invite_invited' => get_config('point_invite_invited', 50),
            'point_consume_rate' => get_config('point_consume_rate', 1),
        ];
        return json_success('ok', $config);
    }
    
    public function ruleSave()
    {
        // 直接调用原有 rule() 方法处理POST请求
        return $this->rule();
    }
    
    public function record()
    {
        try {
            // 核心修复：所有数字型参数强制转为整数
            $page = (int)$this->request->get('page', 1);      // 修复第46行的page参数类型
            $limit = (int)$this->request->get('limit', 20);   // limit参数同步转换
            $userId = (int)$this->request->get('user_id', 0); // 用户ID转为整数
            $type = $this->request->get('type', '');          // type保留字符串（兼容空值）
            if ($type !== '') $type = (int)$type;             // 非空时转为整数

            $query = PointRecord::with('user');
            if ($userId > 0) $query->where('user_id', $userId); // 加>0判断，避免0值筛选
            if ($type !== '') $query->where('type', $type);
            $total = $query->count();
            // 第46行：此时page/limit均为整数，不会触发类型错误
            $list = $query->order('id', 'desc')->page($page, $limit)->select();
            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            // 开发环境返回具体错误，便于排查
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    public function expireNotice()
    {
        // 优化：days参数转为整数
        $days = (int)$this->request->post('days', 7);
        set_config('point_expire_notice_days', $days);
        return json_success('设置成功');
    }
}