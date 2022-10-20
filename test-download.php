<?php
require_once __DIR__ . '/RepoDownloader.php';

_print("DOWNLOAD...\n");
$options = [
	'user'   => null, //github username
	'token'  => null, //your ACCESS_TOKEN
	'repo'   => null, //your REPO_NAME
	//'saveAs' => null,
	//'branch' => null,
	'progress' => '_prog',
];
$dl = new RepoDownloader();
$result = $dl -> download($options, $error);
if (!$result) _print("ERROR: $error\n");
else _print("DONE: " . json_encode($result) . "\n");
exit;

//progress handler
function _prog($size, $total, $done){
	static $prev;
	if ($prev > $size) $prev = 0;
	if (!$done && $prev && ($size - $prev) < ((1048 ** 2)/2)) return;
	$prev = $size;
	if ($done && !$total) $total = $size;
	$s = round($size/1048, 3);
	$tmp = 'progress' . ($done ? ' done' : ''); 
	if ($t = $total ? round($total/1048, 3) : 0) _print(sprintf("- %s: %s/%s kb - %s\n", $tmp, $s, $t, round($size/$total*100, 3) . '%'));
	else _print(sprintf("- %s: %s kb - indeterminate\n", $tmp, $s));
}

//print buffer
function _print($val){
	static $start;
	if (!$start){
		$start = 1;
		while (ob_get_level() && ob_end_flush());
		ob_start();
	}
	echo $val;
	ob_flush();
	flush();
}