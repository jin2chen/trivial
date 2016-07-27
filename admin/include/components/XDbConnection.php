<?php
/**
 * Db connection.
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: XDbConnection.php 172 2011-09-28 02:30:26Z mole1230 $
 */
class XDbConnection extends CDbConnection
{
	/**
	 * 数据库唯一键冲突代码
	 *
	 * @var int
	 */
	const DB_DUPLICATE = 23000;
}