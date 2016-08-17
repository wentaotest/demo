<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>测试手机号变更</title>
</head>
<style>
	*{
		margin-left:0px;
		margin-top:0px;
	}
	ul{
		list-style-type:none;
	}
	li{
		margin-bottom:15px;
	}
	#main{
		position:absolute;
		left:50%;
		width:300px;
		
		border:solid skyblue 1px;
		padding:20px;
		margin-left:-150px;
		top:60px;
	}
	.left-side{
		margin-right:20px;
	}
	.bg{
		display:none;
		width:100%;
		height:800px;
		position:absolute;
		z-index:100;
		background-color:rgba(80,80,80,.5);
	}
	.msg-body{
		width:400px;
		height:200px;
		position:absolute;
		left:50%;
		top:80px;
		margin-left:-200px;
		
	}
	.error{
		color:red;
	}
	.pass{
		color:darkgreen;
	}
	.close{
		float:right;
		margin-right:10px;
		margin-top:10px;
		width:20px;
		height:20px;
		border-radius:50%;
		background-color:red;
		color:white;
		font-weight:bold;
		text-align:center;
		vertical-align:middle;
		cursor:pointer;
	}
	.msg-info{
		width:100%;
		height:80%;
		background-color:white;
		float:left;
		text-align:center;
		vertical-align:middle;
		font-size:2em;
	}
	.msg-header{
		width:100%;
		height:20%;
		background-color:white;
		float:left;
	}
</style>
<body>
<div id="main">
	<form id = "myform" >
		<ul>
			<li>
				<span class='left-side'>
					<label for="oldphone">原手机号:</label>
				</span>
				<span class='right-side'>
					<input type='text' id='oldphone' name='oldphone' placeholder="输入11位手机号"/>
				</span>
			</li>
			<li></li>
			<li>
				<span class='left-side'>
					<label for="newphone">新手机号:</label>
				</span>
				<span class='right-side'>
					<input type='text' id='newphone' name= 'newphone' placeholder="输入11位手机号或数字"/>
				</span>
			</li>
			<li></li>
			<li>
				<span class='left-side'>
					<label for="mysubmit">提交修改:</label>
				</span>
				<span class='right-side'>
					<button type='submit' id='mysubmit'>提交修改</button>
				</span>
			</li>
			<li></li>
		</ul>
	</form>
	
</div>
<div class='bg'>
	<div class='msg-body'>
		<div class='msg-header'>
			<div class='close'>&times;</div>
		</div>
		<div class='msg-info'>
		
		</div>
	</div>
</div>
</body>
<script src="statics/js/jquery/1.8.3/jquery.min.js"></script>
<script src="statics/js/resource/jquery.validate.min.js"></script>
<script>
	
	$(document).ready(function(){
		//表单验证
		$(document).ready(function(){
			//添加验证手机号方法
			jQuery.validator.addMethod("mobile", function(value, element) { 
				var length = value.length; 
				var mobile = /^(\d{11})$/ 
				return this.optional(element) || (length == 11 && mobile.test(value)); 
			});  

			$("#myform").validate({
				//设置校验触发的时机
				onsubmit	: true,
		        onfocusout	: false,
		        onkeyup		: false,
		        onclick		: false,
				rules: {
					"oldphone": {
						required 	: true,
						mobile		: true,
						remote		: {
							url	 	 : 'index.php?action=portal.test.checkphone',
							type 	 : 'post',
							dataType : 'json',
							data	 : {
								oldphone: function() {
									return $("input[name=oldphone]").val();
								}
							}
						},
					},
					"newphone": {
						required	: true,
						mobile		: true,
						remote		: {
							url	 	 : 'index.php?action=portal.test.checkphone',
							type 	 : 'post',
							dataType : 'json',
							data	 : {
								newphone: function() {
									return $("input[name=newphone]").val();
								}
							}
						},
					},
				},
					  
				messages: {
					"oldphone": {
						required	: "要修改的手机号不能为空！",
						mobile		: "手机号码格式错误!",
						remote		: "该号码还没注册！",
					},
					"newphone": {
						required	: "新手机号不能为空！",
						mobile		: "手机号码格式错误!",
						remote		: "该号码已经被注册！",
					}
				},
				  
				errorPlacement:function(error,element) {
					$( element ).closest('li').next().append(error);
			   	},
			   	submitHandler: function(form) {
		   			var oldphone  = $.trim($('input[name=oldphone]').val());
		   			var newphone  = $.trim($('input[name=newphone]').val());

		   			if(oldphone !='' && oldphone != '') {
		   				$.ajax({
			   	    		url	:'index.php?action=portal.test.index',
			   	    		data:{oldphone:oldphone, newphone:newphone},
			   	    		async:false,
			   				type:'post',
			   				dataType:'json',
			   				success:function(info){
								$('.msg-info').html(info['info']);
								$('.bg').show();
								if (info['code'] == '0') {
									$('.msg-info').addClass('pass');
									
								} else {
									$('.msg-info').addClass('error');
								}
								$('.close').click(function(){
									$('.bg').hide();
									if (info['code'] == '0') {
										location.href = location.href;
									}
								});
			   				},
			   	        });
			   		} else {
			   			if (oldphone == ''){
							$('input[name=oldphone]').closest('li').next().html('<label class="error" for="oldphone">'+'号码不能为空!'+'</label>');
						} else if (vcode == '') {
							$('input[name=newphone]').closest('li').next().html('<label class="error" for="newphone">'+'号码不能为空!'+'</label>');
						}
						return false;
				   	}
		   	    },
		   	 	debug:true,
			});	
		});
	});
</script>
</html>
