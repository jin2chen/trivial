<?php
/**
 * 用户登录认证与会话记录存储
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: XUserIdentity.php 185 2011-11-06 14:05:10Z mole1230 $
 */
class XUserIdentity extends CUserIdentity
{
	private $_id;
	
	public function authenticate()
	{
		$admin = Admin::model()->find('username = ?', array($this->username));
		if ($admin === null) {
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		} else if (!$admin->validatePassword($this->password)) {
			$this->errorCode = self::ERROR_PASSWORD_INVALID;
		} else {
			$this->_id = $admin->id;
			$this->username = $admin->username;
			$this->errorCode = self::ERROR_NONE;
			
			$role = $admin->adminRole;
			$states = array();
			$states['admin_role_id'] = $admin->admin_role_id;
			$states['username'] = $admin->username;
			$states['realname'] = $admin->realname;
			$states['honor'] = $role->honor;
			$states['acls'] = ($acls = json_decode($role->acls, true)) == false ? array() : $acls;
			$this->setPersistentStates($states);
			
			// record last login
			try {
				$admin->last_time = TIMEDATE;
				$admin->last_ip = Fn::getIp();
				$admin->save(false);
			} catch (Exception $e) {}
		}
		
		return $this->errorCode === self::ERROR_NONE;
	}
	
	public function getId()
	{
		return $this->_id;
	}
}