��Ҫ�޸��ļ�1��
\admin\login.php
�ڴ�����Щ��
<script> 
$(document).ready(function () {
    $('#name').focus();
});
</script>
���� <?php Typecho_Plugin::factory('gt')->render(); ?>
<script> 
//��ӵ���
 <?php Typecho_Plugin::factory('gt')->render(); ?>
$(document).ready(function () {
    $('#name').focus();
});
</script>


��Ҫ�޸��ļ�2��
\var\Widget\Login.php

$this->security->protect();

1)�޸�Ϊ
        if (!$this->request->__isset('rand')) {
            // protect
            $this->security->protect();
        }

2)��
        /** ����Ѿ���¼ */
        if ($this->user->hasLogin()) {
            /** ֱ�ӷ��� */
            $this->response->redirect($this->options->index);
        }

�������
        $response = Typecho_Plugin::factory('gt')->verify($this);
        if ("data" == $response) {
            return;
        }
        //�жϼ���֤���Ƿ��� �� ��û��ͨ����֤
        if (!empty(Helper::options()->plugins['activated']['Geetest']) && $response['statusMsg'] != 'success') {
            //$error = !empty($status[$response]) ? $status[$response] : $status['error'];
            $this->widget('Widget_Notice')->set($response[$response['statusMsg']]);
            $this->response->goBack();
        }




�������� ��ӭ����
www.moyu.win