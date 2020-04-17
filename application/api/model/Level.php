<?php

namespace app\api\model;

use app\common\model\MoneyLog;
use think\Model;
use think\Db;
class Level extends Model
{
    public function level_add($name,$pid,$price)
    {
        $insert = [
            'name' => $name,
            'createtime' => time(),
            'updatetime' => time(),
            'pid' => $pid,
            'price' => $price,
        ];
        $result = DB::name('level')->insert($insert);
        return $result;
    }


    public function level1_add($name)
    {
        $insert = [
            'name' => $name,
            'createtime' => time(),
            'updatetime' => time(),

        ];
        $result = DB::name('level')->insert($insert);
        return $result;
    }

    public function level2_add($name,$price,$pid)
    {
        $insert = [
            'name' => $name,
            'createtime' => time(),
            'updatetime' => time(),
            'pid'        => $pid,
            'price'      => $price,
        ];
        $result = DB::name('level')->insert($insert);
        return $result;
    }

    public function level_save($id,$name,$price=null)
    {
        $update = [
            'name' => $name,
            'price' => $price,
            'updatetime' => time(),
        ];
        $result = DB::name('level')->where('id',$id)->update($update);
        return $result;
    }

    public function level_list()
    {
        $level = DB::name('level')->where('pid',0)->select();
        foreach($level as $key => &$value){
            $level2 = DB::name('level')->where('pid',$value['id'])->select();
            $value['level2_list'] = $level2;
        }
        
        return json_encode($level,JSON_UNESCAPED_UNICODE);
    }




}
