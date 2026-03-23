<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\ResourceCategory;
use app\model\Resource;

class Index extends BaseController
{
    public function index()
    {
        // 轮播图（从系统配置获取）
        $banners = get_config('index_banners', '');
        $banners = $banners ? json_decode($banners, true) : [];

        // 分类列表
        $categories = ResourceCategory::where('status', 1)->order('sort', 'asc')->limit(8)->select();

        // 推荐资源
        $recommend = Resource::where('status', 1)->where('is_top', 1)->order('id', 'desc')->limit(6)->select();

        return json_success('ok', [
            'banners' => $banners,
            'categories' => $categories,
            'recommend' => $recommend,
        ]);
    }
}