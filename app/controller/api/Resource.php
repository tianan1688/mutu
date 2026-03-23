<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
// 核心修复1：给模型起别名，避免和控制器类名冲突
use app\model\Resource as ResourceModel;
use app\model\UserDownloadRecord;
use app\model\UserLevel;
use app\model\ResourceOrder; 
use app\model\ResourceCategory;
use think\facade\Cache;
use think\facade\Request;

class Resource extends BaseController
{
    // ==================== 资源分类接口 ====================
    public function category()
    {
        try {
            // 查询启用的分类（兼容无status字段的情况）
            $list = ResourceCategory::order('sort', 'asc')
                ->select()
                ->toArray();
            
            // 构建分类树形结构
            $tree = $this->buildCategoryTree($list);
            
            // 核心修复2：替换 json_success 为原生 json 输出（避免函数未定义）
            return json([
                'code' => 1,
                'msg' => 'ok',
                'data' => $tree
            ]);
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json([
                    'code' => 0,
                    'msg' => '服务器错误：' . $e->getMessage(),
                    'data' => []
                ]);
            }
            return json([
                'code' => 0,
                'msg' => '获取分类失败，请稍后重试',
                'data' => []
            ]);
        }
    }

    // 私有方法：构建分类树形结构
    private function buildCategoryTree($categories, $parentId = 0, $depth = 0)
    {
        // 防死循环：限制递归深度
        if ($depth > 10) return [];
        
        $tree = [];
        foreach ($categories as $cat) {
            if ($cat['pid'] == $parentId) {
                $children = $this->buildCategoryTree($categories, $cat['id'], $depth + 1);
                if (!empty($children)) {
                    $cat['children'] = $children;
                }
                $tree[] = $cat;
            }
        }
        return $tree;
    }

    // ==================== 资源列表 ====================
    public function lists()
    {
        try {
            $catId = (int)$this->request->get('cat_id', 0);
            $page = max(1, (int)$this->request->get('page', 1));
            $limit = max(1, min(50, (int)$this->request->get('limit', 20)));
            $order = trim($this->request->get('order', 'id_desc'));
            $keyword = trim($this->request->get('keyword', '')); // 新增：获取关键词$keyword = trim($this->request->get('keyword', '')); // 新增：获取关键词
            $query = ResourceModel::where('status', 1);
            // 核心修复：使用别名 ResourceModel 调用模型
            if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }
            if ($catId > 0) {
                $query->where('cat_id', $catId);
            }

            // 排序
            switch ($order) {
                case 'price_asc':
                    $query->order('price', 'asc');
                    break;
                case 'download_desc':
                    $query->order('download_count', 'desc');
                    break;
                case 'is_top_desc':
                    $query->order('is_top', 'desc')->order('id', 'desc');
                    break;
                default:
                    $query->order('id', 'desc');
            }

            $list = $query->paginate([
                'page' => $page,
                'list_rows' => $limit
            ]);

            return json([
                'code' => 1,
                'msg' => 'ok',
                'data' => [
                    'total' => $list->total(),
                    'list' => $list->items(),
                ]
            ]);
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json([
                    'code' => 0,
                    'msg' => '服务器错误：' . $e->getMessage(),
                    'data' => []
                ]);
            }
            return json([
                'code' => 0,
                'msg' => '获取资源列表失败',
                'data' => []
            ]);
        }
    }

    // ==================== 资源详情 ====================
    public function detail()
    {
        try {
            $id = (int)$this->request->get('id');
            if ($id <= 0) return json([
                'code' => 400,
                'msg' => '参数错误：资源ID不能为空',
                'data' => []
            ]);
            
            $resource = ResourceModel::with('category')->find($id);
            if (!$resource || $resource->status != 1) {
                return json([
                    'code' => 404,
                    'msg' => '资源不存在或已下架',
                    'data' => []
                ]);
            }
            
            $resource->view_count = ($resource->view_count ?? 0) + 1;
            $resource->save();

            $user = $this->request->user ?? null;
            $finalPrice = $this->getFinalPrice($resource, $user);
            $canDownload = false;
            
            if ($user) {
                if ($resource->type == 2) { 
                    $canDownload = $this->isMemberValid($user);
                } else {
                    // 付费资源：检查是否已购买
                    $bought = ResourceOrder::where('user_id', $user->id)
                        ->where('resource_id', $id)
                        ->where('pay_status', 1)
                        ->count();
                    $canDownload = $bought > 0;
                }
            }

            return json([
                'code' => 200,
                'msg' => 'ok',
                'data' => [
                    'resource' => $resource,
                    'final_price' => $finalPrice,
                    'can_download' => $canDownload,
                ]
            ]);
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json([
                    'code' => 500,
                    'msg' => '服务器错误：' . $e->getMessage(),
                    'data' => []
                ]);
            }
            return json([
                'code' => 500,
                'msg' => '获取资源详情失败',
                'data' => []
            ]);
        }
    }

    // ==================== 获取下载链接 ====================
    public function download()
    {
        try {
            $id = (int)$this->request->post('id');
            if ($id <= 0) return json([
                'code' => 400,
                'msg' => '参数错误：资源ID不能为空',
                'data' => []
            ]);
            
            // 检查用户是否登录
            $user = $this->request->user;
            if (!$user) return json([
                'code' => 401,
                'msg' => '请先登录',
                'data' => []
            ]);

            $resource = ResourceModel::find($id);
            if (!$resource || $resource->status != 1) {
                return json([
                    'code' => 404,
                    'msg' => '资源不存在或已下架',
                    'data' => []
                ]);
            }

            // 检查IP黑名单（兼容无该模型的情况）
            $ip = $this->getClientIp();
            try {
                $blacklisted = \app\model\IpBlacklist::where('ip', $ip)->exists();
            } catch (\Exception $e) {
                $blacklisted = false;
            }
            if ($blacklisted) return json([
                'code' => 403,
                'msg' => '您的IP已被限制下载',
                'data' => []
            ]);

            // 检查权限
            if ($resource->type == 2) {
                // 会员专属
                if (!$this->isMemberValid($user)) {
                    return json([
                        'code' => 403,
                        'msg' => '请开通会员后再下载',
                        'data' => []
                    ]);
                }
                // 每日下载次数限制
                $level = UserLevel::find($user->level_id);
                $dailyLimit = $level ? ($level->download_times_per_day ?? $this->getConfig('download_limit_per_day', 10)) : 0;
                if ($dailyLimit > 0) {
                    $todayStart = strtotime(date('Y-m-d'));
                    $count = UserDownloadRecord::where('user_id', $user->id)
                        ->where('create_time', '>=', $todayStart)
                        ->count();
                    if ($count >= $dailyLimit) {
                        return json([
                            'code' => 403,
                            'msg' => '今日下载次数已用完',
                            'data' => []
                        ]);
                    }
                }
            } else {
                // 付费资源：检查是否已购买
                $bought = ResourceOrder::where('user_id', $user->id)
                    ->where('resource_id', $id)
                    ->where('pay_status', 1)
                    ->count();
                if (!$bought) {
                    return json([
                        'code' => 403,
                        'msg' => '请先购买资源',
                        'data' => []
                    ]);
                }
            }

            // 生成加密下载链接
            $expire = (int)$this->getConfig('download_link_expire', 600);
            $filePath = $resource->file_path;
            
            // 生成加密参数
            $token = $this->generateRandomStr(32);
            $expireTime = time() + $expire;
            $data = json_encode([
                'file' => $filePath,
                'user' => $user->id,
                'expire' => $expireTime,
                'token' => $token
            ]);
            $encrypted = $this->encryptData($data);
            
            // 缓存token
            Cache::set('download_token_' . $token, $user->id, $expire);
            
            // 构建下载链接
            $url = Request::domain() . '/api/resource/do_download?token=' . $token . '&data=' . urlencode($encrypted);

            // 记录下载
            UserDownloadRecord::create([
                'user_id' => $user->id,
                'resource_id' => $id,
                'ip' => $ip,
                'create_time' => time(),
            ]);

            // 更新下载次数
            $resource->download_count = ($resource->download_count ?? 0) + 1;
            $resource->save();

            return json([
                'code' => 200,
                'msg' => 'ok',
                'data' => ['url' => $url]
            ]);
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json([
                    'code' => 500,
                    'msg' => '服务器错误：' . $e->getMessage(),
                    'data' => []
                ]);
            }
            return json([
                'code' => 500,
                'msg' => '获取下载链接失败',
                'data' => []
            ]);
        }
    }

    // ==================== 实际下载处理 ====================
    public function doDownload()
    {
        try {
            $token = $this->request->get('token');
            $encrypted = $this->request->get('data');
            
            if (!$token || !$encrypted) {
                return json([
                    'code' => 400,
                    'msg' => '下载链接无效',
                    'data' => []
                ]);
            }

            // 验证token
            $userId = Cache::get('download_token_' . $token);
            if (!$userId) {
                return json([
                    'code' => 400,
                    'msg' => '下载链接已过期或已使用',
                    'data' => []
                ]);
            }

            // 解密数据
            $data = $this->decryptData($encrypted);
            $data = json_decode($data, true);
            if (!$data || $data['token'] != $token || $data['expire'] < time()) {
                return json([
                    'code' => 400,
                    'msg' => '下载链接已过期',
                    'data' => []
                ]);
            }

            // 验证用户
            if ($data['user'] != $userId) {
                return json([
                    'code' => 400,
                    'msg' => '下载链接无效',
                    'data' => []
                ]);
            }

            // 验证文件
            $filePath = $this->safeFilePath(public_path(), $data['file']);
            if (!$filePath || !file_exists($filePath)) {
                return json([
                    'code' => 404,
                    'msg' => '文件不存在',
                    'data' => []
                ]);
            }

            // 删除token
            Cache::delete('download_token_' . $token);

            // 输出文件下载
            return response()->download($filePath, basename($filePath));
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json([
                    'code' => 500,
                    'msg' => '服务器错误：' . $e->getMessage(),
                    'data' => []
                ]);
            }
            return json([
                'code' => 500,
                'msg' => '下载失败',
                'data' => []
            ]);
        }
    }

    // ==================== 工具方法（解决函数缺失） ====================
    /**
     * 获取客户端IP
     */
    private function getClientIp()
    {
        return Request::ip();
    }

    /**
     * 生成随机字符串
     */
    private function generateRandomStr($length = 32)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * 获取配置（兼容无该函数的情况）
     */
    private function getConfig($key, $default = '')
    {
        try {
            return \think\facade\Config::get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * 加密数据（简易实现，可根据实际加密规则修改）
     */
    private function encryptData($data)
    {
        $key = 'your_encrypt_key'; // 替换为实际的加密密钥
        return openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    /**
     * 解密数据
     */
    private function decryptData($data)
    {
        $key = 'your_encrypt_key'; // 替换为实际的加密密钥
        return openssl_decrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    /**
     * 安全文件路径处理
     */
    private function safeFilePath(string $basePath, string $filePath): string|false
    {
        $realBase = realpath($basePath);
        $realPath = realpath($realBase . '/' . ltrim($filePath, '/'));
        
        if (!$realPath || !str_starts_with($realPath, $realBase)) {
            return false;
        }
        return $realPath;
    }

    /**
     * 检查会员是否有效（简易实现，可根据实际业务修改）
     */
    private function isMemberValid($user)
    {
        if (!$user->level_id || !$user->member_expire_time) {
            return false;
        }
        return $user->member_expire_time > time();
    }

    /**
     * 获取资源最终价格（简易实现）
     */
    private function getFinalPrice($resource, $user)
    {
        if (!$user) {
            return $resource->price;
        }
        // 可根据会员等级添加折扣逻辑
        return $resource->price;
    }
}