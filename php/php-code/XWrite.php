<?php
/**
 * Description...
 *
 * @author     mole <mole1230@gmail.com>
 * @version    $Id: XWrite.php 104 2011-03-24 02:38:38Z mole1230 $
 */
function filePutContents($filename, $content)
{
	$fp = fopen($filename, 'a+');
	$isLock = false;
	$res = false;
	do {
		if (flock($fp, LOCK_EX)) {
			$res = fwrite($fp, $content);
			flock($fp, LOCK_UN);
		} else {
			usleep(1000);
		}
	} while (!$isLock);
	
	return $res;
}

