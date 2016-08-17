<?php
class CaptureLottoJob {

	// 为job运行初始化环境
	public function setUp() {

	}

	// 运行job
	public function perform() {
        fwrite(STDOUT, 'Start job! -> ');

		sleep(1);

		$src = 'http://f.apiplus.cn/cqssc.json';
		$src .= '?_='.time();
		$json = file_get_contents(urldecode($src));
		$json = json_decode($json);

		for ($i = 0; $i < count($json->data); $i++) {
			$p = $json ->data[$i]->expect;
			echo "开奖期号：".substr($p,0,8).'-'.substr($p,-3,3);
			echo "开奖号码：".$json ->data[$i]->opencode;
			echo "开奖时间：".$json ->data[$i]->opentime;
		}

		fwrite(STDOUT, 'Job ended!' . PHP_EOL);
	}

	// 移除job运行所需环境
	public function tearDown() {

	}
}