<?php namespace June\apps\services;

class UploadService {
	static private $_inst;

	private $_path = "./uploads";         // 上传文件保存的路径
    private $_allow_types = array('apk', 'png', 'jpg', 'jpeg', 'bmp', 'gif', 'docx', 'mp4'); // 设置限制上传文件的类型
    private $_max_size = 100000000;         // 限制文件上传大小（字节）
    private $_is_rand_name = true;        // 是否随机重命名，默认为false（不随机重命名）
  
    private $_origin_name = null;    // 原文件名
    private $_tmp_file_name = null;  // 临时文件名
    private $_file_type = null;      // 文件类型(文件后缀)
    private $_file_size = null;      // 文件大小
    private $_new_file_name = null;  // 新文件名
    private $_thumbnail = null;      // 缩略图文件
    private $_watermark = './statics/image/water_logo.png';
    private $_error_code = 0; // 错误编码
    private $_error_msg = ""; // 错误信息
    
    private $_mime_types = array(
    		"323" => "text/h323",
    		"acx" => "application/internet-property-stream",
    		"ai" => "application/postscript",
    		"aif" => "audio/x-aiff",
    		"aifc" => "audio/x-aiff",
    		"aiff" => "audio/x-aiff",
    		"asf" => "video/x-ms-asf",
    		"asr" => "video/x-ms-asf",
    		"asx" => "video/x-ms-asf",
    		"au" => "audio/basic",
    		"avi" => "video/x-msvideo",
    		"axs" => "application/olescript",
    		"bas" => "text/plain",
    		"bcpio" => "application/x-bcpio",
    		"bin" => "application/octet-stream",
    		"bmp" => "image/bmp",
    		"c" => "text/plain",
    		"cat" => "application/vnd.ms-pkiseccat",
    		"cdf" => "application/x-cdf",
    		"cer" => "application/x-x509-ca-cert",
    		"class" => "application/octet-stream",
    		"clp" => "application/x-msclip",
    		"cmx" => "image/x-cmx",
    		"cod" => "image/cis-cod",
    		"cpio" => "application/x-cpio",
    		"crd" => "application/x-mscardfile",
    		"crl" => "application/pkix-crl",
    		"crt" => "application/x-x509-ca-cert",
    		"csh" => "application/x-csh",
    		"css" => "text/css",
    		"dcr" => "application/x-director",
    		"der" => "application/x-x509-ca-cert",
    		"dir" => "application/x-director",
    		"dll" => "application/x-msdownload",
    		"dms" => "application/octet-stream",
    		"doc" => "application/msword",
    		"dot" => "application/msword",
    		"dvi" => "application/x-dvi",
    		"dxr" => "application/x-director",
    		"eps" => "application/postscript",
    		"etx" => "text/x-setext",
    		"evy" => "application/envoy",
    		"exe" => "application/octet-stream",
    		"fif" => "application/fractals",
    		"flr" => "x-world/x-vrml",
    		"gif" => "image/gif",
    		"gtar" => "application/x-gtar",
    		"gz" => "application/x-gzip",
    		"h" => "text/plain",
    		"hdf" => "application/x-hdf",
    		"hlp" => "application/winhlp",
    		"hqx" => "application/mac-binhex40",
    		"hta" => "application/hta",
    		"htc" => "text/x-component",
    		"htm" => "text/html",
    		"html" => "text/html",
    		"htt" => "text/webviewhtml",
    		"ico" => "image/x-icon",
    		"ief" => "image/ief",
    		"iii" => "application/x-iphone",
    		"ins" => "application/x-internet-signup",
    		"isp" => "application/x-internet-signup",
    		"jfif" => "image/pjpeg",
    		"jpe" => "image/jpeg",
    		"jpeg" => "image/jpeg",
    		"jpg" => "image/jpeg",
    		"js" => "application/x-javascript",
    		"latex" => "application/x-latex",
    		"lha" => "application/octet-stream",
    		"lsf" => "video/x-la-asf",
    		"lsx" => "video/x-la-asf",
    		"lzh" => "application/octet-stream",
    		"m13" => "application/x-msmediaview",
    		"m14" => "application/x-msmediaview",
    		"m3u" => "audio/x-mpegurl",
    		"man" => "application/x-troff-man",
    		"mdb" => "application/x-msaccess",
    		"me" => "application/x-troff-me",
    		"mht" => "message/rfc822",
    		"mhtml" => "message/rfc822",
    		"mid" => "audio/mid",
    		"mny" => "application/x-msmoney",
    		"mov" => "video/quicktime",
    		"movie" => "video/x-sgi-movie",
    		"mp2" => "video/mpeg",
    		"mp3" => "audio/mpeg",
    		"mpa" => "video/mpeg",
    		"mpe" => "video/mpeg",
    		"mpeg" => "video/mpeg",
    		"mpg" => "video/mpeg",
    		"mpp" => "application/vnd.ms-project",
    		"mpv2" => "video/mpeg",
    		"ms" => "application/x-troff-ms",
    		"mvb" => "application/x-msmediaview",
    		"nws" => "message/rfc822",
    		"oda" => "application/oda",
    		"p10" => "application/pkcs10",
    		"p12" => "application/x-pkcs12",
    		"p7b" => "application/x-pkcs7-certificates",
    		"p7c" => "application/x-pkcs7-mime",
    		"p7m" => "application/x-pkcs7-mime",
    		"p7r" => "application/x-pkcs7-certreqresp",
    		"p7s" => "application/x-pkcs7-signature",
    		"pbm" => "image/x-portable-bitmap",
    		"pdf" => "application/pdf",
    		"pfx" => "application/x-pkcs12",
    		"pgm" => "image/x-portable-graymap",
    		"pko" => "application/ynd.ms-pkipko",
    		"pma" => "application/x-perfmon",
    		"pmc" => "application/x-perfmon",
    		"pml" => "application/x-perfmon",
    		"pmr" => "application/x-perfmon",
    		"pmw" => "application/x-perfmon",
    		"pnm" => "image/x-portable-anymap",
    		"png" => "image/png",
    		"pot" => "application/vnd.ms-powerpoint",
    		"ppm" => "image/x-portable-pixmap",
    		"pps" => "application/vnd.ms-powerpoint",
    		"ppt" => "application/vnd.ms-powerpoint",
    		"prf" => "application/pics-rules",
    		"ps" => "application/postscript",
    		"pub" => "application/x-mspublisher",
    		"qt" => "video/quicktime",
    		"ra" => "audio/x-pn-realaudio",
    		"ram" => "audio/x-pn-realaudio",
    		"ras" => "image/x-cmu-raster",
    		"rgb" => "image/x-rgb",
    		"rmi" => "audio/mid",
    		"roff" => "application/x-troff",
    		"rtf" => "application/rtf",
    		"rtx" => "text/richtext",
    		"scd" => "application/x-msschedule",
    		"sct" => "text/scriptlet",
    		"setpay" => "application/set-payment-initiation",
    		"setreg" => "application/set-registration-initiation",
    		"sh" => "application/x-sh",
    		"shar" => "application/x-shar",
    		"sit" => "application/x-stuffit",
    		"snd" => "audio/basic",
    		"spc" => "application/x-pkcs7-certificates",
    		"spl" => "application/futuresplash",
    		"src" => "application/x-wais-source",
    		"sst" => "application/vnd.ms-pkicertstore",
    		"stl" => "application/vnd.ms-pkistl",
    		"stm" => "text/html",
    		"svg" => "image/svg+xml",
    		"sv4cpio" => "application/x-sv4cpio",
    		"sv4crc" => "application/x-sv4crc",
    		"t" => "application/x-troff",
    		"tar" => "application/x-tar",
    		"tcl" => "application/x-tcl",
    		"tex" => "application/x-tex",
    		"texi" => "application/x-texinfo",
    		"texinfo" => "application/x-texinfo",
    		"tgz" => "application/x-compressed",
    		"tif" => "image/tiff",
    		"tiff" => "image/tiff",
    		"tr" => "application/x-troff",
    		"trm" => "application/x-msterminal",
    		"tsv" => "text/tab-separated-values",
    		"txt" => "text/plain",
    		"uls" => "text/iuls",
    		"ustar" => "application/x-ustar",
    		"vcf" => "text/x-vcard",
    		"vrml" => "x-world/x-vrml",
    		"wav" => "audio/x-wav",
    		"wcm" => "application/vnd.ms-works",
    		"wdb" => "application/vnd.ms-works",
    		"wks" => "application/vnd.ms-works",
    		"wmf" => "application/x-msmetafile",
    		"wps" => "application/vnd.ms-works",
    		"wri" => "application/x-mswrite",
    		"wrl" => "x-world/x-vrml",
    		"wrz" => "x-world/x-vrml",
    		"xaf" => "x-world/x-vrml",
    		"xbm" => "image/x-xbitmap",
    		"xla" => "application/vnd.ms-excel",
    		"xlc" => "application/vnd.ms-excel",
    		"xlm" => "application/vnd.ms-excel",
    		"xls" => "application/vnd.ms-excel",
    		"xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    		"xlt" => "application/vnd.ms-excel",
    		"xlw" => "application/vnd.ms-excel",
    		"xof" => "x-world/x-vrml",
    		"xpm" => "image/x-xpixmap",
    		"xwd" => "image/x-xwindowdump",
    		"z" => "application/x-compress",
    		"zip" => "application/zip",
    		"apk" => "application/vnd.android.package-archive");

