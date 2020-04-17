<?php

namespace app\api\controller;
use think\Db;
use app\common\controller\Api;
use app\api\model\Level;
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin');
/**
 * 示例接口
 */
class Audit extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['level_list'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
        $this->params = request()->param();
        $this->user = $this->auth->getUser();

    }


    //服务管理列表
    public function level_list()
    {
        return model('level')->level_list();
    }

    public function level_add()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        if(!empty($this->params['pid'])  || !empty($this->params['price'])){
            $pid = $this->params['pid'];
            $price = $this->params['price'];
        }else{
            $pid = 0;
            $price = null;
        }
        $result = model('level')->level_add($this->params['name'],$pid,$price);
        if($result !== false){
            $this->success('添加完成');
        }else{
            $this->error('网络错误');
        }
    }
    //新增一级服务类
    public function level1_add()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        $result = model('level')->level1_add($this->params['name']);
        if($result !== false){
            $this->success('添加完成');
        }else{
            $this->error('网络错误');
        }
    }

    //新增二级分类
    public function level2_add()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        $result = model('level')->level2_add($this->params['name'],$this->params['price'],$this->params['pid']);
        if($result !== false){
            $this->success('添加完成');
        }else{
            $this->error('网络错误');
        }
    }


    //修改一级分类
    public function level1_save()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        $result = model('level')->level_save($this->params['id'],$this->params['name']);
        if($result !== false){
            $this->success('修改完成');
        }else{
            $this->error('网络错误');
        }
    }

    //修改二级分类
    public function level2_save()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        $result = model('level')->level_save($this->params['id'],$this->params['name'],$this->params['price']);
        if($result !== false){
            $this->success('修改完成');
        }else{
            $this->error('网络错误');
        }
    }

    public function level_save()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        if(!empty($this->params['price'])){
            $price = $this->params['price'];
        }else{
            $price = null;
        }
        $result = model('level')->level_save($this->params['id'],$this->params['name'],$price);
        if($result !== false){
            $this->success('修改完成');
        }else{
            $this->error('网络错误');
        }
    }

    //留言管理列表
    public function leava_list()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        return model('leava')->leava_list();
    }

    //变更回访状态
    public function update_status()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        $result =  model('leava')->update_status($this->params['id']);
        if($result !== false){
            $this->success('修改成功');
        }else{
            $this->error('网络错误');
        }
    }

    public function order_list()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        $result = [
            'client_number' => model('order')->client_number(),//用户数
            'order_amount' => model('order')->order_amount(),//总订单金额
            'order_number' => model('order')->order_number(),
            'order_list'   => model('order')->order_list(),//未完成订单列表
        ];
        return json_encode($result,JSON_UNESCAPED_UNICODE);
    }

    //修改物流单号
    public function tracking_code()
    {
        if($this->user['group_id'] != 1){
            httpStatus(403);
        }
        $result = model('order')->tracking_code($this->params['id'],$this->params['tracking_code']);
        if($result !== false){
            $this->success('完成');
        }else{
            $this->error('网络错误');
        }
    }












}
