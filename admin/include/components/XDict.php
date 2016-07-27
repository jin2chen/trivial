<?php
/**
 * Dictionary.
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: XDict.php 200 2011-12-14 10:14:28Z mole1230 $
 */
class XDict
{
	const PICK_UP = 0;
	const PICK_DOWN = 1;
	public static $pickLabels = array(
		self::PICK_UP => '推荐',
		self::PICK_DOWN => '取消推荐'
	);
}