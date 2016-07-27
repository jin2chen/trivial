<?php
/**
 * XController 是 CController 扩展类，以满足应用程序需求
 * 所有 controller 类必须继承此类
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: XController.php 204 2011-12-17 13:57:30Z mole1230 $
 */

class XController extends CController
{
	const ERROR = 'error';
	const INFO = 'info';
	
	/**
	 * 默认布局 文件
	 *
	 * @var string
	 */
	public $layout = '//layouts/main';

	/**
	 * 当前页面包屑数组，参考{@link CBreadcrumbs::links}
	 *
	 * @var array
	 */
	public $breadcrumbs = array();
	
	/**
	 * 用户信息
	 * 
	 * @var CWebUser
	 */
	public $user;
	
	/**
	 * @var CHttpRequest
	 */
	public $request;
	
	/**
	 * @var array
	 */
	private $_jsFiles = array();
	
	/**
	 * @var array
	 */	
	private $_cssFiles = array();
	
	/**
	 * @var array
	 */
	private $_menu;
	
	/**
	 * @var string
	 */
	private $_pageTitle;

	/**
	 * 初始化 CWebUser 对象。
	 *
	 * @param string $id
	 * @param string $module
	 */
	public function __construct($id, $module = null)
	{
		parent::__construct($id, $module);
		$this->user = Yii::app()->getUser();
		$this->request = Yii::app()->getRequest();
	}
	
	/**
	 * @see parent::filters()
	 */
	public function filters()
	{
		return array(
			'requireLogin - login, logout, captcha',
			'accessControl - login, logout, captcha'
		);
	}
	
	/**
	 * 强制登陆
	 * 
	 * @param CFilterChain $filterChain
	 */
	public function filterRequireLogin($filterChain)
	{
		if ($this->user->getIsGuest()) {
			if ($this->request->getIsAjaxRequest()) {
				throw new CHttpException(401);
			} else {
				$this->redirect($this->user->loginUrl);
			}
		}
		
		$filterChain->run();
	}
	
	/**
	 * 权限控制
	 * 
	 * @param CFilterChain $filterChain
	 */
	public function filterAccessControl($filterChain)
	{
		$acls = $this->user->acls;
		$ctrl = $this->getId();
		$act  = $this->getAction()->getId();
		
		$excludeCtrls = array('site');
		$excludeActs = $this->excludeActs();
		if ($this->user->admin_role_id != AdminRole::SUPER_ROLE_ID
		&& !in_array($ctrl, $excludeCtrls) 
		&& !in_array($act, $excludeActs) 
		&& !isset($acls['ctrls'][$ctrl])
		&& !isset($acls['acts'][$act])) {
			throw new CHttpException(403);
		}
		
		$filterChain->run();
	}
	
	/**
	 * 哪些 action 排除在权限控制之外
	 * 
	 * @return array
	 */
	public function excludeActs()
	{
		return array();
	}

	/**
	 * 生成资源URL地址，方便移值和资源分离。
	 *
	 * @param string $asset
	 * @param bool $isVersion 是否显示版本号，主要用于资源更新立即生效
	 * @return string
	 */
	public function asset($asset, $isVersion = false)
	{
		if (YII_DEBUG) {
			return $asset;
		}
		return preg_replace('/\.js$/i', '.min.js', $asset);
	}

	/**
	 * 统一AJAX返回数据类型。
	 *
	 * @param string $code 返回代码，以  A 开头表示成功，而以其它字母开头均表示失败。
	 * @param array $data
	 * @param bool $return
	 */
	public function responseJson($code = 'A00001', $data = array() , $return = false)
	{
		$json = json_encode(array(
			'code' => $code,
			'data' => $data
		));
		if ($return) {
			return $json;
		}

		echo $json;
		Yii::app()->end();
	}
	
	/**
	 * 添加JS文件
	 * 
	 * @param string $file
	 * @param array $options
	 * @return XController
	 */
	public function addJsFile($file, $options = array())
	{
		$file = $this->asset($file);
		$this->_jsFiles[$file] = $options;
		return $this;
	}
	
	/**
	 * 添加CSS文件
	 * 
	 * @param string $file
	 * @param array $options
	 * @return XController
	 */	
	public function addCssFile($file, $options = array())
	{
		$file = $this->asset($file);
		$this->_cssFiles[$file] = $options;
		return $this;
	}
	
