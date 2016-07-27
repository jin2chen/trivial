<?php
if (!isset($argv)) {
	echo "Please run at command\n";
}

if ($argc < 3) {
	$basename = basename($argv[0]);
	echo "Usage: php {$basename} listfile targetdir\n";
	exit(1);
}

$listfile  = $argv[1];
$targetdir = rtrim($argv[2], '/\\');

if (!file_exists($listfile)) {
	echo "File {$listfile} is not exist.\n";
	exit(1);
}

if (!is_dir($targetdir) && !@mkdir($targetdir, 0777, true)) {
	echo "mkdir {$targetdir} failure.\n";
	exit(1);
}

$xml = simplexml_load_file($listfile);
$items = $xml->File;

if (!is_array($items)) {
	$items = array($items);
}

$fails = array();
foreach ($xml->File as $item) {
	$sfile = iconv('utf-8', 'cp936', implode(DIRECTORY_SEPARATOR, array(rtrim($item->FilePath, '/\\'), $item->FileName)));
	$dfile = iconv('utf-8', 'cp936', implode(DIRECTORY_SEPARATOR, array($targetdir, $item->FileName)));

	if (file_exists($sfile)) {
		if (copy($sfile, $dfile)) {
			echo "Copy {$sfile}\n";
			continue;
		}
	}
	
	$fails[] = $sfile;
}
if (!empty($fails)) {
	echo "--failure---------------\n";
	echo implode("\n", $fails);
}