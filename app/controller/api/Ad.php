<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\AdStat;

class Ad extends BaseController
{
    public function impression()
    {
        $adId = $this->request->post('ad_id');
        if (!$adId) return json_error('参数错误');
        $date = date('Y-m-d');
        $stat = AdStat::where('ad_id', $adId)->where('date', $date)->find();
        if ($stat) {
            $stat->impression++;
            $stat->save();
        } else {
            AdStat::create([
                'ad_id' => $adId,
                'date' => $date,
                'impression' => 1,
            ]);
        }
        return json_success('ok');
    }

    public function click()
    {
        $adId = $this->request->post('ad_id');
        if (!$adId) return json_error('参数错误');
        $date = date('Y-m-d');
        $stat = AdStat::where('ad_id', $adId)->where('date', $date)->find();
        if ($stat) {
            $stat->click++;
            $stat->save();
        } else {
            AdStat::create([
                'ad_id' => $adId,
                'date' => $date,
                'click' => 1,
            ]);
        }
        return json_success('ok');
    }
}