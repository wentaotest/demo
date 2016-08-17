<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】小工具  - 图片地址转二维码</h1>

<a href="index.php?action=portal.index.index">返回小工具目录</a>

<div>
	<p>
		<label>文章标题：
			<input type="text" name="title" value="" />
		</label>
	</p>
	
	<p>
		<label>微信文章地址：
			<input type="text" name="long_url" id="input-long-url" value="" />
		</label>
	</p>
	
	<p>
		<label>容错率：
			<select name="ec_level">
				<option value="QR_ECLEVEL_L">7%</option>
				<option value="QR_ECLEVEL_M">15%</option>
				<option value="QR_ECLEVEL_Q">25%</option>
				<option value="QR_ECLEVEL_H">30%</option>
			</select>
		</label>
	</p>
	
	<p>
		<label>二维码尺寸：
			<input type="text" name="size" value="" />
		</label>
	</p>
	
	<button id='btn-create'>生成</button>
</div>

<div>
	<p>
		<input type="text" id="input-tiny-url" value="" />
	</p>
	
	<p>
		<img id="img-qr-code" alt="" src="index.php?action=portal.index.createqrcode4url&url=">
	</p>
	
	<p>
		<textarea id="textarea-html-code" rows="" cols=""></textarea>
	</p>
</div>


<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>

<script>
$(function(){
    $('#btn-create').click(function(){
         $.ajax({
             type: "post",
             url: "index.php?action=portal.index.createtinyurl",
             data: {long_url:$("#input-long-url").val()},
             dataType: "json",
             success: function(data){
                 $("#input-tiny-url").val(data.tinyurl);

                 var qr_code_url = "index.php?action=portal.index.createqrcode4url&url=" + data.tinyurl;
                 $("#img-qr-code").attr('src', qr_code_url);
             }
         });
    });
});
</script>