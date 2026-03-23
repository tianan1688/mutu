<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use app\model\PointRecord;
use app\model\BalanceLog;
use app\model\Order;
use app\model\UserLevel;
use app\model\UserDownloadRecord;
use app\model\InviteRecord;
use think\facade\Log;

class UserController extends BaseController
{
    // 通用成功返回
    private function json_success(string $msg = '操作成功', array $data = [])
    {
        return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
    }

    // 通用失败返回
    private function json_error(string $msg = '操作失败', array $data = [])
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    // 获取当前登录管理员信息
    public function info()
    {
        try {
            $admin = $this->request->admin; // 从中间件获取
            if (!$admin) {
                return $this->json_error('未登录');
            }
            $data = $admin->toArray();
            $data['roles'] = ['admin'];
            return $this->json_success('ok', $data);
        } catch (\Exception $e) {
            Log::error('获取管理员信息失败：' . $e->getMessage());
            return $this->json_error('获取信息失败');
        }
    }

    // 用户列表
    public function lists()
    {
        try {
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $keyword = trim($this->request->get('keyword', ''));
            $status = $this->request->get('status', '');
            $level_id = $this->request->get('level_id', '');

            $page = max(1, $page);
            $limit = max(1, min(100, $limit));

            $query = User::with(['level' => function($query) {
                $query->field('id, name'); // 只取必要字段
            }]);
            
            if ($keyword) {
                $query->where('username|mobile', 'like', "%$keyword%");
            }
            if ($status !== '') {
                $query->where('status', $status);
            }
            if ($level_id !== '') {
                $query->where('level_id', $level_id);
            }
            
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            // 关键修复：手动遍历赋值 level_name
            foreach ($list as &$user) {
                if ($user->level_id > 0 && $user->level) {
                        $levelName = $user->level->name;
                        if ($user->expire_time == 0) {
                            $levelName .= '（永久）';
                        }
                        $user->level_name = $levelName;
                    } else {
                        $user->level_name = '普通用户';
                    }
                $createTime = (int)$user->create_time; // 强制转整数（字符串→数字）
                $createTime = $createTime ?: time();   // 空/0 → 当前时间戳
                $user->create_time = $createTime;      // 确保原字段是整数
                $user->create_time_format = date('Y-m-d H:i:s', $createTime); 
            }

            return $this->json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            Log::error('用户列表查询失败：' . $e->getMessage());
            return $this->json_error('获取用户列表失败', ['total' => 0, 'list' => []]);
        }
    }
    
