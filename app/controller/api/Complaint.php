<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Complaint;
use think\facade\Filesystem;

class Complaint extends BaseController
{
    public function add()
    {
        $user = $this->request->user;
        $resourceId = $this->request->post('resource_id');
        $content = $this->request->post('content');
        $images = $this->request->post('images', ''); // 逗号分隔的图片路径

        if (!$resourceId || !$content) {
            return json_error('参数错误');
        }

        Complaint::create([
            'user_id' => $user->id,
            'resource_id' => $resourceId,
            'content' => $content,
            'images' => $images,
            'status' => 0,
            'create_time' => time(),
        ]);

        return json_success('投诉已提交');
    }

    public function lists()
    {
        $user = $this->request->user;
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $list = Complaint::where('user_id', $user->id)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = Complaint::where('user_id', $user->id)->count();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }

    // 上传投诉图片
    public function upload()
    {
        $file = $this->request->file('file');
        if (!$file) return json_error('请选择文件');
        try {
            $saveName = Filesystem::disk('public')->putFile('complaint', $file);
            $url = '/uploads/' . $saveName;
            return json_success('上传成功', ['url' => $url]);
        } catch (\Exception $e) {
            return json_error('上传失败：' . $e->getMessage());
        }
    }
}