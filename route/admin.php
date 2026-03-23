<?php
use think\facade\Route;

Route::group('admin', function () {
    // 无需登录
    Route::post('login', 'admin/Auth/login');
    
    // 需要登录
    Route::group(function () {
        Route::get('user/info', 'admin/UserController/info');
        Route::post('logout', 'admin/Auth/logout');
        Route::get('index/stat', 'admin/Index/stat');
        Route::get('index/todo', 'admin/Index/todo');
        //资源管理
        Route::get('resource/category', 'admin/ResourceController/category');
        Route::post('resource/category/save', 'admin/ResourceController/saveCategory');
        Route::post('resource/category/delete', 'admin/ResourceController/deleteCategory');
        Route::get('resource/lists', 'admin/ResourceController/lists');
        Route::post('resource/save', 'admin/ResourceController/save');
        Route::post('resource/delete', 'admin/ResourceController/delete');
        Route::post('resource/batch', 'admin/ResourceController/batch');
        Route::post('resource/move', 'admin/ResourceController/move');
        Route::post('resource/upload', 'admin/ResourceController/upload');
        Route::get('resource/material', 'admin/ResourceController/material');
        Route::post('resource/deleteMaterial', 'admin/ResourceController/deleteMaterial');
        //会员管理
        Route::get('member/level', 'admin/Member/level');
        Route::post('member/level/save', 'admin/Member/saveLevel');
        Route::post('member/level/delete', 'admin/Member/deleteLevel');
        Route::get('member/order', 'admin/Member/order');
        Route::post('member/adjust', 'admin/Member/adjust');
        //用户管理
        Route::get('user/lists', 'admin/UserController/lists');
        Route::get('user/detail', 'admin/UserController/detail');
        Route::post('user/add', 'admin/UserController/add');
        Route::post('user/save', 'admin/UserController/save');
        Route::post('user/disable', 'admin/UserController/disable');
        Route::post('user/balance', 'admin/UserController/balance');
        Route::post('user/points', 'admin/UserController/points');
        Route::post('user/reset_password', 'admin/UserController/resetPassword');
        Route::get('user/download_records', 'admin/UserController/downloadRecords');
        Route::get('user/point_records', 'admin/UserController/pointRecords');
        Route::get('user/invite_records', 'admin/UserController/inviteRecords');
        //积分管理
        Route::get('point/rule', 'admin/PointController/rule');
        Route::post('point/rule/save', 'admin/PointController/saveRule');
        Route::get('point/record', 'admin/PointController/record');
        Route::post('point/expire_notice', 'admin/PointController/expireNotice');
        //邀请管理
        Route::get('invite/setting', 'admin/Invite/setting');
        Route::post('invite/setting/save', 'admin/Invite/saveSetting');
        Route::get('invite/record', 'admin/Invite/record');
        Route::post('invite/reward', 'admin/Invite/reward');
        //商城管理
        Route::get('mall/category', 'admin/Mall/category');
        Route::post('mall/category/save', 'admin/Mall/saveCategory');
        Route::post('mall/category/delete', 'admin/Mall/deleteCategory');
        Route::get('mall/goods', 'admin/Mall/goods');
        Route::post('mall/goods/save', 'admin/Mall/saveGoods');
        Route::post('mall/goods/delete', 'admin/Mall/deleteGoods');
        Route::post('mall/goods/batch', 'admin/Mall/batchGoods');
        //订单管理
        Route::get('order/resource', 'admin/Order/resource');
        Route::get('order/mall', 'admin/Order/mall');
        Route::post('order/ship', 'admin/Order/ship');
        Route::post('order/refund', 'admin/Order/refund');
        Route::post('order/export', 'admin/Order/export');
        //广告管理
        Route::get('ad/config', 'admin/Ad/config');
        Route::post('ad/config/save', 'admin/Ad/saveConfig');
        Route::get('ad/stats', 'admin/Ad/stats');
        //分销管理
        Route::get('distributor/setting', 'admin/DistributorController/setting');
        Route::post('distributor/setting/save', 'admin/DistributorController/saveSetting');
        Route::get('distributor/apply', 'admin/DistributorController/apply');
        Route::post('distributor/audit', 'admin/DistributorController/audit');
        Route::get('distributor/order', 'admin/DistributorController/order');
        Route::get('distributor/withdraw', 'admin/DistributorController/withdraw');
        Route::post('distributor/withdraw_audit', 'admin/DistributorController/withdrawAudit');
        Route::post('distributor/disable', 'admin/DistributorController/disable');
        //站内信管理
        Route::get('message/template', 'admin/Message/template');
        Route::post('message/template/save', 'admin/Message/saveTemplate');
        Route::post('message/template/delete', 'admin/Message/deleteTemplate');
        Route::get('message/config', 'admin/Message/config');
        Route::post('message/config/save', 'admin/Message/saveConfig');
        Route::get('message/log', 'admin/Message/log');
        Route::post('message/send', 'admin/Message/send');
        //系统设置
        Route::get('setting/basic', 'admin/Setting/basic');
        Route::post('setting/basic/save', 'admin/Setting/saveBasic');
        Route::get('setting/payment', 'admin/Setting/payment');
        Route::post('setting/payment/save', 'admin/Setting/savePayment');
        Route::get('setting/security', 'admin/Setting/security');
        Route::post('setting/security/save', 'admin/Setting/saveSecurity');
        Route::post('setting/upload', 'admin/Setting/upload');
        //投诉管理
        Route::get('complaint/lists', 'admin/Complaint/lists');
        Route::post('complaint/handle', 'admin/Complaint/handle');
        Route::get('complaint/stat', 'admin/Complaint/stat');
        //数据导出
        Route::post('stat/export', 'admin/Stat/export');
        //素材库管理
    Route::get('material/folder', 'admin/MaterialController/folder');  // 查文件夹
    Route::post('material/createFolder', 'admin/MaterialController/createFolder'); // 新建文件夹
    Route::post('material/folder/renameFolder', 'admin/MaterialController/renameFolder'); // 文件夹重命名
    Route::post('material/folder/deleteFolder', 'admin/MaterialController/deleteFolder'); // 文件夹删除
    Route::get('material/list', 'admin/MaterialController/list'); // 查素材
    Route::post('material/upload', 'admin/MaterialController/upload'); // 上传
    Route::post('material/rename', 'admin/MaterialController/rename'); // 重命名
    Route::post('material/move', 'admin/MaterialController/move');
    Route::post('material/delete', 'admin/MaterialController/delete'); // 删除
        
    })->middleware(\app\middleware\AdminAuth::class);
});


