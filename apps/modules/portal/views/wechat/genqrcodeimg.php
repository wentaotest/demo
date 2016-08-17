<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】生成带参数的公众号二维码</h1>

<a href="index.php?action=portal.wechat.menu">返回功能目录</a>

<form action="index.php?action=portal.wechat.genqrcodeimg" method="post">
	
	<p>
		<label>类型：
			<select name="type">
				<option value="QR_SCENE">临时</option>
				<option value="QR_LIMIT_SCENE">永久整数型</option>
				<option value="QR_LIMIT_STR_SCENE">永久字符串</option>
			</select>
		</label>
	</p>
	
	<p>
		<label>参数：
			<input type="text" name="scene_val" value="" />
		</label>
	</p>
		
	<button type="submit">提交</button>
</form>

<?php if (!empty($ticket)) { ?>
<img width="256px" height="256px" src="index.php?action=portal.wechat.getqrcodeimg&ticket=<?php echo $ticket; ?>" />
<?php } ?>

<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>