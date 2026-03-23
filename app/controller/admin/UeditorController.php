<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;

class UeditorController extends BaseController
{
    
    public function server()
    {
        $action = $this->request->get('action', '');
        if ($action === 'config') {
            
            $config = [
                "imageActionName" => "uploadimage",
                "imageMaxSize" => 2048000,
                "imageAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"],
                "imageCompressEnable" => true,
                "imageCompressBorder" => 1600,
                "imageInsertAlign" => "none",
                "imageUrlPrefix" => "",
                "imagePathFormat" => "/uploads/ueditor/image/{yyyy}{mm}{dd}/{time}{rand:6}",
            ];
            return json($config);
        }
        // 其他action（如上传）可后续扩展
        return json([]);
    }
}