<?php
/**
 * @author     mole<mole1230@gmail.com>
 * @version    $Id: tidy-space.php 287 2012-09-25 09:03:27Z mole1230 $
 */

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];

if ($argc < 2) {
	echo 'Usage: php tidy.php <filename>';
	exit(1);
}

$filename = $argv[1];
$lines = file($filename, FILE_IGNORE_NEW_LINES);
$startHeader = $endHeader = false;
$startBody = $endBody = false;

foreach ($lines as &$line) {
	if (!$startHeader) {
		if (preg_match('/^\s*<head>/', $line)) {
			$startHeader = true;
		}
		$line = preg_replace('/^\s{2}/', '', $line);
	} else if ($startHeader && !$endHeader) {
		if (preg_match('/^\s*<\/head>/', $line)) {
			$endHeader = true;
		}
		$line = preg_replace('/^\s{2,4}/', '', $line);
	} else if ($endHeader && !$startBody) {
		if (preg_match('/^\s*<body[^>]*>/', $line)) {
			$startBody = true;
		}
		$line = preg_replace('/^\s{2,4}/', '', $line);
	} else if ($startBody && !$endBody) {
		if (preg_match('/^\s*<\/body>/', $line)) {
			$endBody = true;
		}
		$line = preg_replace('/^\s{2,4}/', '', $line);
		if (preg_match('/(?:<a[^>]*>[^<]*<\/a>\s*){2,}/', $line)) {
			$match = array();
			$space = '';
			if (preg_match('/^\s+/', $line, $match)) {
				$space = $match[0];
			}
			$tmp = trim($line);
			$tmp = explode('</a>', $tmp);
			foreach ($tmp as $k => $a) {
				if (!empty($a)) {
					$tmp[$k] = $space . trim($a);
				} else {
					unset($tmp[$k]);
				}
			}
			$line = implode("</a>\n", $tmp) . '</a>';
		}
	} else {
		$line = preg_replace('/^\s{2}/', '', $line);
	}
}

$content = implode("\n", $lines);
$content = preg_replace_callback('/(<title>)([^>]*)(<\/title>)/', '_tidyTitle', $content, 1);
$content = preg_replace_callback('/(<\/head>)([^>]*)(<body[^>]*>)/', '_addLf', $content, 1);
file_put_contents($filename, $content);

function _tidyTitle($match)
{
	return $match[1] . trim($match[2]) . $match[3];
}

function _addLf($match)
{
	return $match[1] . "\n\n" . $match[3];
}
