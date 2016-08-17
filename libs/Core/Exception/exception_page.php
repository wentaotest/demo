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
                    <span class="logo"></span> 捕获异常
                </div>
                <div class="desc">
                	Code : <?php echo $code; ?>
                	Message : <?php echo $message; ?><br>
                	File : <?php echo $file; ?>
                	Line : <?php echo $line; ?><br>
                </div>
            </div>
            <div class="desc">
               	<table class="table">
                    <thead>
                        <tr>
                        	<th>#</th>
                            <th>File</th>
                            <th>Line</th>
                            <th>Class</th>
                            <th>Type</th>
                            <th>Function</th>
                            <th>Args</th>
                        </tr>
                    </thead>
                    <tbody>
                    	<?php foreach($trace as $k => $p) { ?>
                        <tr>
                        	<td><?php echo $k; ?></td>
                            <td><?php echo $p['file']; ?></td>
                            <td><?php echo $p['line']; ?></td>
                            <td><?php echo isset($p['class']) ? $p['class'] : ''; ?></td>
                            <td><?php echo isset($p['type']) ? $p['type'] : ''; ?></td>
                            <td><?php echo $p['function']; ?></td>
                            <td><?php echo json_encode_ex($p['args'], true); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
               </div>
            <div class="coming-soon-content">
                <div class="input-group">
                    <input id="input-bd-search-content" type="text" class="form-control" placeholder="错误描述" />
                    <div class="input-group-btn">
                        <button id="btn-bd-search" type="button" class="btn btn-success">百度一下</button>
                    </div>
                </div>
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