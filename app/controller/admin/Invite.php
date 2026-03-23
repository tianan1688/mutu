<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\InviteRecord;
use app\model\User;
use think\facade\Db;
use think\facade\Log;

class Invite extends BaseController
{
    /**
     * 通用成功返回（补充缺失的工具函数）
     */
    private function json_success(string $msg = '操作成功', array $data = [], int $code = 1, int $httpCode = 200)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ], $httpCode);
    }

    /**
     * 通用失败返回（补充缺失的工具函数）
     */
    private function json_error(string $msg = '操作失败', array $data = [], int $code = 0, int $httpCode = 200)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ], $httpCode);
    }

    /**
     * 简化版配置读取（补充缺失的get_config）
     */
    private function get_config(string $key, $default = null)
    {
        // 适配常见的配置表结构（config表：key/value字段）
        $value = Db::name('config')->where('key', $key)->value('value');
        return $value === null ? $default : $value;
    }

    /**
     * 简化版配置保存（补充缺失的set_config）
     */
    private function set_config(string $key, $value)
    {
        $exists = Db::name('config')->where('key', $key)->find();
        if ($exists) {
            Db::name('config')->where('key', $key)->update(['value' => $value]);
        } else {
            Db::name('config')->insert(['key' => $key, 'value' => $value]);
        }
    }

    /**
     * 邀请设置
     */
    public function setting()
    {
        if ($this->request->isPost()) {
            try {
                $data = $this->request->post();
                // 优化：配置项转为整数，避免存储字符串
                $this->set_config('invite_reward_enable', (int)($data['enable'] ?? 0));
                $this->set_config('invite_points_inviter', (int)($data['points_inviter'] ?? 0));
                $this->set_config('invite_points_invited', (int)($data['points_invited'] ?? 0));
                return $this->json_success('保存成功');
            } catch (\Exception $e) {
                Log::error('邀请设置保存失败：' . $e->getMessage());
                return $this->json_error('保存失败：' . $e->getMessage());
            }
        }

        $config = [
            'enable' => (int)$this->get_config('invite_reward_enable', 1),
            'points_inviter' => (int)$this->get_config('invite_points_inviter', 100),
            'points_invited' => (int)$this->get_config('invite_points_invited', 50),
        ];
        return $this->json_success('ok', $config);
    }

    /**
     * 邀请记录（修复第47行page参数类型错误+兼容多参数）
     */
    public function record()
    {
        try {
            // 核心修复：所有数字型参数强制转为整数
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $inviterId = (int)$this->request->get('inviter_id', 0);
            
            // 兼容：前端传user_id时自动适配
            if ($inviterId <= 0) {
                $inviterId = (int)$this->request->get('user_id', 0);
            }

            $query = InviteRecord::with([
                'inviter' => function($q) { // 只查询必要字段，提升性能
                    $q->field('id, username, mobile');
                },
                'invited' => function($q) {
                    $q->field('id, username, mobile');
                }
            ]);
            
            // 优化：加>0判断，避免inviterId=0时无效筛选
            if ($inviterId > 0) {
                $query->where('invite_user_id', $inviterId);
            }
            
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return $this->json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            // 生产环境隐藏具体错误，记录日志便于排查
            Log::error('邀请记录查询失败：' . $e->getMessage());
            return $this->json_error('邀请记录加载失败', ['total' => 0, 'list' => []]);
        }
    }

    /**
     * 补发奖励（增加事务+配置校验+数据安全）
     */
    public function reward()
    {
        // 开启数据库事务，避免部分操作成功部分失败
        Db::startTrans();
        try {
            // 优化：id转为整数，避免字符串类型问题
            $id = (int)$this->request->post('id');
            if ($id <= 0) {
                return $this->json_error('参数错误：记录ID不能为空');
            }
            
            $record = InviteRecord::find($id);
            if (!$record) {
                return $this->json_error('记录不存在');
            }

            if ($record->status == 1) {
                return $this->json_error('已奖励，不可重复补发');
            }

            // 校验邀请奖励配置是否开启
            $inviteEnable = (int)$this->get_config('invite_reward_enable', 0);
            if ($inviteEnable != 1) {
                return $this->json_error('邀请奖励功能未开启，无法补发');
            }

            // 获取奖励配置（避免硬编码）
            $pointsInviter = (int)$this->get_config('invite_points_inviter', 100);
            $pointsInvited = (int)$this->get_config('invite_points_invited', 50);

            // 补发积分（兼容用户不存在的情况）
            $inviter = User::find($record->invite_user_id);
            $invited = User::find($record->invited_user_id);
            
            if ($inviter) {
                $inviter->points += $pointsInviter; // 使用配置值而非记录值，更灵活
                $inviter->save();
                // 记录积分变动日志（可选）
                Db::name('point_log')->insert([
                    'user_id' => $inviter->id,
                    'type' => 1, // 1=邀请奖励
                    'points' => $pointsInviter,
                    'remark' => '邀请奖励补发',
                    'create_time' => time()
                ]);
            }
            
            if ($invited) {
                $invited->points += $pointsInvited;
                $invited->save();
                Db::name('point_log')->insert([
                    'user_id' => $invited->id,
                    'type' => 1,
                    'points' => $pointsInvited,
                    'remark' => '被邀请奖励补发',
                    'create_time' => time()
                ]);
            }

            // 更新记录状态
            $record->status = 1;
            $record->reward_time = time(); // 补充奖励时间
            $record->save();

            // 提交事务
            Db::commit();
            return $this->json_success('补发成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            Log::error('邀请奖励补发失败：' . $e->getMessage());
            return $this->json_error('补发失败：' . $e->getMessage());
        }
    }
}