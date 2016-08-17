<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】消息模板详情</h1>

<a href="index.php?action=portal.wechat.menu">返回功能目录</a>
	
<p>
	<label>行业编号：
		<span><?php echo $tpl_info['industry_id']; ?></span>
	</label>
</p>

<p>
	<label>行业名称：
		<span><?php echo $tpl_info['industry_nm']; ?></span>
	</label>
</p>

<p>
	<label>模板编号：
		<span><?php echo $tpl_info['tpl_id']; ?></span>
	</label>
</p>

<p>
	<label>模板标题：
		<span><?php echo $tpl_info['title']; ?></span>
	</label>
</p>

<p>
	<label>变量列表：
		<span><?php echo $tpl_info['params']; ?></span>
	</label>
</p>

<p>
	<label>详细内容：
		<span><?php echo $tpl_info['content']; ?></span>
	</label>
</p>

<p>
	<label>内容示例：
		<span><?php echo $tpl_info['sample']; ?></span>
	</label>
</p>

<hr>

<form action="index.php?action=portal.wechat.sendtplmsg" method="post">
	<input type="hidden" name="_id" value="<?php echo $tpl_info['_id']->{'$id'}; ?>">
	<input type="hidden" name="tpl_id" value="<?php echo $tpl_info['tpl_id']; ?>" />
	<p>
		<label>接收用户：
			<input type="text" name="to" value="oOfjCjhgAkEiV7Oz6Y6XNCWeZEGA" />
		</label>
	</p>
	<p>
		<label>调转地址：
			<input type="text" name="url" value="" />
		</label>
	</p>
	<span>参数列表如下：</span>
	<?php foreach ($params_arr as $p) { ?>
	<p>
		<label><?php echo $p;?>：
			<input type="text" name="data[<?php echo $p; ?>][value]" value="" />
		</label>
	</p>
	<?php } ?>
	
	<button type="submit">提交</button>
</form>
	

<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>