    private function __construct($options) {
		if (!empty($options) && is_array($options)) {
			foreach ($options as $key => $value) {
				$this->setOption($key, $value);
			}
		}
	}

	static public function getInstance($options=array()) {
		if (empty(self::$_inst)) {
			self::$_inst = new UploadService($options);
		}

		return self::$_inst;
	}
	
	/**
	 * 设置参数值
	 * 
	 * @param string $key
	 * @param mixed $val
	 * 
	 * @author 安佰胜
	 */
	public function setOption($key, $val) {
		$this->$key = $val;
	}
	
	/**
	 * 获取参数值
	 * 
	 * @param string $key
	 * @return mixed
	 * 
	 * @author 安佰胜
	 */
	public function getOption($key) {
		return $this->$key;
	}
	
	/**
	 * 获取错误代码
	 * 
	 * @return integer
	 * 
	 * @author 安佰胜
	 */
	public function getErrorCode() {
		return $this->_error_code;
	}
	
	/**
	 * 获取错误信息
	 * 
	 * @return string
	 * 
	 * @author 安佰胜
	 */
	public function getErrorMsg() {
		return $this->_error_msg;
	}
	
	/**
	 * 获取新的文件名
	 * 
	 * @return string
	 * 
	 * @author 安佰胜
	 */
	public function getNewFileName() {
		return $this->_new_file_name;
	}
	
