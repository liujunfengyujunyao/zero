<?php

namespace app\common\library;

use think\Hook;
use alisms\SendSms;

/**
 * 短信验证码类
 */
class Sms
{

    /**
     * 验证码有效时长
     * @var int
     */
    protected static $expire = 120;

    /**
     * 最大允许检测的次数
     * @var int
     */
    protected static $maxCheckNums = 10;

    /**
     * 获取最后一次手机发送的数据
     *
     * @param   int    $mobile 手机号
     * @param   string $event  事件
     * @return  Sms
     */
    public static function get($mobile, $event = 'default')
    {
        $sms = \app\common\model\Sms::
        where(['mobile' => $mobile, 'event' => $event])
            ->order('id', 'DESC')
            ->find();
        Hook::listen('sms_get', $sms, null, true);
        return $sms ? $sms : null;
    }

    /**
     * 发送验证码
     *
     * @param   int    $mobile 手机号
     * @param   int    $code   验证码,为空时将自动生成4位数字
     * @param   string $event  事件
     * @return  boolean
     */
    public static function send($mobile, $code = NULL, $event = 'default')
    {
        $code = is_null($code) ? mt_rand(1000, 9999) : $code;
        $time = time();
        $ip = request()->ip();
        $sms = \app\common\model\Sms::create(['event' => $event, 'mobile' => $mobile, 'code' => $code, 'ip' => $ip, 'createtime' => $time]);
        //$result = Hook::listen('sms_send', $sms, null, true);
        $s = new SendSms();
        //设置关键的四个配置参数，其实配置参数应该写在公共或者模块下的config配置文件中，然后在获取使用，这里我就直接使用了。
//        
//        $s->signName = '旭日东升';
        $s->signName = '哇咔哇咔';
        $s->templateCode = 'SMS_166867972';

        //$mobile为手机号
        //$mobile = $phone;
        //模板参数，自定义了随机数，你可以在这里保存在缓存或者cookie等设置有效期以便逻辑发送后用户使用后的逻辑处理
        //session('code',$code);//用于比对
        $templateParam = array("code"=>$code);
        $result = $s->send($mobile,$templateParam);
        if (!$result)
        {
            $sms->delete();
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 发送通知
     *
     * @param   mixed  $mobile   手机号,多个以,分隔
     * @param   string $msg      消息内容
     * @param   string $template 消息模板
     * @return  boolean
     */
    public static function notice($mobile, $msg = '', $template = null)
    {
        $params = [
            'mobile'   => $mobile,
            'msg'      => $msg,
            'template' => $template
        ];
        $result = Hook::listen('sms_notice', $params, null, true);
        return $result ? true : false;
    }

    /**
     * 校验验证码
     *
     * @param   int    $mobile 手机号
     * @param   int    $code   验证码
     * @param   string $event  事件
     * @return  boolean
     */
    public static function check($mobile, $code, $event = 'default')
    {
        $time = time() - self::$expire;
        $sms = \app\common\model\Sms::where(['mobile' => $mobile, 'event' => $event])
                ->order('id', 'DESC')
                ->find();
        if ($sms)
        {
            if ($sms['createtime'] > $time && $sms['times'] <= self::$maxCheckNums)
            {
                $correct = $code == $sms['code'];
                if (!$correct)
                {
                    $sms->times = $sms->times + 1;
                    $sms->save();
                    return FALSE;
                }
                else
                {   
                    //$result = Hook::listen('sms_check', $sms, null, true);
                    return TRUE;
                }
            }
            else
            {
                // 过期则清空该手机验证码
                self::flush($mobile, $event);
                return false;
            }
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * 清空指定手机号验证码
     *
     * @param   int    $mobile 手机号
     * @param   string $event  事件
     * @return  boolean
     */
    public static function flush($mobile, $event = 'default')
    {
        \app\common\model\Sms::
        where(['mobile' => $mobile, 'event' => $event])
            ->delete();
        Hook::listen('sms_flush');
        return true;
    }
}
