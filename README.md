# GeetestV3_for_Typecho
**需要修改文件1：**

    \admin\login.php

在代码这些中

	<script
	$(document).ready(function () {
	    $('#name').focus();
	});
	</script>

增加 <?php Typecho_Plugin::factory('gt')->render(); ?>

	<script> 
	//添加到这
	 <?php Typecho_Plugin::factory('gt')->render(); ?>
	$(document).ready(function () {
	    $('#name').focus();
	});
	</script>


**需要修改文件2：**
\var\Widget\Login.php

	$this->security->protect();

1)修改为

        if (!$this->request->__isset('rand')) {
            // protect
            $this->security->protect();
        }

2)在

        /** 如果已经登录 */
        if ($this->user->hasLogin()) {
            /** 直接返回 */
            $this->response->redirect($this->options->index);
        }


下面添加

        $response = Typecho_Plugin::factory('gt')->verify($this);
        if ("data" == $response) {
            return;
        }
        //判断极验证码是否开启 且 有没有通过验证
        if (!empty(Helper::options()->plugins['activated']['Geetest']) && $response['statusMsg'] != 'success') {
            //$error = !empty($status[$response]) ? $status[$response] : $status['error'];
            $this->widget('Widget_Notice')->set($response[$response['statusMsg']]);
            $this->response->goBack();
        }




如有问题 欢迎反馈

演示 反馈：[www.moyu.win](http://www.moyu.win)