    // 新增用户（核心修复：解决 create_time 赋值给 null 的问题）
    public function add()
    {
        try {
            $username = trim($this->request->post('username', ''));
            $mobile = trim($this->request->post('mobile', ''));
            $password = $this->request->post('password', '123456');
            $level_id = (int)$this->request->post('level_id', 0);
            $balance = (float)$this->request->post('balance', 0);
            $points = (int)$this->request->post('points', 0);
            $status = (int)$this->request->post('status', 1);
            
            // 1. 先校验参数（移到最前面）
            if (!$username || !$mobile) {
                return $this->json_error('用户名和手机号不能为空');
            }
            if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
                return $this->json_error('手机号格式错误');
            }
            $exist = User::where('mobile', $mobile)->find();
            if ($exist) {
                return $this->json_error('该手机号已注册');
            }

            // 2. 实例化用户对象（关键：赋值前先new，避免null）
            $user = new User();
            $user->username = $username;
            $user->mobile = $mobile;
            $user->password = $password;
            $user->level_id = $level_id;
            $user->balance = $balance;
            $user->points = $points;
            $user->status = $status;
            $user->create_time = time(); // 此时 $user 已实例化，可安全赋值
            $user->update_time = time();
            $user->invite_code = $this->generateInviteCode();
            
            // 设置会员到期时间
            if ($level_id > 0) {
                $level = UserLevel::find($level_id);
                if ($level) {
                    if ($level->duration_type == 'forever') {
                        $user->expire_time = 0;
                    } else {
                        $user->expire_time = time() + $level->days * 86400;
                    }
                } else {
                    $user->expire_time = 0;
                }
            } else {
                $user->expire_time = 0;
            }
            
            $user->save();

            return $this->json_success('用户创建成功', ['id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('新增用户失败：' . $e->getMessage());
            return $this->json_error('创建用户失败：' . $e->getMessage());
        }
    }

    // 生成邀请码
    private function generateInviteCode()
    {
        try {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            $exist = User::where('invite_code', $code)->find();
            if ($exist) {
                return $this->generateInviteCode();
            }
            return $code;
        } catch (\Exception $e) {
            Log::error('生成邀请码失败：' . $e->getMessage());
            return md5(uniqid(mt_rand(), true)) . substr(mt_rand(), 0, 8);
        }
    }

    // 用户详情
    public function detail()
{
    try {
        $id = (int)$this->request->get('id', 0);
        if ($id <= 0) return $this->json_error('参数错误：用户ID必须为正整数');
        
        // 强制加载会员等级（增加容错：找不到等级不影响主逻辑）
        $user = User::find($id);
        if (!$user) return $this->json_error('用户不存在');

        // 手动关联等级（避免 with 关联失败导致整体报错）
        $levelName = '普通用户';
        if ($user->level_id > 0) {
            $level = UserLevel::find($user->level_id);
            if ($level) {
                $levelName = $level->name;
                if ($user->expire_time == 0) {
                    $levelName .= '（永久）';
                }
            }
        }

        // 时间戳处理（终极容错）
        $createTime = is_numeric($user->create_time) ? (int)$user->create_time : 0;
        $createTime = $createTime > 0 ? $createTime : time();
        $createTimeFormat = date('Y-m-d H:i:s', $createTime);

        // 获取统计（完全隔离异常）
        $downloadCount = 0;
        $orderCount = 0;
        $inviteCount = 0;
        try {
            $downloadCount = class_exists(UserDownloadRecord::class) ? UserDownloadRecord::where('user_id', $id)->count() : 0;
        } catch (\Exception $e) {
            Log::warning('下载记录统计失败：' . $e->getMessage());
        }
        try {
            $orderCount = class_exists(Order::class) ? Order::where('user_id', $id)->count() : 0;
        } catch (\Exception $e) {
            Log::warning('订单记录统计失败：' . $e->getMessage());
        }
        try {
            $inviteCount = User::where('pid', $id)->count();
        } catch (\Exception $e) {
            Log::warning('邀请记录统计失败：' . $e->getMessage());
        }

        // 返回数据
        return $this->json_success('ok', [
            'id' => $user->id,
            'username' => $user->username ?: '',
            'mobile' => $user->mobile ?: '',
            'avatar' => $user->avatar ?: '',
            'level_id' => $user->level_id ?: 0,
            'level_name' => $levelName,
            'balance' => $user->balance ?: 0.00,
            'points' => $user->points ?: 0,
            'status' => $user->status ?: 0,
            'create_time' => $createTime,
            'create_time_format' => $createTimeFormat,
            'invite_code' => $user->invite_code ?: '',
            'pid' => $user->pid ?: 0,
            'expire_time' => $user->expire_time ?: 0,
            'stats' => [
                'download' => $downloadCount,
                'order' => $orderCount,
                'invite' => $inviteCount,
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('用户详情查询失败：' . $e->getMessage() . ' | 错误行：' . $e->getLine());
        // 生产环境友好提示，保留日志便于排查
        return $this->json_error('获取用户详情失败');
    }
}

    // 保存用户信息
    public function save()
    {
        try {
            $id = (int)$this->request->post('id', 0);
            $data = $this->request->post();

            if ($id <= 0) return $this->json_error('参数错误');
            $user = User::find($id);
            if (!$user) return $this->json_error('用户不存在');

            $allowFields = ['username', 'mobile', 'avatar', 'status', 'level_id', 'balance', 'points'];
            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    if (in_array($field, ['balance'])) {
                        $user->$field = (float)$data[$field];
                    } elseif (in_array($field, ['level_id', 'points', 'status'])) {
                        $user->$field = (int)$data[$field];
                    } else {
                        $user->$field = $data[$field];
                    }
                }
            }
            
            // 更新会员到期时间
            if (isset($data['level_id']) && $data['level_id'] > 0) {
                $level = UserLevel::find($data['level_id']);
                if ($level) {
                    if ($level->duration_type == 'forever') {
                        $user->expire_time = 0;
                    } else {
                        $user->expire_time = time() + $level->days * 86400;
                    }
                }
            }
            
            $user->update_time = time();
            $user->save();

            return $this->json_success('保存成功');
        } catch (\Exception $e) {
            Log::error('保存用户信息失败：' . $e->getMessage());
            return $this->json_error('保存用户信息失败：' . $e->getMessage());
        }
    }

    // 禁用/启用
    public function disable()
    {
        try {
            $id = (int)$this->request->post('id', 0);
            $status = (int)$this->request->post('status', 0);
            
            if ($id <= 0) return $this->json_error('参数错误');
            $user = User::find($id);
            if (!$user) return $this->json_error('用户不存在');
            
            $user->status = $status ? 1 : 0;
            $user->update_time = time();
            $user->save();
            
            return $this->json_success($status ? '已启用' : '已禁用');
        } catch (\Exception $e) {
            Log::error('禁用/启用用户失败：' . $e->getMessage());
            return $this->json_error('操作失败：' . $e->getMessage());
        }
    }

    // 增减余额
    public function balance()
    {
        try {
            $id = (int)$this->request->post('id', 0);
            $amount = (float)$this->request->post('amount', 0);
            $remark = trim($this->request->post('remark', '后台操作'));

            if ($id <= 0) return $this->json_error('参数错误');
            $user = User::find($id);
            if (!$user) return $this->json_error('用户不存在');

            $before = $user->balance;
            $user->balance = $user->balance + $amount;
            if ($user->balance < 0) {
                return $this->json_error('余额不足');
            }
            $user->update_time = time();
            $user->save();

            // 记录余额日志（如果模型不存在，捕获异常）
            try {
                BalanceLog::create([
                    'user_id' => $id,
                    'before' => $before,
                    'after' => $user->balance,
                    'amount' => $amount,
                    'remark' => $remark,
                    'create_time' => time(),
                ]);
            } catch (\Exception $e) {
                Log::warning('余额日志记录失败：' . $e->getMessage());
            }

            return $this->json_success('操作成功', ['balance' => $user->balance]);
        } catch (\Exception $e) {
            Log::error('增减余额失败：' . $e->getMessage());
            return $this->json_error('余额操作失败：' . $e->getMessage());
        }
    }

    // 增减积分
    public function points()
    {
        try {
            $id = (int)$this->request->post('id', 0);
            $points = (int)$this->request->post('points', 0);
            $remark = trim($this->request->post('remark', '后台操作'));

            if ($id <= 0) return $this->json_error('参数错误');
            $user = User::find($id);
            if (!$user) return $this->json_error('用户不存在');

            $before = $user->points;
            $user->points = $user->points + $points;
            if ($user->points < 0) {
                return $this->json_error('积分不足');
            }
            $user->update_time = time();
            $user->save();

            PointRecord::create([
                'user_id' => $id,
                'type' => 'system',
                'points' => $points,
                'balance' => $user->points,
                'remark' => $remark,
                'create_time' => time(),
            ]);

            return $this->json_success('操作成功', ['points' => $user->points]);
        } catch (\Exception $e) {
            Log::error('增减积分失败：' . $e->getMessage());
            return $this->json_error('积分操作失败：' . $e->getMessage());
        }
    }

    // 重置密码
    public function resetPassword()
    {
        try {
            $id = (int)$this->request->post('id', 0);
            $newPassword = $this->request->post('password', '123456');
            
            if ($id <= 0) return $this->json_error('参数错误');
            $user = User::find($id);
            if (!$user) return $this->json_error('用户不存在');
            
            $user->password = $newPassword;
            $user->update_time = time();
            $user->save();
            
            return $this->json_success('密码已重置为：' . $newPassword);
        } catch (\Exception $e) {
            Log::error('重置密码失败：' . $e->getMessage());
            return $this->json_error('重置密码失败：' . $e->getMessage());
        }
    }
    
    // 获取用户下载记录
    public function downloadRecords()
    {
        try {
            $userId = (int)$this->request->get('user_id', 0);
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 10);
            
            if ($userId <= 0) {
                return $this->json_error('用户ID错误');
            }
            
            $query = UserDownloadRecord::with(['resource' => function($q) {
                $q->field('id, title'); // 只关联资源名称
            }])->where('user_id', $userId);
            
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();
            
            return $this->json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            Log::error('下载记录查询失败：' . $e->getMessage());
            return $this->json_error('下载记录加载失败', ['list' => [], 'total' => 0]);
        }
    }

    // 获取用户积分记录
    public function pointRecords()
    {
        try {
            $userId = (int)$this->request->get('user_id', 0);
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 10);
            
            if ($userId <= 0) {
                return $this->json_error('用户ID错误');
            }
            
            $query = PointRecord::where('user_id', $userId);
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();
            
            return $this->json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            Log::error('积分记录查询失败：' . $e->getMessage());
            return $this->json_error('积分记录加载失败', ['list' => [], 'total' => 0]);
        }
    }

