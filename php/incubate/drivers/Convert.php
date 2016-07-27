<?php
/**
 * Convert Image Driver.
 *
 * @author mole <mole1230@gmail.com>
 * @version $Id: Convert.php 192 2011-12-06 15:43:37Z mole1230 $
 */
class ConvertDriver extends ImageDriver
{
	private $_bin = 'convert';
	
	private $_cmdImage;
	
	public function __construct()
	{
		$this->_bin = escapeshellcmd($this->_bin);
	}
	
	public function process($image, $actions, $file)
	{
		$this->image = $image;
		$this->tmpImage = tempnam(sys_get_temp_dir(), 'XConvert');
		copy($this->image['file'], $this->tmpImage);
		$quality = $this->quality($actions);
		$quality = $quality ? $quality : 80;
		
		$this->_cmdImage = escapeshellarg($this->tmpImage);
		$status = $this->execute($actions);
		if ($status) {
			$cmd = array();
			$cmd[] = $this->_bin;
			$cmd[] = $this->tmpImage;
			$cmd[] = "-quality {$quality}%"; 
			$cmd[] = '-strip';
			$cmd[] = $this->tmpImage;
			$status = $this->exec($cmd);
			if (!is_string($file) || !strlen($file)) {
				header('Content-Type: ' . $this->image['mime']);
				echo file_get_contents($this->tmpImage);
			} else {
				//$this->tmpImage->writeImages($file, true);
			}
		}
		unlink($this->tmpImage);
		$this->tmpImage = null;
		return $status;
	}
	
	public function resize($prop)
	{
		// fixed master
		if (empty($prop['width']) && empty($prop['height'])) {
			return true;
		} elseif (empty($prop['height']) && !empty($prop['width'])) {
			$prop['master'] = Image::RESIZE_WIDTH;
		} elseif (empty($prop['width']) && !empty($prop['height'])) {
			$prop['master'] = Image::RESIZE_HEIGHT;
		}
		
		$status = true;
		switch ($prop['master']) {
			case Image::RESIZE_WIDTH:
				$dim = escapeshellarg($prop['width'] . 'x');
				break;
			case Image::RESIZE_HEIGHT:
				$dim = escapeshellarg('x' . $prop['height']);
				break;
			case Image::RESIZE_AUTO:
				$dim = escapeshellarg($prop['width'] . 'x' . $prop['height']);
				break;
			case Image::RESIZE_FIXED:
				$dim = escapeshellarg($prop['width'] . 'x' . $prop['height'] . '!');
				break;
			case Image::RESIZE_CROP:
				break;
			default:
				break;
		}
		
		$cmd = array();
		$cmd[] = $this->_bin;
		$cmd[] = $this->tmpImage;
		$cmd[] = "-thumbnail {$dim}";
		$cmd[] = $this->tmpImage;
		return $this->exec($cmd);
	}
	
	public function rotate($prop)
	{
		return true;
	}
	
	public function flip($direction)
	{
		return true;
	}
	
	public function crop($prop)
	{
		return true;
	}
	
	public function sharpen($amount)
	{
		return true;
	}
	
	protected function properties()
	{
		return array_slice(getimagesize($this->tmpImage), 0, 2, false);
	}
}