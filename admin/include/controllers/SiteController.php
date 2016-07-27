<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: SiteController.php 198 2011-12-13 10:05:51Z mole1230 $
 */
class SiteController extends XController
{	
	public function actions()
	{
		return array(
			'captcha' => array(
				'class' => 'application.controllers.actions.XCaptchaAction',
				'padding' => 0,
				'width' => 60,
				'height' => 20,
				'backColor' => 0X1167A0,
				'foreColor' => 0XFFFFFF,
				'transparent' => true
			)
		);
	}
	
	public function actionIndex()
	{
		if ($this->user->getIsGuest()) {
			$this->redirect($this->user->loginUrl);
		}
		
		$this->renderPartial('index');
	}
	
	public function actionMenu()
	{
		$this->renderPartial('menu', array(
			'menu' => $this->assembleMenu($this->user->admin_role_id, $this->user->acls)
		));
	}
	
	public function actionTop()
	{
		$this->renderPartial('top');
	}
	
	public function actionHome()
	{
		$this->actionProfile();
	}

	public function actionLogin()
	{
		if (!$this->user->getIsGuest()) {
			$this->redirect('/');
		}
		
		$model = new LoginForm();
		if ($this->request->getIsPostRequest()) {
			$model->attributes = $_POST;
			if ($model->validate() && $model->login()) {
				$this->redirect('/');
			} else {
				$this->error($model->getErrors());
			}
		}
		
		$this->renderPartial('login', array('model' => $model));
	}
	
	public function actionLogout()
	{
		$this->user->logout();
		$this->redirect($this->user->loginUrl);
	}
	
	public function actionHelp()
	{
		$this->render('help');
	}
	
	public function actionReset()
	{
		$model = new PwdForm('self');
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
	
	public function actionProfile()
	{
		$this->render('profile', array(
			'menu' => $this->assembleMenu($this->user->admin_role_id, $this->user->acls)
		));
	}
}
