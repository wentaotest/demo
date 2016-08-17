<?php namespace June\apps\services;

include __ROOT__ . "/libs/Plugins/phpqrcode/phpqrcode.php";

/**
 * 二维码生成服务使用phpqrcode纯PHP方案，其中：
 * 
 * Error Correction Level（容错等级）划分为四级：
 * QR_ECLEVEL_L： 7%的面积可覆盖
 * QR_ECLEVEL_M：15%的面积可覆盖
 * QR_ECLEVEL_Q：25%的面积可覆盖
 * QR_ECLEVEL_H：30%的面积可覆盖
 * 鉴于二维码的容错特性，我们可以在二维码上覆盖自定义图片（如logo、头像等）
 * 
 * Matrix Point Size（点的大小），默认为4
 * 
 * Margin（边框大小），默认为
 *
 */

class QRcodeService {

	/**
	 * 生成二维码并返回二维码图片内容但不保存
	 * 
	 * @param $text_str 待编码的文本内容
	 * @param $error_correction_level 容错等级
	 * @param $matrix_point_size 点的大小
	 * @param $margin 边框大小
	 * @return $qr_code_image
	 * 
	 * @author An Baisheng
	 */
	public static function genQRcode($text_str, $error_correction_level = QR_ECLEVEL_M, $matrix_point_size = 4, $margin = 2) {
		return \QRcode::png($text_str, false, $error_correction_level, $matrix_point_size, $margin); 
	}

	/**
	 * 生成二维码并返回二维码图片内容，保存至系统临时目录下，如果需要持久化，需要额外转存
	 * 
	 * @param $text_str 待编码的文本内容
	 * @param $outfile_nm 保存的文件名
	 * @param $error_correction_level 容错等级
	 * @param $matrix_point_size 点的大小
	 * @param $margin 边框大小
	 * @return $qr_code_image
	 * 
	 * @author An Baisheng
	 */
	public static function genAndSaveQRcode($text_str, $outfile_nm, $error_correction_level = QR_ECLEVEL_M, $matrix_point_size = 4, $margin = 2) {
		$temp_dir = sys_get_temp_dir();
		$file_path = $temp_dir . $outfile_nm;

		return \QRcode::png($text_str, $file_path, $error_correction_level, $matrix_point_size, $margin, true); 
	}

