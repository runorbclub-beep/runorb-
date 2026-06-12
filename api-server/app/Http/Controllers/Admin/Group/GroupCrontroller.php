<?php
namespace App\Http\Controllers\Admin\Group;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\UserGroup;
use App\Models\UserGroupAssociated;
use App\Models\UsrUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class GroupCrontroller extends Controller
{

    /**
     * @author pengjl
     * @time 2021/5/11 15:38
     * @abstract _查询用户团队列表
     */
    public function postGroupList(){
        $_data = request()->input();

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;

        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        $_arrOfUserGroupQuery = UserGroup::where([
            "status"=>1,
        ]);

        if($_search!=''){
            $_arrOfUserGroupQuery = $_arrOfUserGroupQuery->where(function($query) use ($_search){
                $query->where("group_title","like",'%'.$_search."%")->orwhere("group_num","like",'%'.$_search."%");
            });
        }

        $_arrOfUserGroupQuery = $_arrOfUserGroupQuery->select(
            "user_group_id","status","group_title","group_logo","is_official","official_description","group_num","group_user_num"
        );

        $_arrOfUserGroupCount = $_arrOfUserGroupQuery->count();
        $_arrOfUserGroup = $_arrOfUserGroupQuery->skip($_offset)->take($_limit)->get();

        $_arrOfUserGroupKey = array();
        $_arrOfUserGroupId = array();
        foreach ($_arrOfUserGroup as $value){
            $value["group_logo"] = StaticDataController::$_server_url."/".$value["group_logo"];
            $_arrOfUserGroupKey[$value["user_group_id"]] = $value;
            array_push($_arrOfUserGroupId,$value["user_group_id"]);
        }

        $_arrOfUserGroupAssociatedCount = DB::table("user_group_associated")->where("status","=","1")->whereIn("user_group_id",$_arrOfUserGroupId)->selectRaw("count(user_id) as count,user_group_id")->groupBy("user_group_id")->get();

        foreach($_arrOfUserGroupAssociatedCount as $value){
            $_arrOfUserGroupKey[$value->user_group_id]["group_user_num"] = $value->count;
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>$_arrOfUserGroupCount,
                "list"=>array_values($_arrOfUserGroupKey)
            )
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/11 16:00
     * @abstract _团队编辑
     */
    public function postGroupAdd(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["group_title"]) || !isset($_data["group_logo"]) || !isset($_data["group_num"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_is_official = isset($_data["is_official"])?$_data["is_official"]:0;
        $_official_description = isset($_data["official_description"])?$_data["official_description"]:"";

        $_group_logo = $_data["group_logo"];
        $_base_url = StaticDataController::$_server_url."/";
        $_group_logo = str_replace($_base_url,"",$_group_logo);

        $_arrOfUserGroupData = array(
            "group_title"=>$_data["group_title"],
            "group_logo"=>$_group_logo,
            "group_num"=>$_data["group_num"],
            "official_description"=>$_official_description,
            "is_official"=>$_is_official,
        );

//        查询团队编号
        $_arrOfUserGroup = UserGroup::where([
            "status"=>1,
            "group_num"=>$_data["group_num"]
        ])->select("user_group_id")->get();

//        存在用户团队ID
        if(isset($_data["user_group_id"])){
            $_arrOfUserGroupData["updated_uid"] = $_admin_user["admin_user_id"];


            if(count($_arrOfUserGroup)==1 && $_arrOfUserGroup[0]["user_group_id"]!=$_data["user_group_id"]){
                return array(
                    "code"=>0,
                    "msg"=>"当前团队号已被占用"
                );
            }

            UserGroup::where([
                "user_group_id"=>$_data["user_group_id"]
            ])->update($_arrOfUserGroupData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功"
            );

        }else{
            if(count($_arrOfUserGroup)==1){
                return array(
                    "code"=>0,
                    "msg"=>"当前团队号已被占用"
                );
            }

            $_arrOfUserGroupData["created_uid"] = $_admin_user["admin_user_id"];
            $_arrOfUserGroupData["status"] = 1;

            $_sno = new Snowflake(StaticDataController::$_workId);

            $_arrOfUserGroupData["user_group_id"] = $_sno->nextId();
            UserGroup::create($_arrOfUserGroupData);

            return array(
                "code"=>1,
                "msg"=>"创建成功"
            );
        }

    }


    /**
     * @author pengjl
     * @time 2021/5/11 19:07
     * @abstract _管理后台操作用户加入团队
     */
    public function postGroupJoinUser(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["user_id"]) || !isset($_data["user_group_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfUserGroupAssociated = UserGroupAssociated::where([
            "status"=>1,
            "user_id"=>$_data["user_id"],
            "user_group_id"=>$_data["user_group_id"],
        ])->select("user_group_associated_id")->get();

        if(count($_arrOfUserGroupAssociated)>0){
            return array(
                "code"=>0,
                "msg"=>"用户已加入该团队"
            );
        }

        $_sno = new Snowflake(StaticDataController::$_workId);
        $_arrOfUserGroupAssociatedData = array(
            "user_group_associated_id"=>$_sno->nextId(),
            "user_id"=>$_data["user_id"],
            "user_group_id"=>$_data["user_group_id"],
            "created_uid"=>$_admin_user["admin_user_id"],
            "status"=>1,
            "join_description"=>"后台操作加入"
        );

        UserGroupAssociated::create($_arrOfUserGroupAssociatedData);

        return array(
            "code"=>1,
            "msg"=>"操作成功"
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/11 19:09
     * @abstract _后台操作团队删除用户
     */
    public function postGroupDeleteUser(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["user_id"]) || !isset($_data["user_group_id"])){
            return SystemErrorController::paramtersError($_language);
        }


        UserGroupAssociated::where([
            "status"=>1,
            "user_id"=>$_data["user_id"],
            "user_group_id"=>$_data["user_group_id"],
        ])->update([
            "status"=>0,
            "updated_uid"=>$_admin_user["admin_user_id"]
        ]);


        return array(
            "code"=>1,
            "msg"=>"操作成功"
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/11 19:43
     * @abstract _查询团队下的用户列表，用户加入的团队列表公用
     */
    public function postGroupUserList(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

//        两个参数不能同时存在
        if((!isset($_data["user_id"]) && !isset($_data["user_group_id"])) || isset($_data["user_id"]) && isset($_data["user_group_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;

        if(isset($_data["user_id"]) && !isset($_data["user_group_id"])){

            $_arrOfGroupUserQuery = UsrUser::where([
                "usr_user.status"=>1,
                "user_group.status"=>1,
                "user_group_associated.status"=>1,
                "usr_user.user_id"=>$_data["user_id"],
            ])->join("user_group_associated",function ($join){
                $join->on("usr_user.user_id","=","user_group_associated.user_id");
            })->join("user_group",function ($join){
                $join->on("user_group_associated.user_group_id","=","user_group.user_group_id");
            });

            if($_search != ""){
                $_arrOfGroupUserQuery = $_arrOfGroupUserQuery->where(function($query) use ($_search){
                    $query->where("user_group.group_title","like",'%'.$_search."%")->orwhere("user_group.group_num","like",'%'.$_search."%");
                });
            }

            $_arrOfGroupUserQuery = $_arrOfGroupUserQuery->select(
                "usr_user.user_id","usr_user.user_name","usr_user.user_img","usr_user.self_description","user_group.group_title"
                ,"user_group.group_logo","user_group.is_official","user_group.user_group_id","user_group.group_num"
            );

            $_arrOfGroupUserCount = $_arrOfGroupUserQuery->count();
            $_arrOfGroupUser = $_arrOfGroupUserQuery->skip($_offset)->take($_limit)->get();

        }else{

            $_arrOfGroupUserQuery = UserGroup::where([
                "usr_user.status"=>1,
                "user_group.status"=>1,
                "user_group_associated.status"=>1,
                "user_group.user_group_id"=>$_data["user_group_id"],
            ])->join("user_group_associated",function ($join){
                $join->on("user_group.user_group_id","=","user_group_associated.user_group_id");
            })->join("usr_user",function ($join){
                $join->on("user_group_associated.user_id","=","usr_user.user_id");
            });

            if($_search != ""){
                $_arrOfGroupUserQuery = $_arrOfGroupUserQuery->where(function($query) use ($_search){
                    $query->where("usr_user.user_name","like",'%'.$_search."%")->orwhere("usr_user.phone","like",'%'.$_search."%");
                });
            }

            $_arrOfGroupUserQuery = $_arrOfGroupUserQuery->select(
                "usr_user.user_id","usr_user.user_name","usr_user.user_img","usr_user.self_description","user_group.group_title"
                ,"user_group.group_logo","user_group.is_official","user_group.user_group_id","user_group.group_num"
            );

            $_arrOfGroupUserCount = $_arrOfGroupUserQuery->count();
            $_arrOfGroupUser = $_arrOfGroupUserQuery->skip($_offset)->take($_limit)->get();

        }


        foreach ($_arrOfGroupUser as $key=>$value){
            $value["user_img"] = StaticDataController::$_server_url."/".$value["user_img"];
            $value["group_logo"] = StaticDataController::$_server_url."/".$value["group_logo"];

            $_arrOfGroupUser[$key] = $value;
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>$_arrOfGroupUserCount,
                "list"=>$_arrOfGroupUser
            )
        );
    }

}
