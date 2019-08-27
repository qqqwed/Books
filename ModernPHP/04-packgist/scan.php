<?php
/**
 * Description: 扫描URL
 * Created by Martini
 * DateTime: 2019-06-28 23:36
 */
require '../vendor/autoload.php';

// 实例 Guzzle HTTP客户端

//$client = new \GuzzleHttp\Client();
//
// 打开并迭代处理CSV
//$csv = \League\Csv\Reader::createFromString($argv[1]);
//var_dump($csv);
//foreach ($csv as $csvRow) {
//	try {
//		 发送HTTP OPTIONS 请求
//		$httpResponse = $client->options($csvRow[0]);
//
//		 检查HTTP响应码
//		if ($httpResponse->getStatusCode() >= 400) {
//			throw new \Exception();
//		}
//	} catch (\Exception $e) {
//		 把死链发给标准输出
//		echo $csvRow[0] . PHP_EOL;
//	}
//}

$urls = [
	'http://www.baidu.com',
	'http://www.baidusaasa.com',
];
$scanner = new \Oreilly\ModernPHP\Url\Scanner($urls);
print_r($scanner->getInvalidUrls());