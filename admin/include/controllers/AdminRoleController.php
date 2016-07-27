<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: AdminRoleController.php 172 2011-09-28 02:30:26Z mole1230 $
 */
class AdminRoleController extends XController
{	
	public function filters()
	{
		$filters = parent::filters();
		$filters[] = 'authorize';
		
		return $filters;
	}
	
	/**
	 * 对进行数据表更新操作进用户等级控制
	 * 
	 * @param CFilterChain $filterChain
	 */
	public function filterAuthorize($filterChain)
	{
		if ($this->user->admin_role_id != AdminRole::SUPER_ROLE_ID) {
			$act = $this->getAction()->getId();
			$update = array('update', 'delete');
			$create = array('create');
			if (in_array($act, $update)) {
				$id = (int) $_GET['id'];
				$model = AdminRole::model()->findByPk($id);
				if (!$model || strpos($model->admin_path, ',' . $this->user->admin_role_id . ',') === false) {
					throw new CHttpException(403);
				}
			}
		}
		
		$filterChain->run();
	}
	
	/**
	 * Manages all models.
	 */
	public function actionIndex()
	{
		$model = new AdminRole('search');
		$model->unsetAttributes();
		if (isset($_GET['AdminRole'])) {
			$model->attributes = $_GET['AdminRole'];
		}
		
		$this->render('index', array(
			'model' => $model
		));
	}
	
	/**
	 * Displays a particular model.
	 * 
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view', array(
			'model' => $this->loadModel($id)
		));
	}
	
	/**
	 * Creates a new model.
	 * 
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model = new AdminRole();
		
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		
		if (isset($_POST['AdminRole'])) {
			$model->attributes = $_POST['AdminRole'];
			$model->acls = json_encode($this->_collectAcls());

			if ($model->save()) {
				$this->info(Yii::t('code', 'A00001'));
				$this->refresh();
			}
		}
		
		$this->render('create', array(
			'model' => $model,
			'menu' => $this->assembleMenu($this->user->admin_role_id, $this->user->acls),
			'acls' => json_decode($model->acls, true)
		));
	}
	
	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * 
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model = $this->loadModel($id);
		
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if (isset($_POST['AdminRole'])) {
			$model->attributes = $_POST['AdminRole'];
			$model->acls = json_encode($this->_collectAcls());
			if ($model->save()) {
				if ($this->user->admin_role_id == $id) {
					$this->user->setState('acls', json_decode($model->acls, true));
				}
				$this->info(Yii::t('code', 'A00001'));
			}
		}
		
		$this->render('update', array(
			'model' => $model,
			'menu' => $this->assembleMenu($this->user->admin_role_id, $this->user->acls),
			'acls' => json_decode($model->acls, true)
		));
	}
	
	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * 
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		// not allow delete
		throw new CHttpException(403);
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * 
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model = AdminRole::model()->findByPk($id);
		if ($model === null) {
			throw new CHttpException(404, 'The requested page does not exist.');
		}
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * 
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'admin-role-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
	
	/**
	 * collect acl
	 * 
	 * @return array
	 */
	private function _collectAcls()
	{
		$ctrls = $acts = array();
		$acls = $this->user->acls;
		
		if (is_array(@$_POST['AdminRole']['ctrls'])) {
			foreach ($_POST['AdminRole']['ctrls'] as $ctrl) {
				$tmp = explode('/', $ctrl);
				if (count($tmp) == 2) {
					$mod	= $tmp[0];
					$ctrl	= $tmp[1];
					if (@$this->menu[$mod][$ctrl] 
					&& ($this->user->admin_role_id == AdminRole::SUPER_ROLE_ID || isset($acls['ctrls'][$ctrl]))) {
						$ctrls[$ctrl] = 1;
					}
				}
			}
		}
		
		if (is_array(@$_POST['AdminRole']['acts'])) {
			foreach ($_POST['AdminRole']['acts'] as $act) {
				$tmp = explode('/', $act);
				if (count($tmp) == 3) {
					$mod	= $tmp[0];
					$ctrl	= $tmp[1];
					$act	= $tmp[2];
					if (@$this->menu[$mod][$ctrl]['actions'][$act]
					&& ($this->user->admin_role_id == AdminRole::SUPER_ROLE_ID || isset($acls['acts'][$ctrl][$act]))) {
						$acts[$ctrl][$act] = 1;
					}
				}
			}
		}

		return array('ctrls' => $ctrls, 'acts' => $acts);
	}
	
}