    // 获取用户邀请记录
    public function inviteRecords()
    {
        try {
            $userId = (int)$this->request->get('inviter_id', 0);
            // 兼容前端传 user_id
            if ($userId <= 0) {
                $userId = (int)$this->request->get('user_id', 0);
            }
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 10);
            
            if ($userId <= 0) {
                return $this->json_error('邀请人ID错误');
            }
            
            $query = InviteRecord::with(['invited' => function($q) {
                $q->field('id, username, mobile');
            }])->where('invite_user_id', $userId); // 适配模型字段 invite_user_id
            
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();
            
            return $this->json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            Log::error('邀请记录查询失败：' . $e->getMessage());
            return $this->json_error('邀请记录加载失败', ['list' => [], 'total' => 0]);
        }
    }
}

// 邀请记录接口（完整版）
/**public function inviteRecords()
{
    $userId = (int)$this->request->get('inviter_id', 0);
    if ($userId <= 0) {
        $userId = (int)$this->request->get('user_id', 0);
    }

    if ($userId <= 0) {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => ['total' => 0, 'list' => []]
        ]);
    }

    try {
        $query = \app\model\InviteRecord::with(['invited' => function($q) {
            $q->field('id, username, mobile');
        }])->where('inviter_id', $userId);
        
        $total = $query->count();
        $list = $query->order('id', 'desc')
                      ->page($this->request->get('page', 1), $this->request->get('limit', 10))
                      ->select();

        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => ['total' => $total, 'list' => $list]
        ]);
    } catch (\Exception $e) {
        Log::error('邀请记录查询失败：' . $e->getMessage());
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => ['total' => 0, 'list' => []]
        ]);
    }
} **/
