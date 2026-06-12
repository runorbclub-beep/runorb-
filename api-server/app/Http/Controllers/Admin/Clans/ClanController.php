<?php


namespace App\Http\Controllers\Admin\Clans;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\UserClan;
use App\Models\UserClanMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 战队管理
 * Class ClanController
 * @package App\Http\Controllers\Admin\Clans
 * User: zxw
 * Date: 2021/12/17 09:31
 */
class ClanController extends Controller
{
    /**
     * 获取战队待审核审核列表
     * @param Request $request
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/17 09:32
     * @throws BusinessException
     */
    public function getPendingReviewList(Request $request): JsonResponse
    {
        $data = $request->all();
//        $tokenKeys = "admin_user_token:".$request->header('token');
//        Redis::select(1);
//        $token = json_decode(Redis::get($tokenKeys),true);

        $data['limit'] = $data['limit'] ?? 15;
        $list = UserClan::leftJoin('user_clan_members','user_clan_members.user_clan_id','=','user_clans.id')
            ->leftJoin('usr_user','user_clan_members.user_id','=','usr_user.user_id')
            ->selectRaw("user_clans.id,user_clans.title,CONCAT('".StaticDataController::$_server_url . "/',user_clans.clan_avatar) AS clan_avatar,user_clans.address,user_clans.introduction,user_clans.telephone,user_clans.created_at,user_clan_members.remark,user_clan_members.user_id,sys_sex_id,user_name,CONCAT('".StaticDataController::$_server_url . "/',user_img) AS user_img,usr_user.status AS user_status")
            ->where([
                'user_clans.status' => 0,
                'user_clan_members.status' => 1,
                'user_clan_members.is_captain' => 1,
            ])->withCasts([
                'created_at' => 'datetime:Y-m-d H:i:s'
            ])->paginate($data['limit']);

        return $this->success(data_list_format($list));
    }

    /**
     * 审核待审核战队
     * @param Request $request
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/17 11:26
     * @throws BusinessException
     */
    public function postPendingReview(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['user_clan_id'])) return $this->error(ErrorCode::SEVER_ERROR,'战队 user_clan_id 不能为空！',false);
        if (empty($data['status'])) return $this->error(ErrorCode::SEVER_ERROR,'审核状态 status 不能为空！',false);

        //获取数据信息
        $list = UserClan::leftJoin('user_clan_members','user_clan_members.user_clan_id','=','user_clans.id')
            ->leftJoin('usr_user','user_clan_members.user_id','=','usr_user.user_id')
            ->selectRaw("user_clans.id,user_clans.title,CONCAT('".StaticDataController::$_server_url . "/',user_clans.clan_avatar) AS clan_avatar,user_clans.address,user_clans.introduction,user_clans.telephone,user_clans.created_at,user_clan_members.remark,user_clan_members.user_id,sys_sex_id,user_name,CONCAT('".StaticDataController::$_server_url . "/',user_img) AS user_img,usr_user.status AS user_status")
            ->where([
                'user_clans.status' => 0,
                'user_clan_members.status' => 1,
                'user_clans.id' => $data['user_clan_id'],
                'user_clan_members.is_captain' => 1,
            ])->withCasts([
                'created_at' => 'datetime:Y-m-d H:i:s'
            ])->first();

        if (empty($list)) return $this->error(ErrorCode::SEVER_ERROR,'审核的数据不存在！',false);

        if ($data['status'] == 1){//通过
            $messages = "审核成功！";
            DB::beginTransaction();
            try {
                UserClan::where('id',$list['id'])->update(['status' => 1]);
                UserClanMember::where('user_clan_id',$list['id'])->update(['status' => 2]);
                DB::commit();
            }catch (\Throwable $ex){
                DB::rollBack();
                throw new BusinessException(0,'审核失败！',$ex);
            }
        }else{
            $messages = "拒绝成功！";
        }
        return $this->success(true,$messages,ErrorCode::SEVER_SUCCESS);
    }

}
