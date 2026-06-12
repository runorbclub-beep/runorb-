<?php

namespace App\Exports\Shake;

use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\QiyeShakeUser;
use App\Models\UsrUser;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class QiyeShakeUserImport implements ToCollection, WithCalculatedFormulas
{

    protected $id;
    protected $userId;

    public function __construct($id, $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
    }


    /**
     * 使用 ToCollection
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $data = $rows ? $rows->toArray() : [];

        $this->saveData($data);
        $data = null;
    }


    /**
     * @param $data
     * @param $keys
     */
    public function saveData($data)
    {
        unset($data[0]);
        $phoneList = array_column($data, 2);
        $userIdList = $this->getUserIds($phoneList);

        $list = [];
        $_sno = new Snowflake(StaticDataController::$_workId);
        foreach ($data as $da) {
            $name = $da[0] ?? '';
            $department = $da[1] ?? '';
            $phone = $da[2] ?? '';
            $sex = $da[3] ?? '';

            $list[] = [
                'qiye_shake_user_id' => $_sno->nextId(),
                'sys_qiye_shake_id' => $this->id,
                'created_time' => time(),
                'updated_time' => time(),
                'created_uid' => $this->userId,
                'updated_uid' => $this->userId,
                'status' => 1,
                'department' => $department,
                'name' => $name,
                'phone' => $phone,
                'sex' => $sex,
                'user_id' => $userIdList[$phone] ?? ''
            ];
        }

        if ($list) {
            QiyeShakeUser::insert($list);
        }
    }


    /**
     * 获取用户ID
     *
     * @param $phoneList
     * @return array
     */
    public function getUserIds($phoneList)
    {
        $res = UsrUser::whereIn('phone', $phoneList)->get(['user_id', 'phone']);
        $res = $res ? $res->toArray() : [];
        $res = array_column($res, 'user_id', 'phone');

        return $res;
    }


    public function createData($rows)
    {
        //todo
    }
}
