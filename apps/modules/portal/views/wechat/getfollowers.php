<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】关注者列表（共<?php echo $followers['total'];?>人）</h1>

<a href="index.php?action=portal.wechat.menu">返回功能目录</a>

<ul>
	<?php foreach ($followers['data']['openid'] as $f) { ?>
  	<li><a href="index.php?action=portal.wechat.getfollowerinfo&open_id=<?php echo $f; ?>&start_open_id=<?php echo $start_open_id; ?>"><?php echo $f; ?></a></li>
	<?php }?>
</ul>


<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>