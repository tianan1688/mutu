<?php
use think\facade\Route;

// 用户端API路由
Route::group('api', function () {
    // 无需登录
    Route::post('login', 'api/Auth/login');
    Route::post('register', 'api/Auth/register');
    Route::post('forget', 'api/Auth/forget');
    Route::get('captcha', 'api/Auth/captcha');
    Route::get('index', 'api/Index/index'); // 首页数据
    Route::get('resource/category', 'api/Resource/category');
    Route::get('resource/lists', 'api/Resource/lists');
    Route::get('resource/detail', 'api/Resource/detail');
    Route::get('member/levels', 'api/Member/levels');

    // 需要登录验证
    Route::group(function () {
        Route::get('user/info', 'api/User/info');
        Route::post('user/update', 'api/User/update');
        Route::get('user/balance_log', 'api/User/balanceLog');
        Route::get('user/point_log', 'api/User/pointLog');
        Route::post('user/sign', 'api/Point/sign'); // 签到

        Route::post('resource/download', 'api/Resource/download'); // 获取下载链接
        Route::post('member/buy', 'api/Member/buy'); // 购买会员

        Route::get('mall/goods', 'api/Mall/goods');
        Route::get('mall/detail', 'api/Mall/detail');
        Route::post('mall/order/create', 'api/Mall/createOrder');
        Route::get('order/list', 'api/Order/lists');
        Route::get('order/detail', 'api/Order/detail');
        Route::post('order/refund', 'api/Order/refund');
        Route::get('order/logistics', 'api/Order/logistics');

        Route::post('distributor/apply', 'api/Distributor/apply');
        Route::get('distributor/center', 'api/Distributor/center');
        Route::get('distributor/orders', 'api/Distributor/orders');
        Route::post('distributor/withdraw', 'api/Distributor/withdraw');
        Route::get('distributor/withdraw_record', 'api/Distributor/withdrawRecord');

        Route::get('message/list', 'api/Message/lists');
        Route::get('message/detail', 'api/Message/detail');
        Route::post('message/read', 'api/Message/read');

        Route::post('complaint/add', 'api/Complaint/add');
        Route::get('complaint/list', 'api/Complaint/lists');

        Route::post('ad/impression', 'api/Ad/impression'); // 广告曝光上报
        Route::post('ad/click', 'api/Ad/click'); // 广告点击上报
    })->middleware(\app\middleware\Auth::class);
});