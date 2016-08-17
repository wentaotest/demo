<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<fieldset>
	<legend>消息提示：</legend>
	<div>
		<?php echo $message; ?>
	</div>
	
	<div>
		<?php if (!empty($redirect_url) && !empty($delay_seconds)) { ?>
		<span id="timer_cnt"><?php echo $delay_seconds; ?></span> 秒后自动跳转。
		<?php } ?>
		
		<?php if (!empty($redirect_url)) { ?>
		<a href="<?php echo $redirect_url; ?>">点击跳转</a>
		<?php } else { ?>
		<a href="javascript:history.go(-1)">点击后退</a>
		<?php }?>
	</div>
</fieldset>


<script type="text/javascript">
<?php if (!empty($redirect_url) && !empty($delay_seconds)) { ?>
window.setInterval(function() {
	var sec = $("#timer_cnt").html();
	sec = sec - 1;
	$("#timer_cnt").html(sec);

	if (sec == 0) {
		window.location.href = "<?php echo $redirect_url; ?>";
	}
},1000);
<?php } ?>
</script>

<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>