<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\MaterialFolder;
use app\model\Material;
use think\facade\Filesystem;

class MaterialController extends BaseController
{
    // 1. 获取文件夹列表（GET /admin/material/folder）
    public function folder()
    {
        try {
            $folders = MaterialFolder::order('id', 'asc')->select()->toArray();
            return json(['code' => 1, 'data' => $folders, 'msg' => '获取成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }

    // 2. 新建文件夹（POST /admin/material/folder）- TP8适配
    public function createFolder()
{
    try {
        $name = $this->request->post('name', '');
        $parent_id = $this->request->post('parent_id', 0, 'intval');

        if (empty(trim($name))) {
            return json([
                'code' => 0,
                'msg' => '文件夹名称不能为空',
                'data' => []
            ]);
        }

        $exists = MaterialFolder::where('name', trim($name))
            ->where('parent_id', $parent_id)
            ->find() ? true : false;
        
        if ($exists) {
            return json([
                'code' => 0,
                'msg' => '该文件夹名称已存在',
                'data' => []
            ]);
        }

        $folder = new MaterialFolder();
        $folder->name = trim($name);
        $folder->parent_id = $parent_id;
        $folder->save();

        // 修复：返回格式严格包含code/msg/data，且code=1
        return json([
            'code' => 1,
            'msg' => '文件夹创建成功',
            'data' => $folder->toArray()
        ]);
    } catch (\Exception $e) {
        return json([
            'code' => 0,
            'msg' => '创建失败：' . $e->getMessage(),
            'data' => []
        ]);
    }
}
    // 3. 获取素材列表（GET /admin/material/list）
    public function list()
    {
        try {
            $folder_id = $this->request->get('folder_id', 0, 'intval');
            $materials = Material::where('folder_id', $folder_id)
                ->order('create_time', 'desc')
                ->select()->toArray();
            return json(['code' => 1, 'data' => $materials, 'msg' => '获取成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }

    // 4. 上传素材（POST /admin/material/upload）- TP8适配
    public function upload()
    {
        try {
            $folder_id = $this->request->post('folder_id', 0, 'intval');
            $file = $this->request->file('file');
            
            if (!$file) {
                return json(['code' => 0, 'msg' => '请选择要上传的文件']);
            }

            // TP8官方上传写法
            $saveName = Filesystem::disk('public')->putFile('material', $file);
            $fileInfo = $file->getInfo();

            // 保存到数据库
            $material = new Material();
            $material->file_name = $fileInfo['name'];
            $material->file_path = '/storage/' . str_replace('\\', '/', $saveName); // TP8兼容路径分隔符
            $material->file_size = $fileInfo['size'];
            $material->file_ext = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            $material->folder_id = $folder_id;
            $material->save();

            return json(['code' => 1, 'data' => $material, 'msg' => '上传成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }
    }

    // 5. 素材重命名（POST /admin/material/rename）
    public function rename()
    {
        try {
            $id = $this->request->post('id', 0, 'intval');
            $name = $this->request->post('name', '');

            if (empty($id) || empty(trim($name))) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }

            $material = Material::find($id);
            if (!$material) {
                return json(['code' => 0, 'msg' => '素材不存在']);
            }

            $material->file_name = trim($name);
            $material->save();

            return json(['code' => 1, 'msg' => '重命名成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '重命名失败：' . $e->getMessage()]);
        }
    }

    // 6. 删除素材（POST /admin/material/delete）
    public function delete()
    {
        try {
            $id = $this->request->post('id', 0, 'intval');
            $material = Material::find($id);
            
            if (!$material) {
                return json(['code' => 0, 'msg' => '素材不存在']);
            }

            // TP8删除文件
            $filePath = root_path('public') . ltrim($material->file_path, '/');
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $material->delete();
            return json(['code' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }
    // 7. 文件夹重命名（POST /admin/material/folder/rename）
public function renameFolder()
{
    try {
        $id = $this->request->post('id', 0, 'intval');
        $name = $this->request->post('name', '');
        $parent_id = $this->request->post('parent_id', 0, 'intval');

        if (empty($id) || empty(trim($name))) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        $folder = MaterialFolder::find($id);
        if (!$folder) {
            return json(['code' => 0, 'msg' => '文件夹不存在']);
        }

        // 检查同目录下重名
        $exists = MaterialFolder::where('name', trim($name))
            ->where('parent_id', $parent_id)
            ->where('id', '<>', $id)
            ->find() ? true : false;
        
        if ($exists) {
            return json(['code' => 0, 'msg' => '该文件夹名称已存在']);
        }

        $folder->name = trim($name);
        $folder->save();

        return json(['code' => 1, 'msg' => '重命名成功', 'data' => $folder]);
    } catch (\Exception $e) {
        return json(['code' => 0, 'msg' => '重命名失败：' . $e->getMessage()]);
    }
}

// 8. 文件夹删除（POST /admin/material/folder/delete）
public function deleteFolder()
{
    try {
        $id = $this->request->post('id', 0, 'intval');
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 检查是否有子文件夹/素材
        $hasChild = MaterialFolder::where('parent_id', $id)->count() > 0;
        $hasMaterial = Material::where('folder_id', $id)->count() > 0;
        if ($hasChild || $hasMaterial) {
            return json(['code' => 0, 'msg' => '该文件夹下有子文件夹或素材，无法删除']);
        }

        $folder = MaterialFolder::find($id);
        if (!$folder) {
            return json(['code' => 0, 'msg' => '文件夹不存在']);
        }

        $folder->delete();
        return json(['code' => 1, 'msg' => '删除成功']);
    } catch (\Exception $e) {
        return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
    }
}
}