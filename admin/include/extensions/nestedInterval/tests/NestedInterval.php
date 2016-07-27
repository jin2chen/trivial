<?php
/* SVN FILE: $Id: NestedInterval.php 199 2011-12-14 10:13:49Z mole1230 $ */
/**
 * Gallery model class file
 *
 * @copyright   Copyright 2009 PBM Web Development - All Rights Reserved
 * @package     galleries
 * @since       V1.0.0
 * @version     $Revision: 4 $
 * @updatedby   $LastChangedBy: cyates $
 * @lastupdated $Date: 2011-12-03 16:22:03 +0000 (Sat, 03 Dec 2011) $
 */

/**
 * Gallery model class
 *
 * @package galleries
 */
class NestedInterval extends CActiveRecord {
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'nested_intervals';
	}

	public function behaviors() {
		return array(
			'nestedInterval'=>'galleries.components.behaviors.NestedIntervalBehavior'
		);
	}
}