<?php
/**
 * This is the model class for table "admin_role".
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: AdminRole.php 172 2011-09-28 02:30:26Z mole1230 $
 *
 * The followings are the available columns in table 'admin_role':
 * @property integer $id
 * @property integer $admin_id
 * @property string $admin_path
 * @property string $honor
 * @property string $acls
 * @property string $create_time
 * @property string $update_time
 */
class AdminRole extends CActiveRecord
{	
	const SUPER_ROLE_ID = 1;
	
	/**
	 * Returns the static model of the specified AR class.
	 * 
	 * @return AdminRole the static model class
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
		return 'admin_role';
	}
	
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('honor', 'required'),
			array('admin_id', 'numerical', 'integerOnly' => true),
			array('admin_path', 'length', 'max' => 255),
			array('honor', 'length', 'max' => 15),
			array('create_time, update_time', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, admin_id, admin_path, honor, acls, create_time, update_time', 'safe', 'on' => 'search'),
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
			'admin' => array(self::BELONGS_TO, 'Admin', 'admin_id')
		);
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'admin_id' => '创建者',
			'admin_path' => '父路径',
			'honor' => '头衔',
			'acls' => '权限',
			'create_time' => '创建时间',
			'update_time' => '更新时间',
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
			$criteria->compare('admin_path', "%,{$user->id},%", true, 'AND', false);
		}
		$criteria->compare('id', $this->id);
		$criteria->compare('admin_id', $this->admin_id);
		$criteria->compare('honor', $this->honor, true);
		$criteria->compare('acls', $this->acls, true);
		$criteria->compare('create_time', $this->create_time, true);
		$criteria->compare('update_time', $this->update_time, true);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
	
	protected function beforeSave()
	{
		if (parent::beforeSave()) {
			if ($this->isNewRecord) {
				$admin = Admin::model()->findByPk(Yii::app()->user->id);
				
				$this->admin_id = $admin->id;
				$this->admin_path = $admin->parent_path . $admin->id . ',';
				$this->create_time = TIMEDATE;
				$this->update_time = TIMEDATE;
			} else {
				$this->update_time = TIMEDATE;
			}
			
			return true;
		}
		
		return true;
	}
}
