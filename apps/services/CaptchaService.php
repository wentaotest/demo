<?php namespace June\apps\services;

class CaptchaService {
	static private $_inst;

	private function __construct() {
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new CaptchaService();
		}

		return self::$_inst;
	}

	/**
	 * 获取并显示验证码图片
	 * 
	 * @param integer $type 验证码类型 1-数字 2-数字加字母 3-算数验证码
	 * @return captcha_image
	 *
	 * @author An Baisheng
	 */
	public function getCaptchaImage($type, $w = 120, $h = 30) {

		$code = $this->_genCaptchaCode($type);

		$code_im = imagecreatetruecolor($w, $h);
		$bg_color = imagecolorallocate($code_im, 250, 250, 250);
		$black = imagecolorallocate($code_im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
		$gray = imagecolorallocate($code_im, 200, 200, 200);
		imagefill($code_im, 0, 0, $bg_color);

		// 添加随机干扰点
		for ($i = 0; $i < 80; $i++) {
			imagesetpixel($code_im, rand(0, $w), rand(0, $h), $black);
		}

		// 添加两条随机虚线
		$style = array ($black, $black, $black, $black, $black,
						$gray, $gray, $gray, $gray, $gray);
		imagesetstyle($code_im, $style);
		$y1 = rand(0, $h);
		$y2 = rand(0, $h);
		$y3 = rand(0, $h);
		$y4 = rand(0, $h);
		imageline($code_im, 0, $y1, $w, $y3, IMG_COLOR_STYLED);
		imageline($code_im, 0, $y2, $w, $y4, IMG_COLOR_STYLED);

		// 添加验证码内容，字符在一定范围内随机波动
		$font = 5;

		$font_h = imagefontheight($font);
		$font_w = imagefontwidth($font);
		
		$padding_y = ($h - $font_h) / 2;

		$code_len = strlen($code);
		$space_w = $w / ($code_len+1);
		$pos_x = rand($font_w, $space_w);
		for ($i = 0; $i < $code_len; $i++) {
			$pos_y = rand($padding_y, $h-$font_h-5);

			imagestring($code_im, $font, $pos_x, $pos_y, $code[$i], $black);

			$pos_x = ($i+1)* $space_w + rand($font_w, $space_w);
		}

		header("Content-type: image/png");
		imagepng($code_im);
		imagedestroy($code_im);
	}

	/**
	 * 生成验证码内容
	 * 
	 * @param integer $type 验证码类型 1-数字 2-数字加字母 3-算数验证码
	 * @return string $code
	 *
	 * @author An Baisheng
	 */
	private function _genCaptchaCode($type) {
		$code = "";

		switch ($type) {
			case 1://纯数字
				for ($i=0; $i<4; $i++) {
					$code .= rand(0, 9);
				}

				$_SESSION['june_captcha_code'] = $code;
				break;
			case 2://字母加数字，但去掉了易混淆的0、o、1、l等
				$str = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
				for ($i=0; $i<4; $i++) {
					$code .= $str[mt_rand(0, strlen($str)-1)];
				}

				$_SESSION['june_captcha_code'] = $code;
				break;
			case 3: //算数，只算加法
				$num1 = rand(1, 20);
				$num2 = rand(1, 20);
				$operator = '+';
				$code .= "$num1 $operator $num2 = ?";

				$_SESSION['june_captcha_code'] = $num1 + $num2;
				break;
			default:
				for ($i=0; $i<4; $i++) {
					$code .= rand(0, 9);
				}

				$_SESSION['june_captcha_code'] = $code;
				break;
		}

		return $code;
	}

	/**
	 * 检查用户输入的验证码是否正确
	 * 
	 * @param string $input 用户输入
	 * @return boolean 是否正确
	 *
	 * @author An Baisheng
	 */
	public function check($input) {
		$input = trim($input);
		$input = strtolower($input);

		if ($input == $_SESSION['june_captcha_code']) {
			return true;
		} else {
			return false;
		}
	}
}
?>