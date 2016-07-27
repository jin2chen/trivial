<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: AdminController.php 205 2011-12-17 14:23:54Z mole1230 $
 */
class AdminController extends XController
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
			$update = array('update', 'delete', 'reset', 'ban');
			$create = array('create');
			if (in_array($act, $update)) {
				$id = (int) $_GET['id'];
				$model = Admin::model()->findByPk($id);
				if (!$model || strpos($model->parent_path, ',' . $this->user->id . ',') === false) {
					throw new CHttpException(403);
				}
			} else if (in_array($act, $create) && isset($_POST['Admin'])) {
				$roleId = (int) $_POST['Admin']['admin_role_id'];
				$roleModel = AdminRole::model()->findByPk($roleId);
				if (!$roleModel || strpos($roleModel->admin_path, ',' . $this->user->admin_role_id . ',') === false) {
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
		$model = new Admin('search');
		$model->unsetAttributes();
		if (isset($_GET['Admin'])) {
			$model->attributes = $_GET['Admin'];
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
		$model = new Admin();

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		
		if (isset($_POST['Admin'])) {
			$model->attributes = $_POST['Admin'];
			if ($model->save()) {
				$this->info(Yii::t('code', 'A00001'));
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		
		$this->render('create', array(
			'model' => $model
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

		if (isset($_POST['Admin'])) {
			$model->attributes = $_POST['Admin'];
			if ($model->save()) {
				$this->info(Yii::t('code', 'A00001'));
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		
		$this->render('update', array(
			'model' => $model
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
	
	public function actionReset($id)
	{
		if ($this->user->id == $id) {
			$this->redirect(array('site/reset'));
		}
		$model = new PwdForm();
		$model->id = $id;
		if (isset($_POST['PwdForm'])) {
			$model->attributes = $_POST['PwdForm'];
			if ($model->save()) {
				$this->info(Yii::t('code', 'A00001'));
				$this->refresh();
			}
		}
		
		$this->render('reset', array(
			'model' => $model
		));
	}
	
	public function actionPick($id, $val)
	{
		
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * 
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model = Admin::model()->findByPk($id);
		if ($model === null) {
			throw new CHttpException(404, 'The requested page does not exist.');
		}
		return $model;
	}
	
	public function getRoles()
	{
		$model = AdminRole::model()->search();
		$model->setTotalItemCount(99999);
		return CHtml::listData($model->getData(), 'id', 'honor');
	}

	/**
	 * Performs the AJAX validation.
	 * 
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'admin-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
