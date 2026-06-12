<?php


namespace App\Utils\Traits;


use App\Constants\ErrorCode;
use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    /**
     * 状态响应：请求成功
     * @param null $data 返回结果集
     * @param null $msg 返回信息
     * @param int $code 返回状态码
     * @return JsonResponse
     * User: zxw
     * Date: 2021/9/14 11:38
     */
    public function success($data = null, $msg = null, int $code = ErrorCode::SEVER_SUCCESS): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg' => is_null($msg) ? 'success' : $msg,
            'data' => $data,
        ]);
    }

    /**
     * 状态响应：请求失败
     * @param string $code 返回状态码
     * @param null $msg 返回信息
     * @param null $data 返回结果集
     * @return JsonResponse
     * User: zxw
     * Date: 2021/9/14 11:41
     */
    public function error($code = ErrorCode::SEVER_ERROR, $msg = null, $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg' => is_null($msg) ? 'error' : trans($msg),
            'data' => $data,
        ]);
    }

    /**
     * 状态响应：直接返回JSON
     * @param null $data
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/22 11:55
     */
    public function json($data = null): JsonResponse
    {
        return response()->json($data);
    }
}
