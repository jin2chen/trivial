<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: LoginForm.php 172 2011-09-28 02:30:26Z mole1230 $
 */
class LoginForm extends CFormModel
{
	public $username;
	public $password;
	public $captcha;
	
	private $_identity;
	
	public function rules()
	{
		return array(
			array('username', 'required', 'message' => Yii::t('code', 'B10001')),
			array('password', 'required', 'message' => Yii::t('code', 'B10002')),
			//array('captcha', 'captcha', 'message' => Yii::t('code', 'B10003')),
			array('password', 'authenticate', 'skipOnError' => true),
		);
	}
	
	public function authenticate($attribute, $params)
	{
		$this->_identity = new XUserIdentity($this->username, $this->password);
		if (!$this->_identity->authenticate()) {
			$this->addError('password', Yii::t('code', 'B10004'));
		}
	}
	
	public function login()
	{
		if ($this->_identity === null) {
			$this->_identity = new XUserIdentity($this->username, $this->password);
			$this->_identity->authenticate();
		}
		
		if ($this->_identity->errorCode == XUserIdentity::ERROR_NONE) {
			/* @var $user CWebUser */
			$user = Yii::app()->getUser();
			$user->login($this->_identity);
			return true;
		}
		
		return false;
	}
}