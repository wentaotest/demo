<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】功能列表（已开发）</h1>

<ul>
	<li>
  		<a href="index.php?action=portal.wechat.givemeaccesstoken">
  			请告诉我口令！
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.getfollowers">
  			关注者列表
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.getfollowerinfo&open_id=oOfjCjhgAkEiV7Oz6Y6XNCWeZEGA">
  			关注者详情
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.msgtpls">
  			消息模板列表
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.addmsgtpl">
  			添加消息模板
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.msgtpldetail&id=5656b267d0d4fb39048b4596">
  			发送模板消息
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.getmenu">
  			查看自定义菜单
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.createmenu">
  			创建自定义菜单
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.deletemenu">
  			删除自定义菜单
  		</a>
  	</li>
  	<li>
  		<a href="index.php?action=portal.wechat.genqrcodeimg">
  			生成带参数的二维码
  		</a>
  	</li>
</ul>


<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>