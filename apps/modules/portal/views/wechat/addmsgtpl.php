<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】添加消息模板</h1>

<a href="index.php?action=portal.wechat.menu">返回功能目录</a>

<form action="index.php?action=portal.wechat.addmsgtpl" method="post">
	<p style="color: red;">添加之前，请确保已经在微信公众号的mp中添加过该模板！</p>
	
	<p>
		<label>行业编号：
			<input type="text" name="industry_id" value="" />
		</label>
	</p>
	
	<p>
		<label>行业名称：
			<input type="text" name="industry_nm" value="" />
		</label>
	</p>
	
	<p>
		<label>模板编号：
			<input type="text" name="tpl_id" value="" />
		</label>
	</p>
	
	<p>
		<label>模板标题：
			<input type="text" name="title" value="" />
		</label>
	</p>
	
	<p>
		<label>变量列表：
			<input type="text" name="params" value="" placeholder="多个变量之间以逗号间隔" />
		</label>
	</p>
	
	<p>
		<label>详细内容：
			<textarea name="content" rows="" cols=""></textarea>
		</label>
	</p>
	
	<p>
		<label>内容示例：
			<textarea name="sample" rows="" cols=""></textarea>
		</label>
	</p>
	
	<button type="submit">提交</button>
</form>

<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>