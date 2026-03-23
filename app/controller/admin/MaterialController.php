<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\MaterialFolder;
use app\model\Material;
use think\facade\Filesystem;

class MaterialController extends BaseController
{
    // 1. 获取文件夹列表
    public function folder()
    {
        try {
            $list = MaterialFolder::order('id', 'asc')->select()->toArray();
            return json(['code' => 1, 'data' => $list, 'msg' => '获取成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }

  // 2. 创建文件夹
public function createFolder()
{
    try {
        $name = trim($this->request->post('name', ''));
        $parent_id = $this->request->post('parent_id', 0, 'intval');

        if (empty($name)) {
            return json(['code' => 0, 'msg' => '文件夹名称不能为空']);
        }

        $exists = MaterialFolder::where('name', $name)
            ->where('parent_id', $parent_id)
            ->find();

        if ($exists) {
            return json(['code' => 0, 'msg' => '同目录下已存在该文件夹']);
        }

        $folder = new MaterialFolder();
        $folder->name = $name;
        $folder->parent_id = $parent_id;
        $folder->save();

        return json(['code' => 1, 'data' => $folder->toArray(), 'msg' => '创建成功']);
    } catch (\Exception $e) {
        return json(['code' => 0, 'msg' => '创建失败：' . $e->getMessage()]);
    }
}


    // 3. 文件夹重命名
    public function renameFolder()
    {
        try {
            $id = $this->request->post('id', 0, 'intval');
            $name = trim($this->request->post('name', ''));
            $parent_id = $this->request->post('parent_id', 0, 'intval');

            if (empty($id) || empty($name)) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }

            $folder = MaterialFolder::find($id);
            if (!$folder) {
                return json(['code' => 0, 'msg' => '文件夹不存在']);
            }

            $exists = MaterialFolder::where('name', $name)
                ->where('parent_id', $parent_id)
                ->where('id', '<>', $id)
                ->find();

            if ($exists) {
                return json(['code' => 0, 'msg' => '同目录下已存在该文件夹名称']);
            }

            $folder->name = $name;
            $folder->save();

            return json(['code' => 1, 'msg' => '重命名成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '重命名失败：' . $e->getMessage()]);
        }
    }

    // 4. 文件夹删除
    public function deleteFolder()
    {
        try {
            $id = $this->request->post('id', 0, 'intval');
            if (empty($id)) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }

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

    // 5. 获取素材列表
    public function list()
    {
        try {
            $folder_id = $this->request->get('folder_id', 0, 'intval');
            $list = Material::where('folder_id', $folder_id)
                ->order('create_time', 'desc')
                ->select()->toArray();
            return json(['code' => 1, 'data' => $list, 'msg' => '获取成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }

    // 6. 上传素材（最终修复：TP8 正确方法 + 匹配你的filesystem配置）
    public function upload()
    {
        try {
            $file = $this->request->file('file');
            $folder_id = $this->request->post('folder_id', 0, 'intval');

            if (!$file) {
                return json(['code' => 0, 'msg' => '请选择要上传的文件']);
            }

            // 核心：匹配你的public磁盘配置（uploads目录）
            $saveName = Filesystem::disk('public')->putFile('material', $file);
            $filePath = '/uploads/' . str_replace('\\', '/', $saveName);

            // 🔥 修复：TP8 正确获取文件信息的方法
            $originalName = $file->getOriginalName(); // 获取原始文件名
            $fileSize = $file->getSize(); // 获取文件大小（字节）
            $fileExt = $file->getExtension(); // 获取文件扩展名

            // 保存到数据库
            $material = new Material();
            $material->file_name = $originalName;
            $material->file_path = $filePath;
            $material->file_size = $fileSize;
            $material->file_ext = $fileExt;
            $material->folder_id = $folder_id;
            $material->save();

            return json(['code' => 1, 'data' => $material->toArray(), 'msg' => '上传成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }
    }

    // 7. 素材重命名
    public function rename()
    {
        try {
            $id = $this->request->post('id', 0, 'intval');
            $name = trim($this->request->post('name', ''));

            if (empty($id) || empty($name)) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }

            $material = Material::find($id);
            if (!$material) {
                return json(['code' => 0, 'msg' => '素材不存在']);
            }

            $material->file_name = $name;
            $material->save();

            return json(['code' => 1, 'msg' => '重命名成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '重命名失败：' . $e->getMessage()]);
        }
    }

    // 8. 素材删除
    public function delete()
    {
        try {
            $id = $this->request->post('id', 0, 'intval');
            if (empty($id)) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }

            $material = Material::find($id);
            if (!$material) {
                return json(['code' => 0, 'msg' => '素材不存在']);
            }

            // 删除物理文件
            $filePath = app()->getRootPath() . 'public' . $material->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $material->delete();
            return json(['code' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    // 9. 素材移动（新增）
public function move()
{
    try {
        $id = $this->request->post('id', 0, 'intval');
        $target_folder_id = $this->request->post('target_folder_id', 0, 'intval');

        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误：缺少素材ID']);
        }

        // 查找素材是否存在
        $material = Material::find($id);
        if (!$material) {
            return json(['code' => 0, 'msg' => '素材不存在']);
        }

        // 如果目标文件夹ID不为0，检查目标文件夹是否存在
        if ($target_folder_id != 0) {
            $targetFolder = MaterialFolder::find($target_folder_id);
            if (!$targetFolder) {
                return json(['code' => 0, 'msg' => '目标文件夹不存在']);
            }
        }

        // 如果已经在目标文件夹中，直接返回成功（可选）
        if ($material->folder_id == $target_folder_id) {
            return json(['code' => 1, 'msg' => '素材已在目标文件夹中']);
        }

        // 更新文件夹ID
        $material->folder_id = $target_folder_id;
        $material->save();

        return json(['code' => 1, 'msg' => '移动成功']);
    } catch (\Exception $e) {
        return json(['code' => 0, 'msg' => '移动失败：' . $e->getMessage()]);
    }
}
}
