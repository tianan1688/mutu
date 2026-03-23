<?php
declare (strict_types=1);

namespace app\service;

use think\facade\Cache;
use think\facade\Request;
use app\model\IpBlacklist;
use app\model\UserDownloadRecord;
use think\exception\RuntimeException;

class DownloadService
{
    /**
     * 生成加密下载链接
     * @param string $filePath 文件相对路径（如 /uploads/resource/xxx.zip）
     * @param int $userId 用户ID（绑定下载用户，防止链接盗用）
     * @param int $resourceId 资源ID（用于统计）
     * @param int $expire 有效期（秒）
     * @return string 完整URL
     * @throws RuntimeException
     */
    public static function generate(string $filePath, int $userId, int $resourceId, int $expire = 600): string
    {
        // 1. 参数校验
        if (empty($filePath) || $userId <= 0 || $resourceId <= 0) {
            throw new RuntimeException('生成下载链接参数错误');
        }

        // 2. 生成唯一token（增强随机性）
        $token = md5(uniqid(mt_rand(), true) . $filePath . $userId . config('app.app_key'));
        
        // 3. 存储下载凭证（包含用户信息，防止链接盗用）
        $cacheData = [
            'path'      => $filePath,
            'user_id'   => $userId,
            'resource_id' => $resourceId,
            'expire'    => time() + $expire,
            'ip'        => Request::ip() // 绑定生成链接的IP，进一步防盗用
        ];
        
        // 4. 缓存凭证（设置和有效期一致的缓存时间）
        Cache::set('download_' . $token, $cacheData, $expire);

        // 5. 生成完整下载URL（兼容CLI模式，CLI下无request对象）
        $domain = config('app.domain') ?: (Request::has('domain') ? Request::domain() : '');
        return rtrim($domain, '/') . '/api/download/go?token=' . $token;
    }

    /**
     * 验证token并返回文件信息
     * @param string $token 下载令牌
     * @return array|null 文件信息数组（path, user_id, resource_id）或null
     */
    public static function verify(string $token): ?array
    {
        // 1. 基础校验
        if (empty($token)) {
            return null;
        }

        // 2. 获取缓存数据
        $cacheKey = 'download_' . $token;
        $data = Cache::get($cacheKey);
        
        // 3. 校验缓存是否存在
        if (!$data) {
            return null;
        }

        // 4. 校验是否过期
        if ($data['expire'] < time()) {
            Cache::delete($cacheKey);
            return null;
        }

        // 5. 校验IP（可选，增强安全性，可通过配置开关控制）
        $checkIp = config('app.download_check_ip', true);
        if ($checkIp && $data['ip'] != Request::ip()) {
            Cache::delete($cacheKey);
            return null;
        }

        // 6. 校验用户IP是否在黑名单
        if (IpBlacklist::where('ip', Request::ip())->count() > 0) {
            Cache::delete($cacheKey);
            return null;
        }

        // 7. 单次有效，验证后立即删除缓存
        Cache::delete($cacheKey);

        // 8. 记录下载日志（可选，可在调用方记录）
        self::logDownload($data['user_id'], $data['resource_id']);

        // 9. 返回核心信息
        return [
            'path' => $data['path'],
            'user_id' => $data['user_id'],
            'resource_id' => $data['resource_id']
        ];
    }

    /**
     * 安全获取文件真实路径（防止路径穿越）
     * @param string $filePath 相对路径
     * @return string|false 真实路径或false
     */
    public static function getRealFilePath(string $filePath): string|false
    {
        // 1. 拼接基础路径
        $basePath = public_path();
        $realPath = realpath($basePath . ltrim($filePath, '/'));

        // 2. 校验路径是否在public目录内（防止路径穿越攻击）
        if (!$realPath || !str_starts_with($realPath, $basePath)) {
            return false;
        }

        // 3. 校验文件是否存在且可读
        if (!is_file($realPath) || !is_readable($realPath)) {
            return false;
        }

        return $realPath;
    }

    /**
     * 记录下载日志
     * @param int $userId 用户ID
     * @param int $resourceId 资源ID
     */
    private static function logDownload(int $userId, int $resourceId): void
    {
        try {
            // 异步记录（不阻塞下载流程）
            UserDownloadRecord::create([
                'user_id'     => $userId,
                'resource_id' => $resourceId,
                'ip'          => Request::ip(),
                'status'      => 1, // 1=成功
                'create_time' => time()
            ]);

            // 更新资源下载次数（使用inc原子操作）
            \app\model\Resource::where('id', $resourceId)->inc('download_count')->update();
        } catch (\Exception $e) {
            // 日志记录失败不影响下载，仅记录异常
            trace('下载日志记录失败：' . $e->getMessage(), 'error');
        }
    }

    /**
     * 生成下载响应（直接输出文件）
     * @param string $token 下载令牌
     * @return \think\Response
     */
    public static function response(string $token): \think\Response
    {
        // 1. 验证token
        $data = self::verify($token);
        if (!$data) {
            return json(['code' => 0, 'msg' => '下载链接无效或已过期']);
        }

        // 2. 获取真实文件路径
        $realPath = self::getRealFilePath($data['path']);
        if (!$realPath) {
            return json(['code' => 0, 'msg' => '文件不存在或无法访问']);
        }

        // 3. 生成下载响应
        $fileName = basename($realPath);
        return response()->download($realPath, $fileName, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-cache'
        ]);
    }
}