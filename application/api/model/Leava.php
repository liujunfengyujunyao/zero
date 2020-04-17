<?php

namespace app\api\model;

use app\common\model\MoneyLog;
use think\Model;
use think\Db;
class Leava extends Model
{
    public function leava_list()
    {
        $data = DB::name('leava')->order('create_time','desc')->select();
        return json_encode($data,JSON_UNESCAPED_UNICODE);
    }

    public function update_status($id)
    {
        $status = DB::name('leava')->where('id',$id)->value('status');
        if($status == 0){
            $update = ['status'=>1];
        }else{
            $update = ['status'=>0];
        }
        return DB::name('leava')->where('id',$id)->update($update);

    }


}
