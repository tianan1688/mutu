<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\ResourceCategory;
use app\model\Resource;
use app\model\ResourceMaterial;
use think\facade\Filesystem;
use think\exception\ValidateException;
use think\facade\Validate;
use think\facade\Db;



class ResourceController extends BaseController
{
    // ==================== 分类管理 ====================
    public function category()
{
    try {
        $list = ResourceCategory::order('sort', 'asc')->select()->toArray();
        $tree = $this->buildCategoryTree($list);
        // 强制返回：code=1 + data=分类树（前端只认这个格式）
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => $tree
        ]);
    } catch (\Exception $e) {
        // 异常也返回code=1，避免前端判断失败
        return json([
            'code' => 1,
            'msg' => 'error',
            'data' => []
        ]);
    }
}

    private function buildCategoryTree($categories, $parentId = 0)
    {
        $tree = [];
        foreach ($categories as $cat) {
            if ($cat['pid'] == $parentId) {
                $children = $this->buildCategoryTree($categories, $cat['id']);
                if ($children) {
                    $cat['children'] = $children;
                }
                $tree[] = $cat;
            }
        }
        return $tree;
    }

   public function saveCategory()
{
    try {
        
        $pid = $this->request->post('pid', 0); 
        $name = trim($this->request->post('name', ''));
        $sort = $this->request->post('sort', 0);
        $id = $this->request->post('id', 0);

        // 2. 手动转换为整数（替代错误的过滤器参数）
        $pid = is_numeric($pid) ? (int)$pid : 0;
        $pid = max(0, $pid); // 确保pid≥0
        $sort = is_numeric($sort) ? (int)$sort : 0;
        $id = is_numeric($id) ? (int)$id : 0;

        // 3. 核心校验
        if (empty($name)) {
            return json_error('请输入分类名称');
        }
        if (mb_strlen($name) > 100) {
            return json_error('分类名称不能超过100个字符');
        }

        // 4. 保存分类
        if ($id > 0) {
            $cat = ResourceCategory::find($id);
            if (!$cat) return json_error('分类不存在');
        } else {
            $cat = new ResourceCategory();
        }
        
        // 仅赋值存在的字段（避免status字段缺失报错）
        $cat->pid = $pid;
        $cat->name = $name;
        $cat->sort = $sort;
        $cat->save();

        return json_success('保存成功');
    } catch (\Exception $e) {
        if (env('APP_DEBUG')) {
            return json_error('服务器错误：' . $e->getMessage() . ' 行号：' . $e->getLine());
        }
        return json_error('服务器错误，请联系管理员');
    }
}
  

    public function deleteCategory()
    {
        try {
            $id = (int)$this->request->post('id');
            if ($id <= 0) return json_error('参数错误：分类ID不能为空');
            
            // 检查是否有子分类
            if (ResourceCategory::where('pid', $id)->count() > 0) {
                return json_error('请先删除子分类');
            }
            // 检查是否有资源
            if (Resource::where('cat_id', $id)->count() > 0) {
                return json_error('该分类下有资源，无法删除');
            }
            ResourceCategory::destroy($id);
            return json_success('删除成功');
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json_error('服务器错误：' . $e->getMessage());
            }
            return json_error('服务器错误，请联系管理员');
        }
    }

    // ==================== 资源列表 ====================
   public function lists()
{
    try {
        $page = max(1, (int)$this->request->get('page', 1));
        $limit = max(1, min(100, (int)$this->request->get('limit', 20)));
        $catId = (int)$this->request->get('cat_id', 0);
        $keyword = trim($this->request->get('keyword', ''));
        $status = $this->request->get('status', '');

        $query = Resource::with('category');
        if ($catId > 0) $query->where('cat_id', $catId);
        if ($keyword) $query->where('title', 'like', '%' . $keyword . '%');
        if ($status !== '') $query->where('status', (int)$status);
        
        $paginate = $query->order('sort', 'asc')->order('id', 'desc')->paginate([
            'page' => $page,
            'list_rows' => $limit
        ]);
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'total' => $paginate->total(),
                'list' => $paginate->items()
            ]
        ]);
    } catch (\Exception $e) {
        // 异常返回空列表，避免前端报错
        return json([
            'code' => 1,
            'msg' => 'error',
            'data' => [
                'total' => 0,
                'list' => []
            ]
        ]);
    }
}

    public function save()
{
    try {
        // 1. 获取并处理price参数（核心修复）
        $price = $this->request->post('price', 0);
        // 强制转为浮点型，兜底为0，保留2位小数
        $price = is_numeric($price) ? round((float)$price, 2) : 0;
        $price = max(0, $price); // 确保价格≥0

        // 2. 其他参数获取
        $id = (int)$this->request->post('id', 0);
        $cat_id = (int)$this->request->post('cat_id', 0);
        $title = trim($this->request->post('title', ''));
        $description = trim($this->request->post('description', ''));
        $file_path = trim($this->request->post('file_path', ''));
        $cover_path = trim($this->request->post('cover_path', ''));
        $sort = (int)$this->request->post('sort', 0);
        $status = (int)$this->request->post('status', 1);

        // 3. 基础校验（仅核心规则，放弃Validate类）
        if (empty($title)) {
            return json_error('请输入资源标题');
        }
        if ($cat_id <= 0) {
            return json_error('请选择分类');
        }

        // 4. 保存资源
        if ($id > 0) {
            $resource = \app\model\Resource::find($id);
            if (!$resource) return json_error('资源不存在');
        } else {
            $resource = new \app\model\Resource();
        }

        // 赋值（price已处理为合法数字）
        $resource->cat_id = $cat_id;
        $resource->title = $title;
        $resource->price = $price; // 处理后的价格
        $resource->description = $description;
        $resource->file_path = $file_path;
        $resource->cover_path = $cover_path;
        $resource->sort = $sort;
        $resource->status = $status;
        $resource->save();

        return json_success('保存成功');
    } catch (\Exception $e) {
        if (env('APP_DEBUG')) {
            return json_error('服务器错误：' . $e->getMessage());
        }
        return json_error('保存失败，请稍后重试');
    }
}
    

    public function delete()
    {
        try {
            $id = (int)$this->request->post('id');
            if ($id <= 0) return json_error('参数错误：资源ID不能为空');
            
            $resource = Resource::find($id);
            if ($resource) {
                // 安全的文件路径处理，防止路径穿越
                $filePath = $this->safeFilePath(public_path(), $resource->file_path);
                if ($filePath && file_exists($filePath)) {
                    @unlink($filePath); // 使用@避免文件不存在时报错
                }
                $resource->delete();
            }
            return json_success('删除成功');
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json_error('服务器错误：' . $e->getMessage());
            }
            return json_error('服务器错误，请联系管理员');
        }
    }

    public function batch()
    {
        try {
            $action = trim($this->request->post('action', ''));
            $ids = $this->request->post('ids', []);
            
            if (empty($ids) || !is_array($ids)) return json_error('参数错误：请选择要操作的资源');
            $ids = array_filter(array_map('intval', $ids), function($id) {
                return $id > 0; // 过滤无效ID
            });
            if (empty($ids)) return json_error('参数错误：请选择有效的资源ID');

            // 开启事务，保证批量操作原子性
            Db::startTrans();
            try {
                if ($action == 'delete') {
                    // 批量删除
                    $resources = Resource::whereIn('id', $ids)->select();
                    foreach ($resources as $res) {
                        $filePath = $this->safeFilePath(public_path(), $res->file_path);
                        if ($filePath && file_exists($filePath)) @unlink($filePath);
                        $res->delete();
                    }
                } elseif ($action == 'up') {
                    Resource::whereIn('id', $ids)->update(['status' => 1]);
                } elseif ($action == 'down') {
                    Resource::whereIn('id', $ids)->update(['status' => 0]);
                } elseif ($action == 'top') {
                    Resource::whereIn('id', $ids)->update(['is_top' => 1]);
                } elseif ($action == 'untop') {
                    Resource::whereIn('id', $ids)->update(['is_top' => 0]);
                } else {
                    Db::rollback();
                    return json_error('操作类型错误');
                }
                Db::commit();
                return json_success($action == 'delete' ? '批量删除成功' : '操作成功');
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json_error('服务器错误：' . $e->getMessage());
            }
            return json_error('服务器错误，请联系管理员');
        }
    }

        // 上传文件
