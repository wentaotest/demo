<!-- 加载页首-开始 -->
<?php render_view('apps\modules\portal\views\header.php', array('xx'=>'xx'));?>
<!-- 加载页首-结束 -->

<div class="container">
	
	<form action="index.php?action=portal.weixin.tmpqrcode" method="post" class="form-horizontal">
		<div class="form-group">
	    	<label for="expire" class="col-sm-2 control-label">有效时间</label>
	    	<div class="col-sm-6">
	    		<input type="text" class="form-control" id="expire" placeholder="有效时间（单位：秒）">
	    	</div>
	  </div>
	</form>
</div>


<!-- 加载页尾-开始 -->
<?php render_view('apps\modules\portal\views\footer.php', array('xx'=>'xx'));?>
<!-- 加载页尾-结束 -->