<?php
// app/common/Response.php
namespace app\common;

class Response
{
    // 成功返回
    public static function success($data = [], $msg = '操作成功')
    {
        return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
    }

    // 失败返回
    public static function error($msg = '操作失败', $data = [])
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }
}