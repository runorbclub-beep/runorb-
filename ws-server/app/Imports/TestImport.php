<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;

class TestImport implements ToArray
{
    /**
    * @param Collection $collection
    */
    public function array(array $array)
    {
        //
        return $array;
    }
}
