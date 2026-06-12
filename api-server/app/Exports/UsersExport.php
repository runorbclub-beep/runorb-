<?php

namespace App\Exports;

use App\Model\Salary;
use App\Model\SysUser;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection,WithHeadings
{

    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

//        导出屏蔽丹尼斯业务系统
        $_channel_id = array(29);
        $_arrOfPromotersQuery = SysUser::where([
            "sys_user.status"=>1,
            "store.status"=>1,
            "channel.status"=>1,
            "user_store_associated.status"=>1,
            "area.status"=>1,
            "city.status"=>1,
        ])->join("user_store_associated",function($join){
            $join->on("user_store_associated.sys_user_id","=","sys_user.sys_user_id");
        })->join("store",function($join){
            $join->on("user_store_associated.store_id","=","store.store_id");
        })->join("city",function($join){
            $join->on("city.city_id","=","store.cit_city_id");
        })->join("city as area",function($join){
            $join->on("area.city_id","=","store.city_id");
        })->join("user_role",function($join){
            $join->on("sys_user.user_role_id","=","user_role.user_role_id");
        })->join("channel",function($join){
            $join->on("channel.channel_id","=","store.channel_id");
        })->join("channel_city_store",function($join){
            $join->on("channel_city_store.store_id","=","store.store_id");
        })->join("admin_user",function($join){
            $join->on("channel_city_store.admin_user_id","=","admin_user.admin_user_id");
        })->whereNotIn("store.channel_id",$_channel_id);

//        ->whereIn("city.city_id",$_city_id)->whereIn("store.channel_id",$_channel_id)

        $_arrOfPromotersQuery = $_arrOfPromotersQuery->select(
            "sys_user.real_name","sys_user.phone","user_role.role_name","store.store_number","store.store_name","channel.channel_name","city.city_name"
            ,"area.city_name as area_name","admin_user.real_name as admin_real_name","sys_user.created_at"
        )->distinct("sys_user.sys_user_id")->orderBy("channel.channel_id","DESC");

        $_arrOfPromoters = $_arrOfPromotersQuery->get();

        $_arrOfSysUserId = array();
        $_arrOfPromotersKey = array();
        foreach($_arrOfPromoters as $key=>$value){
            $_sys_user_id = $value["sys_user_id"];
            $value["salary"] = 0;
            unset($value["created_at"]);
            $_created_time = date("Y-m-d H:i",$value["created_at"]);
            $value["created_at"] = $_created_time;

            $_arrOfPromotersKey[$_sys_user_id] = $value;
            array_push($_arrOfSysUserId,$_sys_user_id);
        }

        $_arrOfSalary = Salary::where([
            "status"=>1,
            "is_send"=>1,
            "sys_user_id"=>$_arrOfSysUserId
        ])->select("salary_num","sys_user_id")->get();

        foreach($_arrOfSalary as $value){
            $_arrOfPromotersKey[$value["sys_user_id"]]["salary"] = $_arrOfPromotersKey[$value["sys_user_id"]]["salary"]+$value["salary_num"];
        }

        return $_arrOfPromoters;
    }

    public function headings(): array
    {
        return array(
            "real_name"=>"姓名",
            "city"=>"电话",
            "role_name"=>"角色",
            "store_number"=>"编码",
            "store_name"=>"门店",
            "channel_name"=>"系统",
            "phone"=>"城市",
            "area_name"=>"区域",
            "admin_real_name"=>"区域经理",
            "created_time"=>"注册时间"
        );
    }
}
