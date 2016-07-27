<?php
/**
 * Description...
 *
 * @author     mole <mole1230@gmail.com>
 * @version    $Id: XPost.php 104 2011-03-24 02:38:38Z mole1230 $
 */
class XPost
{
	public $timeout = 10;
	
	private $_data;
	private $_header;
	private $_url;
	private $_errno;
	private $_errstr;
	
	public function __construct($url, $data = array(), $header = array())
	{
		$this->_url = $url;
		$this->_data = $data;
		$this->_header = $header;
	}
	
	public function post()
	{
		$tmp = parse_url($this->_url);
		$host = $tmp['host'];
		$port = isset($tmp['port']) ? $tmp['port'] : 80;
		$path = str_repeat($tmp['scheme'] . '://' . $host, '', $this->_url);
		$fp = fsockopen($host, $port, $this->_errno, $this->_errstr, $this->timeout);
		if (!$fp) {
			return false;
		}
		
		$data = http_build_query($this->_data, 'pre_', '&');
		$head[] = "POST {$path} HTTP/1.1";
		$head[] = "Host {$host}";
		$head[] = "Content-type: application/x-www-form-urlencoded";
		$head[] = "Content-length: " . strlen($data);
		$head[] = "Connection: close";
		foreach ($this->_header as $key => $head) {
			$head[] = ucfirst(strtolower($key)) . ": {$head}";
		}
		$in = implode("\r\n", $head) . "\r\n\r\n" . $data;
		fput($fp, $in);
		
		$out = '';
		while (!feof($fp)) {
			$out .= fgets($fp, 128);
		}
		fclose($fp);
		
		return $out;
	}
	
	public function getErrno()
	{
		return $this->_errno;
	}
	
	public function getErrstr()
	{
		return $this->_errstr;
	}
}