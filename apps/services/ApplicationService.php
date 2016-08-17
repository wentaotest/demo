<?php namespace June\apps\services;

class ApplicationService {

	public function uploadPkgFile() {

	}

	/**
	 * 使用ApkParser解析apk的信息，纯PHP实现，但解析res资源过程太慢
	 * 
	 * @param string $apk_file_path 待解析的apk文件路径
	 * @return array $apk_info
	 * 
	 * @author An Baisheng
	 */
	public function parseApkInfo($apk_file_path) {
		include __ROOT__ . '/libs/Plugins/ApkParser/autoload.php';
		$apk = new \ApkParser\Parser($apk_file_path);

		$manifest = $apk->getManifest();
		$permissions = $manifest->getPermissions();

		header("Content-Type:text/html; charset=utf-8");

		echo '<pre>';
		echo "Package Name      : " . $manifest->getPackageName() . "" . PHP_EOL;
		echo "Version           : " . $manifest->getVersionName() . " (" . $manifest->getVersionCode() . ")" . PHP_EOL;
		echo "Min Sdk Level     : " . $manifest->getMinSdkLevel() . "" . PHP_EOL;
		echo "Min Sdk Platform  : " . $manifest->getMinSdk()->platform . "" . PHP_EOL;
		echo PHP_EOL;
		echo "------------- 权限列表 -------------" . PHP_EOL;

		// find max length to print more pretty.
		$perm_keys = array_keys($permissions);
		$perm_key_lengths = array_map(function ($perm) {
		    return strlen($perm);
		}, $perm_keys);
		$max_length = max($perm_key_lengths);

		foreach ($permissions as $perm => $detail) {
		    echo str_pad($perm, $max_length + 4, ' ') . "=> " . $detail['description'] . " " . PHP_EOL;
		    echo str_pad('', $max_length - 5, ' ') . ' cost    =>  ' . ($detail['flags']['cost'] ? 'true' : 'false') . " " . PHP_EOL;
		    echo str_pad('', $max_length - 5, ' ') . ' warning =>  ' . ($detail['flags']['warning'] ? 'true' : 'false') . " " . PHP_EOL;
		    echo str_pad('', $max_length - 5, ' ') . ' danger  =>  ' . ($detail['flags']['danger'] ? 'true' : 'false') . " " . PHP_EOL;

		}


		echo PHP_EOL;
		echo "------------- Activities  -------------" . PHP_EOL;
		foreach ($apk->getManifest()->getApplication()->activities as $activity) {
		    echo $activity->name . ($activity->isLauncher ? ' (Launcher)' : null) . PHP_EOL;
		}
	}

	/**
	 * 使用androguard解析apk的信息，需要安装python环境
	 * 
	 * @param string $apk_file_path 待解析的apk文件路径
	 * @param string $parse_out_path apk解压后的文件输出路径（输出文件包含apk基本信息json文件及apk图标png文件）
	 * @return array $apk_info
	 *
	 * @author An Baisheng
	 */
	public function parseApk($apk_file_path, $parse_out_path = "./runtime/apk_parse_out") {
		if (PATH_SEPARATOR == ':') {
		    // linux 操作系统
		    exec("python ./libs/Tools/apktool/androparser.py " . $apk_file_path . ' ' . $parse_out_path, $echo_out);
		} else {
		    // windows 操作系统
		    exec("python .\\libs\\Tools\\apktool\\androparser.py " . $apk_file_path . ' ' . $parse_out_path, $echo_out);
		}
		if (empty($echo_out)) {
			return false;
		}
		$json = preg_replace("/\': u\'/", "': '", $echo_out[0]);
        $json = preg_replace("/'size': (\d+)L/i", "'size': $1", $json);
		$json = str_replace("L}", '}', $json);
		$json = str_replace("'", '"', $json);
		$json = str_replace("\\", '/', $json);

		$apk_info = (array)json_decode($json);

		$apk_info['pkg_size'] = filesize($apk_file_path);
		$apk_info['icon_file'] = $parse_out_path . "/" . basename($apk_file_path, '.apk') . ".png";

		return $apk_info;
	}

	/**
	 * 获取页面datatable所需的数据
	 *
	 */
	public function getAppsDataTables() {

	}
}

?>