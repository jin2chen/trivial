<?php
/**
 * Convert Array To XML
 *
 * @author     mole <mole1230@gmail.com>
 * @version    $Id: XArrayToXml.php 104 2011-03-24 02:38:38Z mole1230 $
 */
class XArrayToXml
{
	protected $_xml;

	public function __construct(array $data, $encoding = 'utf-8')
	{
		$this->_xml = '<?xml version="1.0" encoding="' . $encoding . '"?><items>';
		$this->_xml.= $this->_array2xml($data);
	}

	public function getXml($return = true)
	{
		$this->_xml .= '</items>';
		if ($return) {
			return $this->_xml;
		}
		
		header("content-type: text/xml");
		echo $this->_xml;
	}

	protected function _arrayToXml($data, $deep = 0)
	{
		$xml = '';
		$deepstr = ($deep < 2) ? '' : $deep;
		$deep++;
		
		foreach ($data as $key => $val) {
			is_numeric($key) && $key = "item{$deepstr} id=\"$key\"";
			$xml .= "<$key>";
			$xml .= is_array($val) ? $this->_array2xml($val, $deep) : "<![CDATA[{$val}]]>";
			list ($key, ) = explode(' ', $key);
			$xml .= "</$key>";
		}
		return $xml;
	}
}

