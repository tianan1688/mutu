<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\PointRecord;
use app\model\User;

class Point extends BaseController
{
    public function sign()
    {
        $user = $this->request->user;
        $today = strtotime(date('Y-m-d'));
        $hasSigned = PointRecord::where('user_id', $user->id)
            ->where('type', 'sign')
            ->where('create_time', '>=', $today)
            ->count();
        if ($hasSigned) {
            return json_error('今日已签到');
        }

        $points = get_config('point_sign', 5);
        $expireDays = get_config('point_expire_days', 30);
        $expireTime = $expireDays > 0 ? time() + $expireDays * 86400 : 0;

        $user->points += $points;
        $user->save();

        PointRecord::create([
            'user_id' => $user->id,
            'type' => 'sign',
            'points' => $points,
            'balance' => $user->points,
            'remark' => '每日签到',
            'expire_time' => $expireTime,
            'create_time' => time(),
        ]);

        return json_success('签到成功', ['points' => $points]);
    }
}