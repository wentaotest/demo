<?php namespace June\apps\services;

class UtilService {
	static private $_inst;

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new UtilService();
		}

		return self::$_inst;
	}

	/**
	 * 根据配置上传文件
	 *
	 * @param  array $file 全局变量$_FILES
	 * @return mixed $fid  文件存储路径
	 *
	 * @author 王文韬
     */
	public function uploadByConfig($file) {
		if (C('web_conf', 'fs_proxy.on')) {
            // 代理上传
            $ch = curl_init();

            $tmp_name = $file['name'];
            $tmp_file = $file['tmp_name'];
            $tmp_type = $file['type'];

            $img = array(
                'video_file' => new \CurlFile($tmp_file, $tmp_type, $tmp_name),
                'dfs_type'   => 'FastDFS'
            );

            curl_setopt($ch, CURLOPT_URL, C('web_conf', 'fs_proxy.url'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $img);

            $output = curl_exec($ch);
            $res = json_decode($output, true);
            $fid = $res['result']['fid'];
            curl_close($ch);
        } else {
            // 直接上传到server
            $s_upload = UploadService::getInstance();
            $upload_ok = $s_upload->upload($file);

            if ($upload_ok) {
                $fid = $s_upload->moveFileToFastDfs();
            }
        }

        if (!$fid) $fid = null;

        return $fid;
	}

    /**
     * 生成视频封面
     *
     * @param  string $tch_nm 教师姓名
     * @param  string $crs_nm 教师所教课程
     * @return mixed          结果
     *
     * @author 王文韬
     */
    public function generateVideoCover($tch_nm, $crs_nm) {
        if (empty($tch_nm) || empty($crs_nm)) {
            return false;
        }

        // 画布的尺寸
        $w = 330;
        $h = 250;
        // 文字尺寸 最好为3的倍数 (单位 磅)
        $font_size = 21;
        // 文字尺寸(单位 像素)
        $font_px = $font_size * 4 / 3;


        $img = imagecreatetruecolor($w, $h);
        // 设置颜色
        $bg_color   = imagecolorallocate($img, 111, 111, 111);
        $fore_color = imagecolorallocate($img, 255, 255, 255);

        // 填充背景色
        imagefill($img, 0, 0, $bg_color);

        $text1 = $tch_nm.'老师';
        $text2 = $crs_nm.'课';

        // 绘制文字的参数 左边距/上边距
        $cnt = mb_strlen($text1, 'utf-8');
        $x1 = ($w - $cnt * 28) / 2;
        $x2 = ($w - mb_strlen($text2, 'utf-8') * $font_px) / 2;
        $y1 = 120;
        $y2 = 170;

        // 字体
        $ttf = 'statics/font/msyh.ttf';

        // 写入文字
        preg_match_all('/./u', $text1, $text);
        for($i = 0; $i < $cnt; $i++) {
            imagettftext($img, $font_size, 0, $x1, $y1, $fore_color, $ttf, $text[0][$i]);
            $x1 += 28;
        }

        imagettftext($img, $font_size, 0, $x2, $y2, $fore_color, $ttf, $text2);

        $img_name = md5(time()).'.jpg';
        $dir = sys_get_temp_dir ();
        $file_dir = $dir.'/'.$img_name;

        //输出图片
        imagejpeg($img, $file_dir);

        //销毁图像资源
        imagedestroy($img);

        $img_info = getimagesize($file_dir);

        $file = array(
            'size'     => filesize($file_dir),
            'name'     => $img_name,
            'tmp_name' => $file_dir,
            'type'     => $img_info['mime']
        );

        return $file;
    }
}

?>