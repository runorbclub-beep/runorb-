<?php

namespace App\Exports\UsrUser;

use App\Models\UsrUser;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class UsrUserExport implements FromView
{
    /**
     * 导出用户
     * @return View
     * User: zxw
     * Date: 2021/12/22 18:12
     */
    public function view(): View
    {
        return view('web.usr_user.usr_user', [
            'list' => UsrUser::where('status',1)->where('phone','>',0)->where('sys_user_type_id',1809649560981504)->get()
        ]);
    }
}
