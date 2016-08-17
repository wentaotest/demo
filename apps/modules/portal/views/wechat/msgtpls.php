<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】消息模板列表</h1>

<a href="index.php?action=portal.wechat.menu">返回功能目录</a>

<ul>
	<?php foreach ($msg_tpls as $tpl) { ?>
  	<li><a href="index.php?action=portal.wechat.msgtpldetail&id=<?php echo $tpl['_id']->{'$id'}; ?>"><?php echo $tpl['title']; ?></a></li>
	<?php }?>
</ul>


<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>