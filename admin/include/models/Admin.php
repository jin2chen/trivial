<?php
/**
 * This is the model class for table "admin".
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: Admin.php 172 2011-09-28 02:30:26Z mole1230 $
 *
 * The followings are the available columns in table 'admin':
 * @property integer $id
 * @property integer $admin_role_id
 * @property integer $parent_id
 * @property string $parent_path
 * @property string $username
 * @property string $password
 * @property string $realname
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property string $last_time
 * @property string $last_ip
 */
class Admin extends CActiveRecord
{		
	/**
	 * Returns the static model of the specified AR class.
	 * 
	 * @return Admin the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
	
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'admin';
	}
	
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('admin_role_id, username, realname', 'required'),
			array('username', 'unique'),
			array('admin_role_id, parent_id, status', 'numerical', 'integerOnly' => true),
			array('parent_path', 'length', 'max' => 255),
			array('username, realname', 'length', 'max' => 25),
			array('password', 'length', 'max' => 33),
			array('create_time, update_time, last_time, last_ip', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, admin_role_id, parent_id, parent_path, username, password, realname, status, create_time, update_time, last_time, last_ip', 'safe', 'on' => 'search'),
		);
	}
	
	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'adminRole' => array(self::BELONGS_TO, 'AdminRole', 'admin_role_id'),
			'parent' => array(self::BELONGS_TO, 'Admin', 'parent_id')
		);
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'admin_role_id' => '角色',
			'parent_id' => '父ID',
			'parent_path' => '父路径',
			'username' => '用户名',
			'password' => '密码',
			'realname' => '真实姓名',
			'status' => '状态',
			'create_time' => '创建时间',
			'update_time' => '修改时间',
			'last_time' => '最后登录时间',
			'last_ip' => '最后登录IP'
		);
	}
	
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * 
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria = new CDbCriteria();
		$user = Yii::app()->user;
		if ($user->admin_role_id != AdminRole::SUPER_ROLE_ID) {
			$criteria->compare('parent_path', "%,{$user->id},%", true, 'AND', false);
		}
		$criteria->compare('id', $this->id);
		$criteria->compare('admin_role_id', $this->admin_role_id);
		$criteria->compare('parent_id', $this->parent_id);
		$criteria->compare('username', $this->username, true);
		$criteria->compare('password', $this->password, true);
		$criteria->compare('realname', $this->realname, true);
		$criteria->compare('status', $this->status);
		$criteria->compare('create_time', $this->create_time, true);
		$criteria->compare('update_time', $this->update_time, true);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
	
	public function validatePassword($password)
	{
		return $this->hashPassword($password) === $this->password;
	}
	
	public function hashPassword($password)
	{
		return md5($password);
	}
	
	protected function beforeSave()
	{
		if (parent::beforeSave()) {
			if ($this->isNewRecord) {
				$admin = Admin::model()->findByPk(Yii::app()->user->id);
				
				$this->create_time = TIMEDATE;
				$this->update_time = TIMEDATE;
				$this->password = $this->hashPassword($this->username);
				$this->parent_id = $admin->id;
				$this->parent_path = $admin->parent_path . $admin->id . ',';
			} else {
				$this->update_time = TIMEDATE;
				if ($this->getScenario() == 'password') {
					$this->password = $this->hashPassword($this->password);
				}
			}
			
			return true;
		}
		
		return true;
	}
}
