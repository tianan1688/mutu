<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\DownloadService;

class Download extends BaseController
{
    public function go()
    {
        $token = $this->request->get('token');
        if (!$token) {
            return json_error('参数错误');
        }
        $path = DownloadService::verify($token);
        if (!$path) {
            return json_error('链接无效或已过期');
        }
        $fullPath = public_path() . ltrim($path, '/');
        if (!is_file($fullPath)) {
            return json_error('文件不存在');
        }
        // 下载文件
        return download($fullPath);
    }
}