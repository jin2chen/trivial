<?php
/**
 * Description...
 *
 * @author     mole <mole1230@gmail.com>
 * @version    $Id: XIterator.php 104 2011-03-24 02:38:38Z mole1230 $
 */
class XIterator implements Iterator
{
	private $_data;
	
	private $_index;
	
	public function __construct(array $data)
	{
		$this->_data = $data;
		$this->_index = 0;
	}
	
	public function current()
	{
		return $this->_data[$this->_index];
	}
	
	public function key()
	{
		return $this->_index;
	}
	
	public function next()
	{
		++$this->_index;
	}
	
	public function rewind()
	{
		$this->_index = 0;
	}
	
	public function valid()
	{
		return isset($this->_data[$this->_index]);
	}
}

$data = array(1, 2, 3, 4, 5);
$obj = new XIterator($data);
foreach ($obj as $i => $v) {
	echo $i, '=>', $v, "\n";
}