<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\SysSex;
use App\Models\SysUserType;

class SysUserTypeController extends Controller
{


    /**
     * @abstract 查询用户类型字典
     * @return array
     */
    public function postSysUserTypeList(){

        $_arrOfSysUserTypeList = SysUserType::where([
            "status"=>1,
        ])->select("sys_user_type_id as value","user_type_name as label","user_type_code")->get();

        $_arrOfSysUserTypeValue = array();
        foreach ($_arrOfSysUserTypeList as $value){
            array_push($_arrOfSysUserTypeValue,$value["value"]);
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "list"=>$_arrOfSysUserTypeList,
                "value"=>$_arrOfSysUserTypeValue
            )
        );

    }

    /**
     * @abstract 查询用户性别字典
     * @return array
     */
    public function postSysSexList(){

        $_arrOfSysSexList = SysSex::where([
            "status"=>1,
        ])->select("sys_sex_id as value","sex_name as label","sex_code")->get();

        $_arrOfSysSexValue = array();
        foreach ($_arrOfSysSexList as $value){
            array_push($_arrOfSysSexValue,$value["value"]);
        }
        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "list"=>$_arrOfSysSexList,
                "value"=>$_arrOfSysSexValue
            )
        );
    }
}
