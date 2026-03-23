<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdConfig;
use app\model\AdStat;

class Ad extends BaseController
{
    /**
     * 广告位配置
     */
    public function config()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $id = $data['id'] ?? 0;
            if ($id) {
                $ad = AdConfig::find($id);
                if (!$ad) return json_error('广告不存在');
            } else {
                $ad = new AdConfig();
            }
            $ad->save($data);
            return json_success('保存成功');
        }

        $list = AdConfig::order('sort', 'asc')->select();
        return json_success('ok', $list);
    }

    /**
     * 广告统计数据
     */
    public function stats()
    {
        $date = $this->request->get('date', date('Y-m-d'));
        $list = AdStat::with('ad')->where('date', $date)->select();
        return json_success('ok', $list);
    }
}