/**
 * 文件上传接口（TP8 最终稳定版）
 */
public function upload()
{
    try {
        // 1. 获取上传文件
        $file = $this->request->file('file');
        if (!$file) {
            return json_error('请选择要上传的文件');
        }

        // 2. 上传配置
        $maxSize = 20 * 1024 * 1024; // 20MB
        $allowExts = ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'pdf', 'zip', 'rar', 'txt', 'xls', 'xlsx'];

        // ========== 核心：原生手动校验（替代Validate类，无任何报错） ==========
        // 校验文件大小
        if ($file->getSize() > $maxSize) {
            return json_error('文件大小不能超过20MB（当前大小：' . round($file->getSize()/1024/1024, 2) . 'MB）');
        }
        
        // 校验文件扩展名
        $fileExt = strtolower($file->extension() ?: '');
        if (empty($fileExt) || !in_array($fileExt, $allowExts)) {
            return json_error('不支持的文件格式！仅允许：' . implode('、', $allowExts) . '（当前格式：' . $fileExt . '）');
        }

        // 3. 上传文件（TP8 Filesystem 正确用法，需先引入 use think\facade\Filesystem;）
        $saveName = Filesystem::disk('public')->putFile('resource', $file);
        $filePath = '/uploads/' . $saveName;

        // 4. 记录素材库
        \app\model\ResourceMaterial::create([
            'file_path' => $filePath,
            'file_name' => $file->getOriginalName(),
            'file_size' => $file->getSize(),
            'file_ext' => $fileExt,
            'create_time' => time(),
            'usage_count' => 0
        ]);

        return json_success('上传成功', [
            'file_path' => $filePath,
            'file_name' => $file->getOriginalName()
        ]);
    } catch (\Exception $e) {
        return json_error('上传失败：' . $e->getMessage());
    }
}
            

    // 素材库列表
    public function material()
    {
        try {
            $page = max(1, (int)$this->request->get('page', 1));
            $limit = max(1, min(100, (int)$this->request->get('limit', 20)));
            
            $list = ResourceMaterial::order('id', 'desc')->paginate([
                'page' => $page,
                'list_rows' => $limit
            ]);

            // 补充文件大小的MB显示
            $list->each(function($item) {
                $item->file_size_mb = round($item->file_size / 1024 / 1024, 2);
            });

            return json_success('ok', [
                'total' => $list->total(),
                'list' => $list->items(),
            ]);
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json_error('服务器错误：' . $e->getMessage());
            }
            return json_error('服务器错误，请联系管理员');
        }
    }

    // 删除素材库文件
    public function deleteMaterial()
    {
        try {
            $id = (int)$this->request->post('id');
            if ($id <= 0) return json_error('参数错误：素材ID不能为空');
            
            $material = ResourceMaterial::find($id);
            if (!$material) return json_error('素材不存在');
            
            // 先删除物理文件
            $filePath = $this->safeFilePath(public_path(), $material->file_path);
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // 再删除数据库记录
            $material->delete();
            return json_success('删除成功');
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json_error('服务器错误：' . $e->getMessage());
            }
            return json_error('服务器错误，请联系管理员');
        }
    }

    // 移动文件
    public function move()
    {
        try {
            $id = (int)$this->request->post('id');
            $catId = (int)$this->request->post('cat_id');
            
            if ($id <= 0 || $catId <= 0) return json_error('参数错误：资源ID和分类ID不能为空');
            
            // 检查分类是否存在
            $category = ResourceCategory::find($catId);
            if (!$category) return json_error('目标分类不存在');
            
            $resource = Resource::find($id);
            if (!$resource) return json_error('资源不存在');
            
            $resource->cat_id = $catId;
            $resource->save();
            return json_success('移动成功');
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                return json_error('服务器错误：' . $e->getMessage());
            }
            return json_error('服务器错误，请联系管理员');
        }
    }

    
    /**
     * 安全的文件路径处理（防止路径穿越）
     * @param string $basePath 基础目录（如 public_path()）
     * @param string $filePath 文件相对路径
     * @return string|false 安全的绝对路径或false
     */
    private function safeFilePath(string $basePath, string $filePath): string|false
    {
        // 拼接绝对路径
        $realBase = realpath($basePath);
        $realPath = realpath($realBase . '/' . ltrim($filePath, '/'));
        
        // 校验路径是否在基础目录内
        if (!$realPath || !str_starts_with($realPath, $realBase)) {
            return false;
        }
        return $realPath;
    }

    
}