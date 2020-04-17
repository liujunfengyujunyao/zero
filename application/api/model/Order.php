<?php

namespace app\api\model;

use app\common\model\MoneyLog;
use think\Model;
use think\Db;
class Order extends Model
{
        public function client_number()
        {
            $result = DB::name('user')->count()-2;
            return $result;
        }

        public function order_amount()
        {
            $result = DB::name('order')->where('status',1)->sum('amount');
            return $result;
        }

        public function order_number()
        {
            $result = DB::name('order')->where('status',1)->count();
            return $result;
        }

        public function order_list()
        {
            $where = [
                'status' => 1,//已支付
                'is_end' => 0//未完成
            ];
            $data = DB::name('order')->field('id,amount,paytime,level_id,tracking_code')->where($where)->select();
            if($data){
                foreach($data as $key => &$value){
                    $level2 = DB::name('level')->where('id',$value['level_id'])->find();
                    $value['level2'] = $level2['name'];
                    $value['level1'] = DB::name('level')->where('id',$level2['pid'])->value('name');
                }
            }
            return $data;

        }

        public function tracking_code($id,$tracking_code)
        {
            $result = DB::name('order')->where('id',$id)->update(['tracking_code',$tracking_code]);
            return $result;
        }




}
