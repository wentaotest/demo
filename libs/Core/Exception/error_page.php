<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
	<meta charset="utf-8" />
	<title>OMG! It doesn't work !</title>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
	<meta content="Error or Exception occours" name="description" />
	<meta content="Baisheng An" name="author" />
	
	<!-- ================== BEGIN BASE CSS STYLE ================== -->
	<link href="statics/plugins/jquery-ui/themes/base/minified/jquery-ui.min.css" rel="stylesheet" />
	<link href="statics/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
	<link href="statics/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
	<link href="statics/css/color_admin/animate.min.css" rel="stylesheet" />
	<link href="statics/css/color_admin/style.min.css" rel="stylesheet" />
	<link href="statics/css/color_admin/style-responsive.min.css" rel="stylesheet" />
	<link href="statics/css/color_admin/theme/default.css" rel="stylesheet" id="theme" />
	<!-- ================== END BASE CSS STYLE ================== -->
	
	<!-- ================== BEGIN PAGE CSS STYLE ================== -->
    <link href="statics/plugins/jquery.countdown/jquery.countdown.css" rel="stylesheet" />
	<!-- ================== END PAGE CSS STYLE ================== -->
	
	<!-- ================== BEGIN BASE JS ================== -->
	<script src="statics/plugins/pace/pace.min.js"></script>
	<!-- ================== END BASE JS ================== -->
</head>
<body class="bg-white p-t-0 pace-top">
	<!-- begin #page-loader -->
	<div id="page-loader" class="fade in"><span class="spinner"></span></div>
	<!-- end #page-loader -->
	
	<!-- begin #page-container -->
	<div id="page-container" class="fade">
	    <!-- begin coming-soon -->
        <div class="coming-soon">
            <div class="coming-soon-header">
                <div class="bg-cover"></div>
                <div class="brand">
                    <span class="logo"></span> 代码错误
                </div>
                <?php 
                	$err_names = array('1'   => 'Fatal Error',
                					   '2'   => 'Warning',
			                		   '4'   => 'Parse Error',
			                		   '8'   => 'Notice',
			                		   '16'  => 'Core Error',
			                		   '32'  => 'Core Warning',
			                		   '64'  => 'Compile Error',
			                		   '128' => 'Compile Warning',
                					   '256' => 'User Error',
                					   '512' => 'User Warning',
                					   '1024' => 'User Notice',
                			           '2047' => 'All(~E_SRTICT)',
                			           '2048' => 'Strict',
                	); 
                ?>
                <div class="desc">
                	Type : <?php echo $err_names[$type]; ?>
                	Message : <?php echo $message; ?><br>
                	File : <?php echo $file; ?>
                	Line : <?php echo $line; ?><br>
                </div>
            </div>
            <div class="desc">
            	<?php 
            		$err_hints = array('1'   => '运行时致命的错误。不能修复的错误。停止执行脚本。',
                					   '2'   => '运行时非致命的错误。没有停止执行脚本。',
			                		   '4'   => '编译时的解析错误。解析错误应该只由解析器生成。',
			                		   '8'   => '运行时的通知。脚本发现可能是一个错误，但也可能在正常运行脚本时发生。',
			                		   '16'  => 'PHP启动时的致命错误。这就如同 PHP核心的 E_ERROR。',
			                		   '32'  => 'PHP启动时的非致命错误。这就如同 PHP核心的 E_WARNING。',
			                		   '64'  => '编译时致命的错误。这就如同由 Zend 脚本引擎生成的 E_ERROR。',
			                		   '128' => '编译时非致命的错误。这就如同由 Zend 脚本引擎生成的 E_WARNING。',
			            			   '256' => '用户自定义的错误。',
			            			   '512' => '用户自定义的警告。',
			            			   '1024' => '用户自定义的提醒。',
			            			   '2047' => '所有错误，但不包括E_STRICT。',
			            			   '2048' => '编码标准化警告，允许PHP建议如何修改代码以确保最佳的互操作性向前兼容性。',
            		);
            	?>
            </div>
            <div class="coming-soon-content">
            	<div class="desc">
            		<?php echo $err_hints[$type]; ?>
            	</div>
            	
                <div class="input-group">
                    <input id="input-bd-search-content" type="text" class="form-control" placeholder="错误描述" value="PHP:<?php echo $message; ?>" />
                    <div class="input-group-btn">
                        <button id="btn-bd-search" type="button" class="btn btn-success">百度一下</button>
                    </div>
                </div>
                
                <br>
                
                <p>
                    <a href="http://php.net/manual/zh/">PHP官方手册</a>
                    <a href="http://www.w3school.com.cn/">W3School</a>
                </p>
            </div>
        </div>
        <!-- end coming-soon -->
       
	</div>
	<!-- end page container -->
	
	<!-- ================== BEGIN BASE JS ================== -->
	<script src="statics/plugins/jquery/jquery-1.9.1.min.js"></script>
	<script src="statics/plugins/jquery/jquery-migrate-1.1.0.min.js"></script>
	<script src="statics/plugins/jquery-ui/ui/minified/jquery-ui.min.js"></script>
	<script src="statics/plugins/bootstrap/js/bootstrap.min.js"></script>
	<!--[if lt IE 9]>
		<script src="statics/js/crossbrowserjs/html5shiv.js"></script>
		<script src="statics/js/crossbrowserjs/respond.min.js"></script>
		<script src="statics/js/crossbrowserjs/excanvas.min.js"></script>
	<![endif]-->
	<script src="statics/plugins/slimscroll/jquery.slimscroll.min.js"></script>
	<script src="statics/plugins/jquery-cookie/jquery.cookie.js"></script>
	<!-- ================== END BASE JS ================== -->
	
	<!-- ================== BEGIN PAGE LEVEL JS ================== -->
    <script src="statics/plugins/jquery.countdown/jquery.plugin.js"></script>
    <script src="statics/plugins/jquery.countdown/jquery.countdown.js"></script>
	<script src="statics/js/color_admin/coming-soon.demo.min.js"></script>
	<script src="statics/js/color_admin/apps.min.js"></script>
	<!-- ================== END PAGE LEVEL JS ================== -->
	
	<script>
		$(document).ready(function() {
			App.init();
			ComingSoon.init();
		});
		
		$('#btn-bd-search').click(function() {
			var search_content = $("#input-bd-search-content").val();
			
			window.open("https://www.baidu.com/s?wd=" + search_content);  
		});
	</script>
</body>
</html>