	/**
	 * 上传文件
	 * 
	 * @param $_FILES $file 文件
	 * @return boolean
	 * 
	 * @author 安佰胜
	 */
	public function upload($file) {
		$name  = isset($file['name']) ? $file['name'] : null;
		$size  = isset($file['size']) ? $file['size'] : null;
		$error = isset($file['error']) ? $file['error'] : null;
		$tmp_name = isset($file['tmp_name']) ? $file['tmp_name'] : null;

		if (!empty($error)) {
			$this->setOption('_error_code', $error);
			$this->setOption('_error_msg', $this->_errorMsg());

			return false;
		}
		
		$name_pieces = explode('.', $name);
		$file_ext_nm = end($name_pieces);
		
		$this->setOption('_file_type', strtolower($file_ext_nm));
		$this->setOption('_file_size', $size);

		if ($this->_checkFileSize() && $this->_checkFileType()) {
			$this->setOption('_origin_name', $name);
			$this->setOption('_tmp_file_name', $tmp_name);
			$this->setOption('_new_file_name', $this->_genNewFileName());

			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 生成新的文件名
	 * 
	 * @return string 文件名
	 * 
	 * @author 安佰胜
	 */
	private function _genNewFileName() {
		$file_name = date("YmdHis") . "_" . rand(100, 999);
		return $file_name . '.' . $this->_file_type;
	}
	
	/**
	 * 获取输出的错误信息
	 * 
	 * @return string
	 * 
	 * @author 安佰胜
	 */
	private function _errorMsg() {
		$msg = "{$this->_origin_name}上传失败！原因：";

		switch ($this->_error_code) {
			case 1:
				$msg .= "文件超出php允许的上传文件大小限制。";
				break;
			case 2:
				$msg .= "文件超出服务器允许的上传文件大小限制。";
				break;
			case 3:
				$msg .= "文件上传不完整。";
				break;
			case 4:
				$msg .= "没有文件被上传。";
				break;
			case -1:
				$msg .= "非法文件类型。";
				break;
			case -2:
				$msg .= "文件超出系统允许的上传文件大小限制。";
				break;
			case -3:
				$msg .= "文件上传后移动失败。";
				break;
			case -4:
				$msg .= "文件上传目录创建失败。";
				break;
			case -5:
				$msg .= "文件上传目录未指定。";
				break;
			case -6:
				$msg .= "图片缩放失败。";
				break;
			case -7:
				$msg .= "图片添加水印失败。";
				break;
			default:
				$msg .= "未知错误。";
				break;
		}

		return $msg;
	}
	
	/**
	 * 检查文件路径是否合法
	 * 
	 * @return boolean
	 * 
	 * @author 安佰胜
	 */
	private function checkFilePath() {
		if(empty($this->_path)){
			$this->setOption('_error_code', -5);
			$this->setOption('_error_msg', $this->_errorMsg());

			return false;
		}

		if (!file_exists($this->_path) || !is_writable($this->_path)) {
			if (!@mkdir($this->_path, 0755)) {
				$this->setOption('_error_code', -4);
				$this->setOption('_error_msg', $this->_errorMsg());

				return false;
			}
		}

		return true;
    }

	/**
	 * 检查文件大小是否合法
	 * 
	 * @return boolean
	 * 
	 * @author 安佰胜
	 */
	private function _checkFileSize() {
		if ($this->_file_size > $this->_max_size) {
			$this->setOption('_error_code', -2);
			$this->setOption('_error_msg', $this->_errorMsg());

			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 检查文件类型是否合法
	 * 
	 * @return boolean
	 * 
	 * @author 安佰胜
	 */
	private function _checkFileType() {
		if (in_array(strtolower($this->_file_type), $this->_allow_types)) {
			return true;
		} else {
			$this->setOption('_error_code', -1);
			$this->setOption('_error_msg', $this->_errorMsg());

			return false;
		}
	}
	
	/**
	 * 将上传的文件从临时目录转移到uploads目录下
	 * 
	 * @return boolean
	 * 
	 * @author 安佰胜
	 */
	public function moveFile() {
		if(!$this->_error_code) {
			$path = rtrim($this->_path, '/').'/';
			$path .= $this->_new_file_name;

			if (@move_uploaded_file($this->_tmp_file_name, $path)) {
				return true;
			} else {
				$this->setOption('_error_code', -3);
				$this->setOption('_error_msg', $this->_errorMsg());

				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * 将上传的文件转移到Mongo GridFS中，同时将原有文件从上传目录中移除
	 * 
	 * @author 安佰胜
	 */
	public function moveFileToGridFs() {
		$s_gridfs = GridFsService::getInstance();
		
		$file_path = $this->_tmp_file_name;
		
		$metadata = array('mime' => $this->_getMime(),
						  'filename' => $this->_new_file_name,
						  'originName' => $this->_origin_name,
		);
		
		$fid = $s_gridfs->saveFile($file_path, $metadata);
		
		// 从文件夹中删除原文件
		unlink($file_path);
		
		return $fid->{'$id'};
	}
	
	/**
	 * 将上传的文件转移到FastDFS中，同时将原有文件从上传目录中移除
	 * 
	 * @param array   $inch         图片压缩的尺寸
	 * @param boolean $is_watermark 是否添加水印
	 * 
	 * @author 安佰胜
	 */
	public function moveFileToFastDfs($inch = null, $is_watermark = false) {
		$s_fdfs = FastDfsService::getInstance();
		
		if(is_array($inch)) {
			$w = $inch[0];
			$h = $inch[1];
			$this->_tmp_file_name = $this->zoomImg($w, $h);
		}
		
		if($is_watermark) {
			$zoom_img = $this->_thumbnail || $this->_tmp_file_name;
			$this->_tmp_file_name = $this->watermark();
		}
		
		$file_path = $this->_tmp_file_name;
		
		$fid = $s_fdfs->uploadFile($file_path, $this->_file_type);
		
		// 从文件夹中删除原文件
		unlink($file_path);
		
		return $fid;
	}
	
	/**
	 * 将上传的图片压缩
	 * 
	 * @param number $dst_w 缩放的目的宽度
	 * @param number $dst_h 缩放的目的高度
	 * @return mixed $new_img 缩放后的文件路径
	 * 
	 * @author 王文韬
	 */
	public function zoomImg($dst_w = 540, $dst_h = null) {
		$this->_path = sys_get_temp_dir();
		$this->moveFile();
		$ext = $this->_file_type;
		
		if ($ext == 'jpg' || $ext == 'jpeg') {
			$imagecreatefrom = 'imagecreatefromjpeg';
			$save_img        = 'imagejpeg';
		} else {
			$imagecreatefrom = 'imagecreatefrom' . $ext;
			$save_img        = 'image' . $ext;
		}
		
		
		$img = $this->_path.'/'.$this->_new_file_name;
		// 老的图片资源
		$oImg = $imagecreatefrom($img);
		
		// 获取老图片的宽度和高度
		list($o_w, $o_h) = getimagesize($img);
		
		if ($o_w < $dst_w) {
			$final_w = $o_w;
			$final_h = $o_h;
		} else {
			// 按照给定的宽度进行等比缩放
			$final_w = $dst_w;
			$final_h = intval($o_h * $dst_w / $o_w);
		}
		
		// 新的画布资源
		$nImg = imagecreatetruecolor($final_w, $final_h);
		imagealphablending($nImg, false);//这里很重要,意思是不合并颜色,直接用$image图像颜色替换,包括透明色;
		imagesavealpha($nImg, true);//这里很重要,意思是不要丢了$portrait图像的透明色;
		
		// 进行缩放
		imagecopyresampled($nImg, $oImg, 0, 0, 0, 0, $final_w, $final_h, $o_w, $o_h);
		
		// 保存图像
		$new_img = sys_get_temp_dir() . '/' . time() . rand(0, 1000) . $this->_new_file_name;
		$res = $save_img($nImg, $new_img);
		
		// 销毁图像资源
		imagedestroy($oImg);
		imagedestroy($nImg);
		if (!$res) {
			$this->setOption('_error_code', -6);
			$this->setOption('_error_msg', $this->_errorMsg());
			return false;
		}
		$this->_thumbnail = $new_img;
		// 删除原图
		unlink($img);
		
		return $new_img;
	}
	
	/**
	 * 添加图片水印
	 * 
	 * @param mixed $watermark 水印图片完整路径
	 * @return mixed           新的图片路径
	 * 
	 * @author 王文韬
	 */
	public function watermark() {
		$watermark = $this->_watermark;
		
		if (!is_file($watermark)) {
			$this->setOption('_error_code', -7);
			$this->setOption('_error_msg', $this->_errorMsg());
			
			return false;
		}
		$target = $this->_thumbnail;
		if (empty($target)) {
			$target = $this->_tmp_file_name;
			$this->_path = sys_get_temp_dir();
			$img = $this->_path.'/'.$this->_new_file_name;
			
		}
		
		list($src_w, $src_h) = getimagesize($watermark);
		list($dst_w, $dst_h, $dst_type) = getimagesize($target);

		$left = $dst_w - $src_w - 10;
		$top = $dst_h - $src_h - 10;
		if ($left <= $src_w || $top <= $src_h) {
			$this->setOption('_error_code', -7);
			$this->setOption('_error_msg', $this->_errorMsg());
			return false;
		}
		
		$r_target = imagecreatefromstring(file_get_contents($target));
		$r_watermark = imagecreatefromstring(file_get_contents($watermark));
		imagecopy($r_target, $r_watermark, $left, $top, 0, 0, $src_w, $src_h);
		switch ($dst_type) {
			case 1://GIF
				$save_img = 'imagegif';
				break;
			case 2://JPG
				$save_img = 'imagejpeg';
				break;
			case 3://PNG
				$save_img = 'imagepng';
				break;
			default:
				break;
		}
		
		$new_img = sys_get_temp_dir() . '/' . time() . rand(0, 1000) . $this->_new_file_name;
		$res = $save_img($r_target, $new_img);
		
		imagedestroy($r_target);
		imagedestroy($r_watermark);
		if (!$res) {
			$this->setOption('_error_code', -7);
			$this->setOption('_error_msg', $this->_errorMsg());
			return false;
		}
		// 删除原图
		unlink($target);
		
		return $new_img;
	}
	
	/**
	 * 获取上传文件的mime信息
	 * 
	 * @return string mime
	 * 
	 * @author 安佰胜
	 */
	public function _getMime() {
		$file_path  = rtrim($this->_path, '/').'/';
		$file_path .= $this->_new_file_name;
		
		$pieces = explode('.', $this->_new_file_name);
		$ext_name = end($pieces);
		
		if (isset($this->_mime_types[$ext_name])) {
			return $this->_mime_types[$ext_name];
		} else {
			return 'unknown';
		}
	}
	
	/**
	 * 断点上传文件
	 * 
	 * @author 王索
	 */
	public function UploadFile(){
	
	    if (!empty($_GET['resumableChunkNumber']) || !empty($_POST['resumableChunkNumber'])){
			$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];//请求方法，GET或POST
			$uploads_dir    = $this -> _path;//上传文件保存临时目录
	        if($REQUEST_METHOD == "GET")
	        {
	            if(count($_GET)>0)
	            {
					$chunkNumber = $_GET['resumableChunkNumber'];//第几块
					$chunkSize   = $_GET['resumableChunkSize'];	 //块大小
					$totalSize   = $_GET['resumableTotalSize'];	 //总大小
					$identifier  = $_GET['resumableIdentifier'];  //文件唯一标识
					$filename    = iconv ( 'UTF-8', 'GB2312', $_GET ['resumableFilename'] ); //文件名编码转成GB2312
	                //判断文件块是否存在
	                if($this->validateRequest($chunkNumber, $chunkSize, $totalSize, $identifier, $filename)=='valid')
	                {
	                    //拼装每块文件的文件名
	                    $chunkFilename = $uploads_dir.'/'.$identifier.'/'.$filename.'.part'.$chunkNumber;
	                    {
	                        if(file_exists($chunkFilename)){
	                            echo "found";
	                        } else {
	                            header("HTTP/1.0 404 Not Found");
	                            echo "not_found";
	                        }
	                    }
	                }
	                else
	                {
	                    header("HTTP/1.0 404 Not Found");
	                    echo "not_found";
	                }}
	        }
	
	        //组合文件块
	        if($REQUEST_METHOD == "POST"){
	            if(count($_POST)>0)
	            {$post = $_POST;
				$resumableFilename    = iconv ( 'UTF-8', 'GB2312', $_POST ['resumableFilename'] );//文件名
				$resumableIdentifier  = $_POST['resumableIdentifier'];	 //唯一标识
				$resumableChunkNumber = $_POST['resumableChunkNumber'];//第几块
				$resumableTotalSize   = $_POST['resumableTotalSize'];	 //总大小
				$resumableChunkSize   = $_POST['resumableChunkSize'];	 //块大小
	            if (!empty($_FILES)) foreach ($_FILES as $file) {
	                // 检查错误状态
	                if ($file['error'] != 0) {
	                    continue;
	                }
	                 
	                $temp_dir = $uploads_dir.'/'.$resumableIdentifier;
	                $dest_file = $temp_dir.'/'.$resumableFilename.'.part'.$resumableChunkNumber;//块文件的命名格式
	                	
	                if (!is_dir($temp_dir)) {
	                    mkdir($temp_dir, 0777, true);
	                }
	                // 将上传块文件移到到自定义临时目录
	                if (move_uploaded_file($file['tmp_name'], $dest_file)) {
	                    // 检查是否上传完毕,并组合文件块
	                    $this->createFileFromChunks($temp_dir, $resumableFilename,$resumableChunkSize, $resumableTotalSize);
	                }
	            }
	            }
	        }
	    }
	}
	
	/**
	 * 断点上传功能相关联的方法  判断参数是否合法有效
	 * 
	 * @param unknown $chunkNumber
	 * @param unknown $chunkSize
	 * @param unknown $totalSize
	 * @param unknown $identifier
	 * @param unknown $filename
	 * @param string $fileSize
	 * @return string
	 * 
	 * @author 王索
	 */
	private function validateRequest ($chunkNumber, $chunkSize, $totalSize, $identifier, $filename, $fileSize=''){
	
	    if ($chunkNumber==0 || $chunkSize==0 || $totalSize==0 || $identifier==0 || $filename=="") {
	        return 'non_resumable_request';
	    }
	    $numberOfChunks = max(floor($totalSize/($chunkSize*1.0)), 1);
	    if ($chunkNumber>$numberOfChunks) {
	        return 'invalid_resumable_request1';
	    }
	
	    if($fileSize!="") {
	        if($chunkNumber<$numberOfChunks && $fileSize!=$chunkSize) {
	            // The chunk in the POST request isn't the correct size
	            return 'invalid_resumable_request3';
	        }
	        if($numberOfChunks>1 && $chunkNumber==$numberOfChunks && $fileSize!=(($totalSize%$chunkSize)+$chunkSize)) {
	            // The chunks in the POST is the last one, and the fil is not the correct size
	            return 'invalid_resumable_request4';
	        }
	        if($numberOfChunks==1 && $fileSize!=$totalSize) {
	            // The file is only a single chunk, and the data size does not fit
	            return 'invalid_resumable_request5';
	        }
	    }
	    return 'valid';
	}
	
	/**
	 *
	 * 断点上传关联方法 块文件合成一个文件  Check if all the parts exist, and
	 * gather all the parts of the file together
	 * @param string $dir - the temporary directory holding all the parts of the file
	 * @param string $fileName - the original file name
	 * @param string $chunkSize - each chunk size (in bytes)
	 * @param string $totalSize - original file size (in bytes)
	 * 
	 * @author 王索
	 */
	private function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize) {
	    $uploads_dir = $this -> _path;//上传文件保存临时目录
	    $total_files = 0;// 总文件块数
	    foreach(scandir($temp_dir) as $file) {
	        if (stripos($file, $fileName) !== false) {
	            $total_files++;
	        }
	    }
	
	    //根据文件块的数量算出已上传文件的大小，判断是否已上传完成
	    if ($total_files * $chunkSize >=  ($totalSize - $chunkSize + 1)) {
	
	        // 组合文件
	        if (($fp = fopen($uploads_dir.'/'.$fileName, 'w')) !== false) {
	            for ($i=1; $i<=$total_files; $i++) {
	                fwrite($fp, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
	            }
	            fclose($fp);
	            echo json_encode(array('ok' => true, 'msg' => 'create file from chunks'));
	        } else {
	            return false;
	        }
	         
	        //删除文件块
	        if (rename($temp_dir, $temp_dir.'_UNUSED')) {
	            $this->rrmdir($temp_dir.'_UNUSED');
	        } else {
	            $this->rrmdir($temp_dir);
	        }
	        exit;
	    }
	}
	
	/**
	 *断点上传相关联的方法 删除目录
	 *
	 * Delete a directory RECURSIVELY
	 * @param string $dir - directory path
	 * 
	 * @author 王索
	 */
	private function rrmdir($dir) {
	    if (is_dir($dir)) {
	        $objects = scandir($dir);
	        foreach ($objects as $object) {
	            if ($object != "." && $object != "..") {
	                if (filetype($dir . "/" . $object) == "dir") {
	                    rrmdir($dir . "/" . $object);
	                } else {
	                    unlink($dir . "/" . $object);
	                }
	            }
	        }
	        reset($objects);
	        rmdir($dir);
	    }
	}
}
?>