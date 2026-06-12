<?php

namespace App\Exports\UserPlay;

use App\Models\UsrUser;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsrUserExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return UsrUser::where('user_id',100019056605663232)->first();
    }
}
