<!-- 加载页首-开始 -->
<?php render_view('apps\modules\portal\views\header.php', array('xx'=>'xx'));?>
<!-- 加载页首-结束 -->

	<div class='container'>
	    <h1>微信api列表</h1>
	    <h3>基础支持</h3>
	    <ol class="list-unstyled">
	        <li><a href="index.php?action=portal.weixin.getAccessToken" target="_blank">获取access_token</a></li>
	        <li><a href="index.php?action=portal.weixin.getWxServerIp" target="_blank">获取微信服务器IP地址</a></li>
	    </ol>

	    <h3>推广支持</h3>
	    <ol class="list-unstyled">
	    	<li>生产带参数二维码</li>
	    	<li>
	    		<ol>
	    			<li><a href="index.php?action=portal.weixin.tmpqrcode" target="_blank">临时二维码</a></li>
	    			<li><a href="index.php?action=portal.weixin.fixedqrcode" target="_blank">永久二维码</a></li>
	    		</ol>
	    	</li>
	    </ol>
	</div>

    <!-- 加载基础js-开始 -->
	<?php render_view('apps\modules\portal\views\base_js.php', array('xx'=>'xx'));?>
	<!-- 加载基础js-结束 -->
    <script type="text/javascript">

    </script>
    
<!-- 加载页尾-开始 -->
<?php render_view('apps\modules\portal\views\footer.php', array('xx'=>'xx'));?>
<!-- 加载页尾-结束 -->