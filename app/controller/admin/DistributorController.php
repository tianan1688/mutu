<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Distributor;
use app\model\DistributorOrder;
use app\model\WithdrawApply;

class DistributorController extends BaseController
{
    /**
     * 分销设置
     */
    public function setting()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 优化：配置项转为整数，避免存储字符串
            set_config('distributor_enable', (int)($data['enable'] ?? 0));
            set_config('distributor_apply_need_real', (int)($data['apply_need_real'] ?? 1));
            set_config('commission_resource_rate', (int)($data['commission_resource_rate'] ?? 0));
            set_config('commission_mall_rate', (int)($data['commission_mall_rate'] ?? 0));
            set_config('withdraw_min', (int)($data['withdraw_min'] ?? 10));
            set_config('withdraw_fee', (int)($data['withdraw_fee'] ?? 0));
            return json_success('保存成功');
        }

        $config = [
            'enable' => get_config('distributor_enable', 1),
            'apply_need_real' => get_config('distributor_apply_need_real', 1),
            'commission_resource_rate' => get_config('commission_resource_rate', 10),
            'commission_mall_rate' => get_config('commission_mall_rate', 5),
            'withdraw_min' => get_config('withdraw_min', 10),
            'withdraw_fee' => get_config('withdraw_fee', 0),
        ];
        return json_success('ok', $config);
    }

    /**
     * 分销申请列表
     */
    public function apply()
    {
        try {
            // 核心修复：所有数字型参数强制转为整数
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $status = (int)$this->request->get('status', 0); // 0待审核

            $query = Distributor::with('user')->where('status', $status);
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 审核分销申请
     */
    public function audit()
    {
        // 优化：参数转为整数/去空格
        $id = (int)$this->request->post('id');
        $status = (int)$this->request->post('status'); // 1通过 2拒绝
        $remark = trim($this->request->post('remark', ''));

        // 参数合法性校验
        if ($id <= 0 || !in_array($status, [1, 2])) {
            return json_error('参数错误：记录ID不能为空，审核状态只能是1（通过）或2（拒绝）');
        }

        $distributor = Distributor::find($id);
        if (!$distributor) return json_error('记录不存在');

        $distributor->status = $status;
        $distributor->pass_time = $status == 1 ? time() : null;
        $distributor->save();

        // 发送通知
        if ($status == 1) {
            \app\service\MessageService::send($distributor->user_id, 'distributor_pass', []);
        } else {
            \app\service\MessageService::send($distributor->user_id, 'distributor_reject', ['remark' => $remark]);
        }

        return json_success('操作成功');
    }

    /**
     * 分销订单
     */
    public function order()
    {
        try {
            // 核心修复：数字型参数转为整数
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $distributorId = (int)$this->request->get('distributor_id', 0);

            $query = DistributorOrder::with('distributor');
            // 优化：加>0判断，避免distributorId=0时无效筛选
            if ($distributorId > 0) $query->where('distributor_id', $distributorId);
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 提现申请列表
     */
    public function withdraw()
    {
        try {
            // 核心修复：数字型参数转为整数
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $status = $this->request->get('status', ''); // 保留空值，非空时转整数

            // 非空时转换为整数，避免字符串匹配
            if ($status !== '') $status = (int)$status;

            $query = WithdrawApply::with('distributor.user');
            if ($status !== '') $query->where('status', $status);
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select();

            return json_success('ok', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return json_error('服务器错误：' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 提现审核
     */
    public function withdrawAudit()
    {
        // 优化：参数转为整数/去空格
        $id = (int)$this->request->post('id');
        $status = (int)$this->request->post('status'); // 1已打款 2驳回
        $remark = trim($this->request->post('remark', ''));

        // 参数合法性校验
        if ($id <= 0 || !in_array($status, [1, 2])) {
            return json_error('参数错误：记录ID不能为空，审核状态只能是1（已打款）或2（驳回）');
        }

        $apply = WithdrawApply::find($id);
        if (!$apply) return json_error('记录不存在');

        $distributor = Distributor::find($apply->distributor_id);
        if (!$distributor) return json_error('分销商记录不存在');

        if ($status == 1) {
            // 打款成功
            $apply->status = 1;
            $apply->audit_time = time();
            $apply->save();

            // 更新分销商已提现佣金
            $distributor->withdrawn_commission += $apply->amount;
            $distributor->save();

            // 通知
            \app\service\MessageService::send($distributor->user_id, 'withdraw_success', [
                'amount' => $apply->amount,
            ]);
        } else {
            // 驳回：退款至钱包（解冻）
            $apply->status = 2;
            $apply->remark = $remark;
            $apply->audit_time = time();
            $apply->save();

            // 解冻佣金（避免负数）
            if ($distributor->frozen_commission >= $apply->amount) {
                $distributor->frozen_commission -= $apply->amount;
                $distributor->save();
            } else {
                return json_error('冻结佣金不足，无法驳回提现申请');
            }

            \app\service\MessageService::send($distributor->user_id, 'withdraw_reject', [
                'amount' => $apply->amount,
                'remark' => $remark,
            ]);
        }

        return json_success('操作成功');
    }

    /**
     * 禁用分销商
     */
    public function disable()
    {
        // 优化：参数转为整数
        $id = (int)$this->request->post('id');
        $status = (int)$this->request->post('status', 0); // 0禁用 1启用

        if ($id <= 0) return json_error('参数错误：记录ID不能为空');

        $distributor = Distributor::find($id);
        if (!$distributor) return json_error('记录不存在');
        
        $distributor->status = $status ? 1 : 2;
        $distributor->save();
        
        return json_success($status ? '已启用' : '已禁用');
    }
}