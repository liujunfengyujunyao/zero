<?php

namespace app\api\controller;
use think\Db;
use app\common\controller\Api;
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin');
/**
 * 示例接口
 */
class Client extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['leava_post','changepassword','notify'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    public function _initialize()
        {
            parent::_initialize();
            $this->params = request()->param();
            $this->user = $this->auth->getUser();
        }


    //申报
    public function leava_post()
    {
        $insert = [
            'name' => $this->params['name'],
            'mobile' => $this->params['mobile'],
            'create_time' => time(),
        ];
        $result = DB::name('leava')->insert($insert);
        if($result){
            $this->success('申请成功');
        }else{
            $this->error('网络错误');
        }
    }

    public function changepassword()
    {

        $mobile = $this->request->request("mobile");

        $newpassword = $this->request->request("newpassword");

        if (!$newpassword) {
            $this->error(__('Invalid parameters'));
        }
        $user = DB::name('user')->where('mobile', $mobile)->find();

        //模拟一次登录
        $this->auth->direct($user['id']);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    //创建订单
    public function pay()
    {
        $price = DB::name('level')->where('id',$this->params['level_id'])->value('price');//价格
        if($this->params['pm'] == 'wx'){
            $data['pm'] = 2;
        }else{
            $data['pm'] = 1;
        }
        $post = [
            'type' => $data['pm'],
            'user_id' => 1,//易宝的商户ID
            'amount' => floatval($price),
            'goods_name' => '收款',
            'sn' => null,
            'notify' => "http://liujunfeng.imwork.net:23930/api/client/notify",
            'goodsDesc' => null,
        ];
        $url = "http://www.wakapay.cn/index.php/api/yeepay/pay";
        $return = json_curl($url,$post);
        $arr = json_decode($return,true)['data'];
        $result = [
            'qr' => $arr['url'],
            'uid' => $arr['unique_order_id'],//服务器生成的支付唯一编码
        ];

        $insert = [
            'unique_order_id' => $result['uid'],
            'amount' => $post['amount'],
            'type' => $post['type'],//1支付宝 2微信
            'createtime' => time(),
            'user_id' => $this->user['id'],
            'level_id' => $this->params['level_id']
        ];
        DB::name('order')->insert($insert);
        $this->success($result['qr'],$result['uid']);
    }


    public function notify()
    {
//        $txt = file_get_contents('php://input');
//        file_put_contents('./uploads/notify.txt',$txt,FILE_APPEND);
//        die;
        $private_Key = "MIIEugIBADANBgkqhkiG9w0BAQEFAASCBKQwggSgAgEAAoIBAQDAVCOCDnslcJceuxavrLswc9WPU9b7yBTVadL8dPVD+Qqpd1xcFQm1FyxIZRbgEAV4MT8oSdhMYqV7bKSyt5PrT9oU5bzJytdJQwxe3eX7WYMldHNv9EHr1uJAQhgWPwqRndRoKHiCxcgy6ps10HGE8Qj0IsAyTL/Og6idcYekVlbVj9w0kotq0kPmRkda0wS8lYD6mH6qq9C36FnEWV3qVKdcO/hJ2AG9e5m75HuAU99BbfwYr0uStZcimpYLtOj0/Cn4v5B//Gthc/Cgf3LJ5FuiKmPKoxfnNoB4TB5ALRcDaovacT7SsMhXFwbfRkt2OfZVYqFtiiuyzUYefU+ZAgMBAAECgf90cn0NQbdN892Lvbr+opazv26OWTTRPVNf47LbJ/VYMnFCKgLBvfsiqeUl8A7pmsm0/BxBSHStywxmrmEJ1By7XJ2uCWtEwouW0AGtbqzQgmHlS5yZLEq9gF18iogK8CB2ChmQ9vAAPb/5FBLlgk85Lrc9Gc1EpzN61jxBF3wJAy/2AL0Q+NYpq6TOWXWoEYFnjQtStq7AaJOh4/K0RhmFvVapyXL4i7fWddWW2jZ//AzIOe5ok5VD7YdxPKXRSxCjlS5JTDVDAZ3KY72i4+oVpeqffF5XR3MdAai+66wHI3eH0QKf6Qz56wyH9yFwSzBEValeWV29SP+MOhjcqI0CgYEA8NxL2kzVh8Kdygkm9pJB3Gxd9ZUPw9oKEdWusZSKLvIs36KPY6qYB5xsF03lmZoe0HvtBLUL03J/D2BVDChHbv2pT5wxKkHU0vkw5ojRiEnMpWbvE6skndeZEA1DD6E4+RSL10siAjXoSKifHzaEu7s1Km30hWqsRBdzXir3gNMCgYEAzGrpqkFnSnQq4sepnL9v247ikjYJi80tly1tjMdkJww9exX1EOSgSMXtXgMof99GUTipFBHe8PRtX4I+yI9K/I4zxRtaYgP+gZ7BVgYe98E6ZNrGD/8LNbJfDbsBwrtYDE/Y23hRbLJOPN/+PocF5LA+uJMuIni1DDfh7MJgymMCgYB+cskHtCqt+UgpVyCzdhlJhULWuQjrwz5iGpJ5/AeHmfBg/9DTfC4QYNiGa4jMWRMwVL8cJ4gr3AJEqkg797F43YbTmqZdDu6SS+yWOuH18PiVJTMCWmkAzL04ph28yOFGMrkvr+wMyQxHiO7wzghlHmVM/yjOGjCSFtWkbF4/rQKBgDI+8VKdIvOFHGmD5GgYEjmopH6F89C+TT+EthHNjQugEZiorAVL/S4GILNkGVddHV6ni7/YKLGXky7Px/jqZ+cuWQFRGOVQ0AUybZlkhcYmY+EYeWjDKxE21/B7EBK6lAjqs4Y2y+To6xxBfrAF5mfw/mnGG6fzfaUUM19L5Bi7AoGAVI8iQ5NP0iZtCdSnQPkjKZMDifwVLwdfcaEjRYop7cfe9IYak+QPC/LQGkjKH5G8t2OAsbC9wExwM3Lhd9DKRBDlqcCPxaTD5Wxq1UDXDcARWarWOpDF7l3Gt7StAsGo9QRb8d0w9CRFLCDzxj1CKGwVz12XfrpL/OdVqtHe/EI=";
        //公钥
        $public_Key = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6p0XWjscY+gsyqKRhw9MeLsEmhFdBRhT2emOck/F1Omw38ZWhJxh9kDfs5HzFJMrVozgU+SJFDONxs8UB0wMILKRmqfLcfClG9MyCNuJkkfm0HFQv1hRGdOvZPXj3Bckuwa7FrEXBRYUhK7vJ40afumspthmse6bs6mZxNn/mALZ2X07uznOrrc2rk41Y2HftduxZw6T4EmtWuN2x4CZ8gwSyPAW5ZzZJLQ6tZDojBK4GZTAGhnn3bg5bBsBlw2+FLkCQBuDsJVsFPiGh/b6K/+zGTvWyUcu+LUj2MejYQELDO3i2vQXVDk7lVi2/TcUYefvIcssnzsfCfjaorxsuwIDAQAB";
        $source = $_REQUEST["response"];
//        $source = "PXxED22fm4OFWIWbSovSLyCVi-0CikkTK4hKbDirKLQ-_dhPOGD8Hv9Kg_HDPH3_GHuDERSJo_mftHN3dK7fRSE3bzd42ZLONXC670nfFs55aduvrn0bO_LK5-PmrYX_p5wxfuWV4pXj7T4SrgJ2rxiA0dyC9sHqtUn3kR4-G_nRNjgSveeHX2uX7ljzS7toiRJ_8JaNrwdt5CAKrOYcYo4r0NbvJI_OMKEuWyaFEM8puNqqsWko8kNgxemrVmzHlOfXmk3Ly3aeh76eDRKXrnrxHB5jUaIqGL3rvc61h2Kbc_uJIRUGmctIYmnrRZ7pLzYuDcgbyLefFirbsUN0hg%24VeH-K3__sHrYqBLXkCbBVZdinHhgPQhqpGWDMylhnqoT5gjcNaQdpfmjfdnKirVXrWq9RXrW0w7iu-MgkD8hAMivFWRyplhcC3d12Idj1quSdU_06m7bBo3qEVsQLDysi8A9SO3kSewNvXXKrE8wSd3gTLaIJN_w7wzeITK8jURALbiRff_324yPRHhfuIkNyZOddHDvby2aGrxzeINrXPRY05XvSDCO6ZWnFXC9Ml_AA-5B0MWEn1Q55KaciBTS54Uk6aWhb6XFGbJxn2249xmW-UbdtTeK3DZLvt_40_XlYTTjDgCoz8KyJtzFcrsbnJ1WzoKZY8VfClXLrG0G8hluWxiY7FFS76clCD6xK448DImVunTafRX2ndT0WGW09IvC4bscv1fqtbwQRznpIz09Lnr8yXEoWPAjKQWBSjLTqMImXrppkG_QRolW0DOEWGA0TC3k3pd0SQ9fKBu8f0fWLrt5--YksoBRIhxt9DtZFBfGJB79CqoUOq1ZNhxwURsAGeIsYTcbE_gMBmHnEZs5ftt1ZOfuRIri8xa4uwOwg-pS5cWQGRXMB2vHUxVESJOScMs9NuUczgtTqVMaXfj1L4_CEcS7NOsdWlmSCJbpHFjKAEY1yKnYTnYyf3-mkXCgFIN_XkbmFlI7qbjIZTJ4chjA401F6_75Tm4XwfK3v6o9vSyNrMfBHSwrZDNEUgC1P5V6NwcgG2U7NBNdLo7kNdN7KkjJ70vF28lPBwHiPMkl51YwJxc8yIIyzGAKFmzNOYAz-EjjviBwhwzz6dV2G7lXkyDcD6dVxUrxRpQ2nFAxFC8vp6jTbJ1fjsrspzChI58D0_VTJ1EACP2zA1u95bEfS6raLkpDDd4RINA396fUYRBBpCS-vBXijksIK-vd7oPRjp8PvU5PJ2HruQRLTKjpqAn5l9X7zgbe-BTjimuYHT-eBL-H4pLngHpckqMW4n0x0nJUbl29cpxD0VbFgQWmFFDI1OxIJ5Gi5ZBNZad1xoSjlo1Gd_erIpiiidmkPtKZNehNlgOKl3BF9BwIJfMCW28PYhDzsY9UKyAM5tkRwXF3BhF5kDAuf0D0x6fm1d4MFU_i_-uA70R699PmSMPrXkTXhmYJm7y6T2Ttd9ssJempjoNReAWX4pkyzX_SzlWZgEFE9ZQtbYFHxe99fmwf_8moL6YSFrXwmVJ_Dnd63C4PfhfmpgCzosQpUyllqYklOUodI8M2q3jzzXyaZcFzqj2Q4bCyFlegw2KCn8-Mamb5cDcMDr69m4EjwVzQaRPXMCEPfUVubFMEvQ%24AES%24SHA256&customerIdentification=OPR%3A10026912451response=Dk7M1PK-cuW1BL_dWU_5-L3ypv7F7eo79Gx7Xo07L6uUjj-ixax0OY-gNWe0hOOvSfPUv-E0XS-1h1DHIHLYWd64zB7h3TmLEFrL7gA0gEkLI_oTpehhTY7QnPHuhc6uXLMb3Ru4l8JFXTpnm2UGpAWTM2tTiwFTjJfZeerXE3mPY9ojQXh18pForJaMXSFlD9oAeAbdNL_c4GDX14nZYKmwx_e8dNqBoeHDxTjTUs7c10e6FD2qfwVTyRNOraSgUWZJCbCXPVD0CEBMMJehfqsLC-fOv8T4Q5r5QCckx48Dp6pv3bf4R9TiJ73SoYAe6bII8Jqb9rKuBHlEVh5bRA%24yd6JlJMjoI7nF0kA9QPAbozO4N9mkdOpw1ieEamvHpEA9a0H6dJMG-Dpc2Z6lkY383scZKt03-pA18l-OUZ_JrmM7yks6Et4Al_HXMKuvdE4hNbJeoPjRlSaWkeGSzgMaL3mMQxDZ9fnI2pzdSURnrZyH1zZrwdg-xHhAaX-QWHUAYjb4e_XTYWXqFhcRwRCE4WH4jvi_NgtzGJUckHICKG4_Dlx3adWfA9I7J4qYTneusqDbOL0YIztYArdOHMKOuhfMZPGBz_TbM4Sq5AjhTE_uKHcI9-dUYh6S-SYuoQK69JcpXIZSNUWnzNfgg4K2r9_RKj4SFlzoD7r6Q_5cqNBWik7PSXDtUf3LSmZPT9Y3Mdyevnfc18_Ax1Trr8NroiWVRTKMP8bCKm4qpzwZjzrr6Aw2r9fNwAB6pR223IMEbWFaSShN4xHXTAzFSSi7btPERlHlWIVmPICtY6kZ1qyi704qM4mYE1pWvV92j0dv6HZlq78hRun6bU8ntGeBUqC2jWDeGwdxWkFOouamDpiBqImjBkODXBYAEYgbPIyZINZznQcPv0JlcGomjeNZzNEXjtIMHBh6mhnl5ryQ4GdeZ1aXKn2fZ8zQtcdSF8wvFkCTE0dqHOZVGomSHd9Hp8ZSFXNCJv6mvp2czhpTaK5Pj0-noUJPWkviioqeW25xNWMUhhCGyQTrm6Edv33d5Na0D5FjAAdJczvxRd5U254bopBLWgFaLP8z6OEd2ro6GIK48UfilxSzcfNVrIEsDCB_qxCA5nNwhUJJqe98_0R9KmVWXT0Ts3NBSce8iClDRqjiM2mpEC7Tf3bDEDFW80huubk0b_ooFJf-I-0QUgFkEdddVexbS4taHkxbw7lbCoRX8-IYwIyh9hqpvRlWYMMXQ1I7LoXhhxQ56n8AFmBxjHrZZuXfWS_iUWiYe83wJXjUVwdHNuTtmFV0Uubp16ryfUu0IRLPNHG3uLzStatasm9ErI0cYDYKr2m54gVbYs9miwaDiWVN15VB7a2Jx3EepI3EG7IaY3gCbO8mBd3ucv5SGTFeH_M6OhD77aTHeJ3vEFrPgDMyBpczdtXLgJLo6O5o2Rj0tG5DmSxhF7lBKRo5FZJNyHZLMUQmwJ_ZQmbJtUDzx1EGgWAE3kRF9dVRcl3LFFOToa8JoWNTMv31kZcXFEq9q8D6ZzYTuqdk39nH5Uz6e_wTEpp5Gu6fl_ZIFqP4Tl6WsoqT223tuq1sSm9MT9nbQw9J6zDjTdsYVNmWOdZu6Ww6nrVjr1aTQwd5AEjrupM6a4LhAAKFA%24AES%24SHA256&customerIdentification=OPR%3A10026912451";
        $json = decrypt($source,$private_Key,$public_Key);
        $data = json_decode($json,true);
//        halt($data);

        $log = $data['uniqueOrderNo'];
        file_put_contents('./uploads/notify.txt',$log,FILE_APPEND);
        $order = DB::name('order')->where(['unique_order_id'=>$data['uniqueOrderNo']])->find();
        if($order['status'] != 0){
            exit();
        }

        DB::name('order')->where('unique_order_id',$data['uniqueOrderNo'])->update(['status'=>1,'paytime'=>time()]);
        echo 'SUCCESS';
    }


    //查询订单支付状态
    public function pay_status()
    {
        $status = DB::name('order')->where('unique_order_id',$this->params['unique_order_id'])->value('status');
        return $status;
    }

    //我的订单
    public function order_list()
    {
        $where = [
            'status' => 1,
            'is_end' => 0,
            'user_id' => $this->user['id'],
        ];
        $order = DB::name('order')->field('id,amount,level_id,paytime')->where($where)->select();
        if($order){
            foreach($order as $key => &$value){
                $level2 = DB::name('level')->where('id',$value['level_id'])->find();
                $value['level2'] = $level2['name'];
                $value['level1'] = DB::name('level')->where('id',$level2['pid'])->value('name');
            }
        }
        return json_encode($order,JSON_UNESCAPED_UNICODE);
    }

    //上传文件
    public function upload_file()
    {
        $file = request()->file('file');
        $path = $file->validate(['size'=>21078000,'ext'=>'zip,rar'])->move(ROOT_PATH . 'public' . DS . 'file_info');
        $file_url = DS . 'file_info' . DS . $path->getSaveName();
        $insert = [
            'user_id' => $this->user['id'],
            'name' => $this->params['name'],
            'level_id' => $this->params['level_id'],
            'createtime' => time(),
            'path' => $file_url,
        ];
        $result = DB::name('file_info')->insert($insert);
        if($result){
            $this->success('上传完成');
        }else{
            $this->error('上传失败');
        }
    }








}
