<?php
/**
 * Created by PhpStorm.
 * User: ns210
 * Date: 2020/5/16
 * Time: 14:33
 */

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromArray;

class testExport implements FromArray
{
    public function array():array
    {
        return [
            [1, 2, 3],
            [4, 5, 6]
        ];
    }
}