<?php
if(empty($argv[1])) {
	die('Specify the name of a job to add. e.g, php queue.php PHP_Job');
}

require_once __DIR__ . '/../autoloader.php';

date_default_timezone_set('GMT');
Resque::setBackend('10.232.71.102:6379');

$args = array(
	'time' => time(),
	'array' => array(
		'test' => 'test',
	),
);

$jobId = Resque::enqueue($argv[1], $argv[2], $args, true);
echo "Queued job ".$jobId."\n\n";