	/**
	 * 生成 JS html
	 * 
	 * @return string
	 */
	public function renderJsFiles()
	{
		$html = array();
		foreach ($this->_jsFiles as $src => $options) {
			if (is_array($options) && $options !== array()) {
				$tmp = array();
				foreach ($options as $key => $val) {
					$tmp[] = $key . '="' . $val . '"';
				}
				$html[] =  '<script src="' . $src . '" ' . implode(' ', $tmp) . '></script>';
			} else {
				$html[] = '<script src="' . $src . '"></script>'; 
			}
		}
		
		return implode("\n", $html) . ($html === array() ? '' : "\n");;
	}
	
	/**
	 * 生成 CSS html
	 * 
	 * @return string
	 */
	public function renderCssFiles()
	{
		$html = array();
		foreach ($this->_cssFiles as $src => $options) {
			if (is_array($options) && $options !== array()) {
				$tmp = array();
				foreach ($options as $key => $val) {
					$tmp[] = $key . '="' . $val . '"';
				}
				$html[] =  '<link rel="stylesheet" href="' . $src . '" ' . implode(' ', $tmp) . ' />';
			} else {
				$html[] = '<link rel="stylesheet" href="' . $src . '" />'; 
			}		
		}
		
		return implode("\n", $html) . ($html === array() ? '' : "\n");
	}
	
//	/**
//	 * 屏蔽 notice 错误
//	 * 
//	 * @see {@link parent::renderPartial()}
//	 */
//	public function renderPartial($view, $data = null, $return = false, $processOutput = false)
//	{
//		$o = error_reporting(error_reporting() & ~E_NOTICE);
//		$out = parent::renderPartial($view, $data, $return, $processOutput);
//		error_reporting($o);
//		
//		return $out;
//	}
	
	/**
	 * @param string|array $msg
	 */
	public function error($msg)
	{
		if (is_string($msg)) {
			$msg = array($msg);
		}
		
		$msg = Fn::arrayFlat($msg);
		$this->user->setFlash(self::ERROR, $msg);
	}
	
	/**
	 * @param string|array $msg
	 */
	public function info($msg)
	{
		if (is_array($msg)) {
			$msg = implode(' ', $msg);
		}

		$this->user->setFlash(self::INFO, $msg);
	}
	
	/**
	 * @return bool
	 */
	public function hasError()
	{
		return $this->user->hasFlash(self::ERROR);
	}
	
	/**
	 * @return bool
	 */
	public function hasInfo()
	{
		return $this->user->hasFlash(self::INFO);
	}
	
	/**
	 * @return array
	 */
	public function getError()
	{
		return $this->user->getFlash(self::ERROR);
	}
	
	/**
	 * @return string
	 */
	public function getInfo()
	{
		return $this->user->getFlash(self::INFO);
	}
	
	/**
	 * 返回菜单
	 * 
	 * @return array
	 */
	public function getMenu()
	{
		if ($this->_menu === null) {
			$this->_menu = require_once Yii::app()->basePath . '/config/menu.php';
		}
		
		return $this->_menu;
	}
	
	/**
	 * 提取用户权限菜单
	 * 
	 * @param int $rank
	 * @param array $acls
	 * @return array
	 */
	public function assembleMenu($rank, $acls)
	{
		if ($rank == AdminRole::SUPER_ROLE_ID) {
			return $this->menu;
		}
		
		if (!$acls) {
			return array();
		}
		
		$menu = array();
		foreach ($this->menu as $modKey => $mod) {
			foreach ($mod as $ctrlKey => $ctrl) {
				if (isset($acls['ctrls'][$ctrlKey])) {
					$menu[$modKey][$ctrlKey] = $ctrl;
					continue;
				}
	
				$acts = array();
				foreach ($ctrl['actions'] as $actKey => $act) {
					if (isset($acls['acts'][$ctrlKey][$actKey])) {
						$acts[$actKey] = true;
					}
				}
				if (!empty($acts)) {
					$menu[$modKey][$ctrlKey] = $ctrl;
					$menu[$modKey][$ctrlKey]['actions'] = array_intersect_key($ctrl['actions'], $acts);
				}
			}
		}
		
		return $menu;
	}
	
	/**
	 * @return string the page title. Defaults to the controller name and the action name.
	 */	
	public function getPageTitle()
	{
		if ($this->_pageTitle !== null) {
			return $this->_pageTitle;
		} else {
			$name = ucfirst(basename($this->getId()));
			if ($this->getAction() !== null && strcasecmp($this->getAction()->getId(), $this->defaultAction)) {
				return $this->_pageTitle = ucfirst($this->getAction()->getId()) . ' ' . $name;
			} else {
				return $this->_pageTitle = $name;
			}
		}
	}
	
	/**
	 * @param string $value the page title.
	 */
	public function setPageTitle($value)
	{
		$this->_pageTitle = $value;
	}
}
