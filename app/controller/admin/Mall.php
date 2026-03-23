<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\GoodsCategory;
use app\model\Goods;
use think\facade\Filesystem;

class Mall extends BaseController
{
    // ==================== 商品分类管理 ====================
    public function category()
    {
        $list = GoodsCategory::order('sort', 'asc')->select();
        $tree = $this->buildCategoryTree($list);
        return json_success('ok', $tree);
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
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        if ($id) {
            $cat = GoodsCategory::find($id);
            if (!$cat) return json_error('分类不存在');
        } else {
            $cat = new GoodsCategory();
        }
        $cat->save($data);
        return json_success('保存成功');
    }

    public function deleteCategory()
    {
        $id = $this->request->post('id');
        if (!$id) return json_error('参数错误');
        // 转换为整数，避免类型问题
        $id = (int)$id;
        if (GoodsCategory::where('pid', $id)->count() > 0) {
            return json_error('请先删除子分类');
        }
        if (Goods::where('cat_id', $id)->count() > 0) {
            return json_error('该分类下有商品，无法删除');
        }
        GoodsCategory::destroy($id);
        return json_success('删除成功');
    }

    // ==================== 商品管理 ====================
    public function goods()
    {
        try {
            // 核心修复：强制转换所有数字型参数为整数
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $catId = (int)$this->request->get('cat_id', 0);
            $keyword = trim($this->request->get('keyword', '')); // 字符串参数无需转换

            $query = Goods::with('category');
            if ($catId > 0) {
                $query->where('cat_id', $catId);
            }
            if ($keyword) {
                $query->where('title', 'like', "%$keyword%");
            }
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            // 开发环境下返回具体错误信息，便于排查
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    public function saveGoods()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        // 转换为整数
        $id = (int)$id;
        if ($id) {
            $goods = Goods::find($id);
            if (!$goods) return json_error('商品不存在');
        } else {
            $goods = new Goods();
        }

        // 验证
        $validate = new \app\validate\GoodsValidate();
        if (!$validate->check($data)) {
            return json_error($validate->getError());
        }

        $goods->save($data);
        return json_success('保存成功', ['id' => $goods->id]);
    }

    public function deleteGoods()
    {
        $id = $this->request->post('id');
        if (!$id) return json_error('参数错误');
        // 转换为整数
        $id = (int)$id;
        Goods::destroy($id);
        return json_success('删除成功');
    }

    public function batchGoods()
    {
        $action = $this->request->post('action'); // up/down/delete
        $ids = $this->request->post('ids');
        if (!$ids || !is_array($ids)) return json_error('参数错误');
        // 将ids数组中的元素全部转为整数
        $ids = array_map('intval', $ids);

        if ($action == 'delete') {
            Goods::destroy($ids);
            return json_success('批量删除成功');
        } elseif ($action == 'up') {
            Goods::whereIn('id', $ids)->update(['status' => 1]);
            return json_success('已上架');
        } elseif ($action == 'down') {
            Goods::whereIn('id', $ids)->update(['status' => 0]);
            return json_success('已下架');
        }
        return json_error('操作类型错误');
    }
    
    // 商品封面上传
    public function upload()
    {
        $file = $this->request->file('file');
        if (!$file) return json_error('请选择文件');
        try {
            $saveName = Filesystem::disk('public')->putFile('goods', $file);
            $url = '/uploads/' . $saveName;
            return json_success('上传成功', ['url' => $url]);
        } catch (\Exception $e) {
            return json_error('上传失败：' . $e->getMessage());
        }
    }
}