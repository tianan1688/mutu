<?php
declare (strict_types=1);

namespace app\middleware;

use think\facade\Cache;

class RateLimit
{
    protected $limit = 60; // 每分钟请求次数
    protected $key = 'rate_limit:';

    public function handle($request, \Closure $next)
    {
        $ip = $request->ip();
        $key = $this->key . $ip;
        $current = Cache::get($key, 0);
        if ($current >= $this->limit) {
            return json_error('请求过于频繁，请稍后再试', 429);
        }
        Cache::set($key, $current + 1, 60);
        return $next($request);
    }
}