<?php
// 全局公共函数

use think\facade\Cache;
use think\facade\Config;
use think\facade\Request;

if (!function_exists('generate_random_str')) {
    function generate_random_str($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        $charsLength = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $charsLength - 1)];
        }
        return $str;
    }
}

if (!function_exists('generate_order_no')) {
    function generate_order_no($prefix = '')
    {
        return $prefix . date('YmdHis') . mt_rand(1000, 9999);
    }
}

if (!function_exists('get_config')) {
    function get_config($key, $default = '')
    {
        static $configs = [];
        if (empty($configs)) {
            $configs = Cache::remember('system_config', function () {
                $list = \app\model\SystemConfig::select();
                $arr = [];
                foreach ($list as $item) {
                    $arr[$item->key] = $item->value;
                }
                return $arr;
            }, 3600);
        }
        return $configs[$key] ?? $default;
    }
}

if (!function_exists('set_config')) {
    function set_config($key, $value)
    {
        $config = \app\model\SystemConfig::where('key', $key)->find();
        if ($config) {
            $config->value = $value;
            $config->save();
        } else {
            \app\model\SystemConfig::create(['key' => $key, 'value' => $value]);
        }
        
        Cache::delete('system_config');
        get_config($key); 
    }
}

if (!function_exists('json_success')) {
    function json_success($msg = 'success', $data = [])
    {
        return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
    }
}

if (!function_exists('json_error')) {
    function json_error($msg = 'error', $code = 0)
    {
        
        return json(['code' => $code, 'msg' => $msg, 'data' => []]);
    }
}

if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        
        $ip = Request::header('X-Real-IP');
        if (!$ip) {
            $ip = Request::header('X-Forwarded-For');
            if ($ip) {
                $ip = explode(',', $ip)[0];
            }
        }
        return $ip ?: Request::ip();
    }
}

if (!function_exists('encrypt_data')) {
    function encrypt_data($data, $key = '')
    {
        if (!$key) {
            $key = Config::get('app.app_key') ?: env('ENCRYPT_KEY', generate_random_str(16));
        }
        // 修复：使用安全的AES-128-CBC模式
        $iv = substr(md5($key), 0, 16);
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encrypted);
    }
}

if (!function_exists('decrypt_data')) {
    function decrypt_data($encrypted, $key = '')
    {
        if (!$key) {
            $key = Config::get('app.app_key') ?: env('ENCRYPT_KEY', generate_random_str(16));
        }
        $iv = substr(md5($key), 0, 16);
        $encrypted = base64_decode($encrypted);
        return openssl_decrypt($encrypted, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}

if (!function_exists('safe_file_path')) {
    function safe_file_path($basePath, $filePath)
    {
        $realBase = realpath($basePath);
        $realFile = realpath($basePath . '/' . $filePath);
        
        return $realFile && str_starts_with($realFile, $realBase) ? $realFile : false;
    }
}