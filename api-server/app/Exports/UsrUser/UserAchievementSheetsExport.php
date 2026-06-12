<?php

namespace App\Exports\UsrUser;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserAchievementSheetsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];
        $titles = ['max_speed', 'onemin', 'exponent', 'marathon'];
        foreach ($titles as $k => $v) {
            $sheets[] = new UserAchievementExport($v);
        }
        return $sheets;
    }
}
