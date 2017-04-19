<?php
/**
 * GEETEST(极验验证码) 3.0 for Typecho
 * 从<a href="http://nsimple.top/archives/typecho-plugin-geetest.html" target="_blank">没那么简单</a>改进而来
 *
 * @package GeetestV3
 * @author 玖玖kyuu
 * @version 1.0.0
 * @link http://www.moyu.win
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once('lib/class.geetestlib.php');

class Geetest_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('gt')->render = array('Geetest_Plugin', 'render');
        Typecho_Plugin::factory('gt')->server = array('Geetest_Plugin', 'server');
        Typecho_Plugin::factory('gt')->verify = array('Geetest_Plugin', 'verify');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 极验验证配置 */
        $geetest_id = new Typecho_Widget_Helper_Form_Element_Text('geetest_id', NULL, '', _t('极验验证ID'));
        $geetest_key = new Typecho_Widget_Helper_Form_Element_Text('geetest_key', NULL, '', _t('极验验证Key'));
        $types = array(
            'float' => '浮动式',
            'embed' => '嵌入式'
        );
        $geetest_type = new Typecho_Widget_Helper_Form_Element_Select('geetest_type', $types, 'float', _t('极验验证类型'));
        $form->addInput($geetest_id);
        $form->addInput($geetest_key);
        $form->addInput($geetest_type);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        $config = Typecho_Widget::widget('Widget_Options')->plugin('Geetest');
        $jsondata = '../index.php/action/login?rand=';
        echo <<<EOT
       </script>\n<script src="https://static.geetest.com/static/tools/gt.js"></script>\n<script>
       $(".btn").attr({disabled:true,style:"background-color:#a3b7c1;color:#FFF;cursor:not-allowed;"});
        $(".submit").before("<div id=\"captcha\" style=\"height:44px;\"><p class=\"geetestinit\" style=\"background-color: #e8e8e8;color: #4d4d4d;padding: 12px;\">验证系统初始化中...</p></div>");
    //极客验证码验证
    (function(){
        var handler = function (captchaObj) {
            // 将验证码加到id为captcha的元素里
            captchaObj.appendTo("#captcha");
            captchaObj.onSuccess(function () {
                    $(".btn").attr({disabled:false});$(".btn").removeAttr("style");     
             });
             captchaObj.onReady(function () {
            // DOM 准备好后，删除 .geetestinit 元素
            $(".geetestinit").remove();
        });
        };
        $.ajax({
            // 获取id，challenge，success（是否启用failback）
            url: "{$jsondata}"+Math.random()*100,
            type: "get",
            dataType: "json", // 使用jsonp格式
            success: function (data) {
                // 使用initGeetest接口
                // 参数1：配置参数，与创建Geetest实例时接受的参数一致
                // 参数2：回调，回调的第一个参数验证码对象，之后可以使用它做appendTo之类的事件new_captcha: data.new_captcha,
                initGeetest({
                    gt: data.gt,
                    challenge: data.challenge,
                    new_captcha:'true',
                    product: '{$config->geetest_type}', // 产品形式float-浮动式 embed-嵌入式
                    offline: !data.success, //支持本地验证
                    width: '100%'
                }, handler);
            }
        });
    })();
    
EOT;

    }
	
    /**
     * 输出验证geetest服务器响应字符串和验证行为是否合法
     *
     * @param array $data
     * @return string
     */
    public function verify($loginobj)
    {
        @session_start();
        //获取参数并且初始化
        $config = Typecho_Widget::widget('Widget_Options')->plugin('Geetest');
        $GtSdk = new GeetestLib($config->geetest_id, $config->geetest_key);
        $userdata = array(
            "user_id" => "1", # 网站用户id
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        );
        //极验证返回json前台验证数据
        if ($loginobj->request->__isset('rand')) {//根据键判断是否是取极验证数据
            $status = $GtSdk->pre_process($userdata, 1);
            $_SESSION['gtserver'] = $status;
            echo $GtSdk->get_response_str();
            return "data";
        }
        $requestres = array(
            'statusMsg' => '',
            'empty' => '请进行验证',
            'failed' => '验证失败',
            'success' => '验证通过',
            'down' => '请求超时，请重试',
            'error' => '服务器异常，请重试'
        );
        $data = $loginobj->request->from('geetest_challenge', 'geetest_validate', 'geetest_seccode');
        if (empty($data['geetest_challenge']) || empty($data['geetest_validate']) && empty($data['geetest_seccode'])) {
            $requestres['statusMsg'] = 'empty';
            return $requestres;
        }
        if ($_SESSION['gtserver'] == 1) {
            $result = $GtSdk->success_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'], $userdata);
            if ($result) {
                $requestres['statusMsg'] = 'success';
                return $requestres;
            } else {
                $requestres['statusMsg'] = 'failed';
                return $requestres;
            }
        } else {
            if ($GtSdk->fail_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'])) {
                $requestres['statusMsg'] = 'success';
                return $requestres;
            } else {
                $requestres['statusMsg'] = 'down';
                return $requestres;
            }
        }
    }
}
