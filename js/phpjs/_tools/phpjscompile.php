#!/usr/bin/php -q
<?php
error_reporting(E_ALL);
require_once "PHPJS_Library/PHPJS/Library.php";

// Check for CLI
if ((php_sapi_name() != 'cli')) {
    die("CLI Only");
}

$dirFunctions = realpath(dirname(__FILE__)."/..")."/functions";
$dirCompile   = realpath(dirname(__FILE__)."/..")."";
$PHPJS_Compiler_Shell = new PHPJS_Library_Compiler_Shell($dirFunctions, $dirCompile);

$items = array(
	'http_build_query', 'parse_str', 'parse_url', 'urlencode', 'urldecode',
	'strtr'
);
$cFuncs = $PHPJS_Compiler_Shell->Functions;
$collect = array();
foreach ($items as $item) {
	$collect = array_merge($collect, $cFuncs[$item]->getDependencies());
	$collect[] = $item;
}
$collect = array_unique($collect);
foreach ($collect as $i => $item) {
	$collect[$i] = 'function::' . $item;
}
#$PHPJS_Compiler_Shell->setSelection("category::math");
#$PHPJS_Compiler_Shell->setSelection("all");
$PHPJS_Compiler_Shell->setSelection($collect);


// Set flags
$flags = 0;
$flags = $flags | PHPJS_Library_Compiler::COMPILE_NAMESPACED;
$flags = $flags | PHPJS_Library_Compiler::COMPILE_MINFIED;
#$flags = $flags | PHPJS_Library_Compiler::COMPILE_PACKED;

//echo $PHPJS_Compiler_Shell->genLicense(1.61);


echo $PHPJS_Compiler_Shell->compile($flags);

?>