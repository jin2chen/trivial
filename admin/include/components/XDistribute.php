<?php
class XDistribute extends CComponent 
{
	public $svnAdminPath = 'svnadmin';

	public $svnPath = 'svn';

	public $rsyncSenderPath = 'rsync -ahW';

	public $rsyncReceiverPath = 'rsync';

	public $sshUser = 'mole';

	public $sshPort = '22';
	
	private $_svnRepoBasepath;

	private $_srcBasepath;

	private $_destBasepath;

	private $_destIps = array();

	private $_exclude = array(
		'.svn/'	
	);

	private $_rsyncErrors = array();

	public function __construct()
	{
	}

	public function init()
	{
	}

	public function setSrcBasepath($path)
	{
		$this->_srcBasepath = rtrim($path, '/\\');
	}

	public function getSrcBasepath()
	{
		if ($this->_srcBasepath === null) {
			throw new Exception();
		}

		return $this->_srcBasepath;
	}

	public function setDestBasepath($path)
	{
		$this->_destBasepath = rtrim($path, '/\\');
	}

	public function getDestBasepath()
	{
		if ($this->_destBasepath === null) {
			throw new Exception();
		}

		return $this->_destBasepath;
	}

	public function setDestIps($ips)
	{
		if (is_string($ips)) {
			$ips = preg_split('/[\s,]+/', $ips);
		}

		$this->_destIps = $ips;
		return $this->_destIps;
	}

	public function getDestIps()
	{
		return $this->_destIps;
	}

	public function setSvnRepoBasePath($path)
	{
		$this->_svnRepoBasepath = rtrim($path, '/\\');
	}

	public function getSvnRepoBasepath()
	{
		if ($this->_svnRepoBasepath === null) {
			throw new Exception();
		}

		return $this->_svnRepoBasepath;
	}

	public function getExclude()
	{
		$excludes = array();
		foreach ($this->_exclude as $exclude) {
			$excludes[] = '--exclude=' . escapeshellarg($exclude);
		}

		return implode(' ', $excludes);
	}

	public function getRsyncErrors($code)
	{
		return $this->_rsyncErrors;
	}

	public function rsync($files)
	{
		$this->_rsyncErrors = array();
		$tmp = array();
		foreach ($files as $file) {
			$tmp[] = escapeshellarg($this->getSrcBasepath() . '/./' . trim($file, '/\\'));
		}

		$command = array();
		$command[] = $this->rsyncSenderPath;
		$command[] = $this->getExclude();
		$command[] = '--rsync-path=' . escapeshellarg('mkdir -p ' . $this->getDestBasepath() . ';' . $this->rsyncReceiverPath);
		$command[] = '--rsh=' . escapeshellarg("ssh -p {$this->sshPort} -l {$this->sshUser}");
		$command[] = implode(' ', $tmp);
		$command = implode(' ', $command) . ' ';
		
		$isValid = true;
		foreach ($this->getDestIps() as $ip) {
			$cmd = $command . escapeshellarg($ip . ':' . $this->getDestBasepath());
			$output = array();
			$status = 0;
    		$this->_exec($cmd, $output, $status);
			if ($status != 0) {
				var_dump($output);
				$this->_rsyncErrors[$ip] = $status;
				if ($isValid) {
					$isValid = false;
				}
			}
		}

		return $isValid;
	}

	public function svnCreate($project)
	{
		$command = array();
		$command[] = $this->svnAdminPath;
		$command[] = 'create';
		$command[] = $this->getSvnRepoBasepath() . '/' . trim($project, '/\\');
		$cmd = implode(' ', $command);
		return $this->_exec($cmd);
	}

	public function svnInitImport($project)
	{
		$path = $this->getSvnRepoBasepath() . '/template/dir';
		if (!is_dir($path)) {
			return true;
		}
		$command = array();
		$command[] = $this->svnPath;
		$command[] = 'import';
		$command[] = '-m init';
		$command[] = '--config-dir /';
		$command[] = $path;
		$command[] = 'file://' . $this->getSvnRepoBasepath() . '/' . trim($project, '/\\');
		$cmd = implode(' ', $command);
		return $this->_exec($cmd);
	}
	
	public function svnUpdate()
	{
		$command = array();
		$command[] = $this->svnPath;
		$command[] = 'up';
		$command[] = $this->getSrcBasepath();
		$cmd = implode(' ', $command);
		return $this->_exec($cmd);
	}

	private function _exec($cmd, &$output = array(), &$status = 0)
	{
		echo $cmd, "\n";
		exec($cmd . ' 2>&1', $output, $status);
		echo $status, "\n";
		if ($status != 0) {
			//Yii::log();
			return false;
		}

		return true;
	}
}

