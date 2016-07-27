<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: PwdForm.php 172 2011-09-28 02:30:26Z mole1230 $
 */
class PwdForm extends CFormModel
{
	public $old;
	public $new;
	public $cnf;
	
	private $_id;
	
	/**
	 * @var Admin
	 */
	private $_admin;
	
	public function rules()
	{
		return array(
			array('old', 'required', 'on' => 'self'),
			array('old', 'check', 'skipOnError' => true, 'on' => 'self'),
			array('new, cnf', 'required'),
			array('new', 'compare', 'compareAttribute' => 'cnf')
		);
	}
	
	public function check()
	{
		$admin = $this->getAdmin();
		if ($admin->hashPassword($this->old) !== $admin->password) {
			$this->addError('old', Yii::t('code', 'B10005'));
		}
	}
	
	public function save()
	{
		if ($this->validate()) {
			$admin = $this->getAdmin();
			$admin->password = $admin->hashPassword($this->new);
			$admin->save(false);
			return true;
		}
		
		return false;
	}
	
	public function getAdmin()
	{
		if ($this->_admin === null) {
			$this->_admin = Admin::model()->findByPk($this->getId());
		}
		
		return $this->_admin;
	}
	
	public function getId()
	{
		if ($this->_id === null) {
			$this->_id = Yii::app()->user->id;
		}
		return $this->_id;
	}
	
	public function setId($id)
	{
		$this->_id = $id;
	}
	
	public function attributeLabels()
	{
		return array(
			'old' => '原始密码',
			'new' => '新  密  码',
			'cnf' => '确认密码'
		);
	}
}