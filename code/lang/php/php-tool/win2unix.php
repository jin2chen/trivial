<?php
/**
 * Windows format TO Unix format. 
 *
 * @author     mole<mole1230@gmail.com>
 * @version    $Id: win2unix.php 8 2010-10-19 12:45:57Z mole1230 $
 */

$path = 'E:\www\jiaju\designer.jiaju.sina.com.cn\trunk\js';	// dir
$exts = '/\.(php|html|htm|as|xml|js|css)$/i';	// suffix

if (!is_dir($path)) {
	trigger_error("{$path} is not a dir.", E_USER_ERROR);
}

$files = rScandir($path, $exts);
foreach ($files as $file) {
	$content = file_get_contents($file);
	$content = str_replace("\r\n", "\n", $content);
	file_put_contents($file, $content);
	echo $file, "\n";
}

function rScandir($path, $exts)
{
	static $res = array();
	
	$path = rtrim($path, '/\\');
	$files = scandir($path);
	foreach ($files as $file) {
		$filepath = $path . DIRECTORY_SEPARATOR . $file;
		if (is_dir($filepath) && $file[0] != '.') {
			rScandir($filepath, $exts);
		} else {
			if (preg_match($exts, $file)) {
				$res[] = $filepath;
			}
		}
	}
	
	return $res;
}
