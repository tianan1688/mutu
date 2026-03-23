<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Distributor;
use app\model\DistributorOrder;
use app\model\WithdrawApply;

class Distributor extends BaseController
{
    public function apply()
    {
        $user = $this->request->user;
        $data = $this->request->post();

        // 检查是否已申请
        $exist = Distributor::where('user_id', $user->id)->find();
        if ($exist && $exist->status != 2) {
            return json_error('您已提交申请，请勿重复提交');
        }

        $needReal = get_config('distributor_apply_need_real', 1);
        if ($needReal) {
            $validate = new \think\Validate();
            $validate->rule([
                'real_name' => 'require',
                'id_card' => 'require|idCard',
                'mobile' => 'require|mobile',
            ]);
            if (!$validate->check($data)) {
                return json_error($validate->getError());
            }
        }

        $distributor = new Distributor();
        $distributor->user_id = $user->id;
        $distributor->real_name = $data['real_name'] ?? '';
        $distributor->id_card = $data['id_card'] ?? '';
        $distributor->id_card_front = $data['id_card_front'] ?? '';
        $distributor->id_card_back = $data['id_card_back'] ?? '';
        $distributor->mobile = $data['mobile'] ?? '';
        $distributor->status = 0; // 待审核
        $distributor->apply_time = time();
        $distributor->create_time = time();
        $distributor->save();

        return json_success('申请已提交，等待审核');
    }

    public function center()
    {
        $user = $this->request->user;
        $distributor = Distributor::where('user_id', $user->id)->find();
        if (!$distributor || $distributor->status != 1) {
            return json_error('您还不是分销商');
        }
        return json_success('ok', [
            'total_commission' => $distributor->total_commission,
            'frozen_commission' => $distributor->frozen_commission,
            'withdrawn_commission' => $distributor->withdrawn_commission,
            'level' => $distributor->level,
        ]);
    }

    public function orders()
    {
        $user = $this->request->user;
        $distributor = Distributor::where('user_id', $user->id)->find();
        if (!$distributor) return json_error('不是分销商');
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $list = DistributorOrder::where('distributor_id', $distributor->id)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = DistributorOrder::where('distributor_id', $distributor->id)->count();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }

    public function withdraw()
    {
        $user = $this->request->user;
        $distributor = Distributor::where('user_id', $user->id)->find();
        if (!$distributor || $distributor->status != 1) {
            return json_error('不是分销商');
        }

        $amount = $this->request->post('amount');
        $accountType = $this->request->post('account_type'); // wechat/alipay
        $account = $this->request->post('account');
        $realName = $this->request->post('real_name');

        $min = get_config('withdraw_min', 10);
        if ($amount < $min) {
            return json_error('提现金额不能低于' . $min . '元');
        }
        if ($distributor->total_commission - $distributor->withdrawn_commission < $amount) {
            return json_error('可提现金额不足');
        }

        // 计算手续费
        $fee = get_config('withdraw_fee', 0);
        $actualAmount = $amount - $fee;
        if ($actualAmount <= 0) {
            return json_error('提现金额扣除手续费后为0');
        }

        WithdrawApply::create([
            'distributor_id' => $distributor->id,
            'amount' => $amount,
            'fee' => $fee,
            'actual_amount' => $actualAmount,
            'account_type' => $accountType,
            'account' => $account,
            'real_name' => $realName,
            'status' => 0,
            'create_time' => time(),
        ]);

        // 冻结金额
        $distributor->frozen_commission += $amount;
        $distributor->save();

        return json_success('提现申请已提交');
    }

    public function withdrawRecord()
    {
        $user = $this->request->user;
        $distributor = Distributor::where('user_id', $user->id)->find();
        if (!$distributor) return json_error('不是分销商');
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        $list = WithdrawApply::where('distributor_id', $distributor->id)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = WithdrawApply::where('distributor_id', $distributor->id)->count();
        return json_success('ok', ['total' => $total, 'list' => $list]);
    }
}