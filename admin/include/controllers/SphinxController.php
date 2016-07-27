<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: SphinxController.php 206 2011-12-23 10:10:15Z mole1230 $
 */
class SphinxController extends XController
{
	public function actionIndex()
	{
		$this->render('index');
	}
	
	public function actionScript()
	{
		sleep(10);
	}
	
//	public function actionIndex()
//	{
//		/* @var $sh DGSphinxSearch */
//		$sh = Yii::app()->sphinx->SetMatchMode(SPH_MATCH_PHRASE);
//		$sh->select('hot_area_id, district_id')
//			->from('idx_house')
//			->groupby(array('field' => 'district_id', 'mode' => SPH_GROUPBY_ATTR, 'order' => '@group desc'))
//		//	->groupby(array('field' => 'hot_area_id', 'mode' => SPH_GROUPBY_ATTR, 'order' => '@group asc'))
//			->limit(0, 30);
//		$res = $sh->searchRaw();
//		var_dump($res);
//	}
}