	/**
	 * 生成带有嵌入logo的二维码
	 * 
	 * @param string $text_str 待编码的文本内容
	 * @param string $logo_file_path logo文件路径
	 * @param string $outfile_nm 输出文件名，如果值为null，则直接将生成的内容输出到浏览器，前面加：header('Content-type: image/png');
	 * @param integer $margin 边框大小
	 * @param float $logo_size logo宽度或高度占二维码宽度或高度的比例
	 * @param integer $logo_border_w logo边框宽度
	 * @return mixed 
	 *
	 */
	public static function genQRcodeWithLogo($text_str, $logo_file_path, $outfile_nm, $margin = 4, $logo_size = 0.25 ,$logo_border_w = 6) {
    	$qr_tmp_file = sys_get_temp_dir() . "qr_" . time() . rand(100000, 999999) . ".png"; 
    	\QRcode::png($text_str, $qr_tmp_file, QR_ECLEVEL_Q, 4, $margin);

    	$qr_im   = imagecreatefromstring(file_get_contents($qr_tmp_file));
    	$logo_im = imagecreatefromstring(file_get_contents($logo_file_path));

    	$qr_width  = imagesx($qr_im);
    	$qr_height = imagesy($qr_im);

    	$logo_width  = imagesx($logo_im);
    	$logo_height = imagesy($logo_im);

    	// 将logo切成圆角正方形
    	$im_temp = imagecreatetruecolor($logo_width, $logo_height);
    	imagecopymerge($im_temp, $logo_im, 0, 0, 0, 0, $logo_width, $logo_height, 100);

    	$border_color = imagecolorallocate($im_temp, 255, 255, 255);

    	$radius = 12;
    	$arc_w = $radius*2;
    	$arc_h = $radius*2;

    	$lt_cx = $radius;
    	$lt_cy = $radius;
    	imagearc($im_temp, $lt_cx, $lt_cy, $arc_w, $arc_h, 180, 270, $border_color);
    	imagefilltoborder($im_temp, 0, 0, $border_color, $border_color);

    	$rt_cx = $logo_width - $radius;
    	$rt_cy = $radius;
    	imagearc($im_temp, $rt_cx, $rt_cy, $arc_w, $arc_h, 270, 360, $border_color);
    	imagefilltoborder($im_temp, $logo_width, 0, $border_color, $border_color);

    	$lb_cx = $radius;
    	$lb_cy = $logo_height - $radius;
    	imagearc($im_temp, $lb_cx, $lb_cy, $arc_w, $arc_h, 90, 180, $border_color);
    	imagefilltoborder($im_temp, 0, $logo_height, $border_color, $border_color);

    	$rb_cx = $logo_width - $radius;
    	$rb_cy = $logo_height - $radius;
    	imagearc($im_temp, $rb_cx, $rb_cy, $arc_w, $arc_h, 0, 90, $border_color);
    	imagefilltoborder($im_temp, $logo_width, $logo_height, $border_color, $border_color);

    	// 为logo加一个圆角边框
    	$border_w = $logo_border_w;
    	$bg_w = $logo_width+$border_w*2;
    	$bg_h = $logo_height+$border_w*2;

    	$bg_im = imagecreatetruecolor($bg_w, $bg_h);

    	$bg_color = imagecolorallocate($bg_im, 255, 255, 255);

    	$radius = 16;
    	$arc_w = $radius*2;
    	$arc_h = $radius*2;

    	$lt_cx = $radius;
    	$lt_cy = $radius;
    	imagefilledarc($bg_im, $lt_cx, $lt_cy, $arc_w, $arc_h, 180, 270, $bg_color, IMG_ARC_PIE);

    	$rt_cx = $bg_w - $radius;
    	$rt_cy = $radius;
    	imagefilledarc($bg_im, $rt_cx, $rt_cy, $arc_w, $arc_h, 270, 360, $bg_color, IMG_ARC_PIE);

    	$lb_cx = $radius;
    	$lb_cy = $bg_h - $radius;
    	imagefilledarc($bg_im, $lb_cx, $lb_cy, $arc_w, $arc_h, 90, 180, $bg_color, IMG_ARC_PIE);

    	$rb_cx = $bg_w - $radius;
    	$rb_cy = $bg_h - $radius;
    	imagefilledarc($bg_im, $rb_cx, $rb_cy, $arc_w, $arc_h, 0, 90, $bg_color, IMG_ARC_PIE);

    	// 使用横纵两个实心矩形填充画弧留下的空隙区域
    	$x_box_w = $bg_w;
    	$x_box_h = $bg_h-$radius*2;
    	$x_box_im = imagecreatetruecolor($x_box_w, $x_box_h);
    	$x_box_bg_color = imagecolorallocate($x_box_im, 255, 255, 255);
    	imagefilledrectangle($x_box_im, 0, 0, $x_box_w-1, $x_box_h-1, $x_box_bg_color);

    	$y_box_w = $bg_w-$radius*2;
    	$y_box_h = $bg_h;
    	$y_box_im = imagecreatetruecolor($y_box_w, $y_box_h);
    	$y_box_bg_color = imagecolorallocate($y_box_im, 255, 255, 255);
    	imagefilledrectangle($y_box_im, 0, 0, $y_box_w-1, $y_box_h-1, $y_box_bg_color);

    	imagecopyresampled($bg_im, $x_box_im, 0, $radius, 0, 0, $bg_w, $x_box_h, $x_box_w, $x_box_h);
    	imagecopyresampled($bg_im, $y_box_im, $radius, 0, 0, 0, $y_box_w, $bg_w, $y_box_w, $y_box_h);


    	imagecopyresampled($bg_im, $im_temp, $border_w, $border_w, 0, 0, $bg_w-$border_w*2, $bg_h-$border_w*2, $logo_width, $logo_height);

    	// 合并后的logo大小
    	$logo_width_new  = $qr_width * $logo_size;
    	$logo_height_new = $qr_height * $logo_size;

    	// logo粘贴位置
    	$from_pos = ($qr_width - $logo_width_new) / 2;

    	// 合并粘贴
    	imagecopyresampled($qr_im, $bg_im, $from_pos, $from_pos, 0, 0, $logo_width_new, $logo_height_new, $bg_w, $bg_h);

    	if (empty($outfile_nm)) {
    		return imagepng($qr_im);
    	} else {
    		return imagepng($qr_im, sys_get_temp_dir().$outfile_nm);
    	}
    	
	}


	
}

?>