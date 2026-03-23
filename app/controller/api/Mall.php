<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Goods;
use app\model\GoodsCategory;
use app\service\OrderService;

class Mall extends BaseController
{
    public function goods()
    {
        $catId = $this->request->get('cat_id', 0);
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $query = Goods::where('status', 1);
        if ($catId > 0) {
            $query->where('cat_id', $catId);
        }
        $total = $query->count();
        $list = $query->order('sort', 'asc')->order('id', 'desc')->page($page, $limit)->select();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }

    public function detail()
    {
        $id = $this->request->get('id');
        $goods = Goods::with('category')->find($id);
        if (!$goods || $goods->status != 1) {
            return json_error('商品不存在');
        }
        return json_success('ok', $goods);
    }

    public function createOrder()
    {
        $goodsId = $this->request->post('goods_id');
        $num = $this->request->post('num', 1);
        $address = $this->request->post('address', []);
        $user = $this->request->user;

        $result = OrderService::createMallOrder($user->id, $goodsId, $num, $address);
        if ($result['code'] == 1) {
            return json_success('订单创建成功', ['order_no' => $result['order']->order_no]);
        } else {
            return json_error($result['msg']);
        }
    }
}