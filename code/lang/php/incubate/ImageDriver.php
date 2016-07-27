<?php
/**
 * Image API driver.
 *
 * @author mole <mole1230@gmail.com>
 * @version $Id: ImageDriver.php 210 2011-12-31 05:36:56Z mole1230 $
 */
abstract class ImageDriver
{
	/**
	 * Reference to the current image
	 * 
	 * @var array
	 */
	protected $image;
	
	/**
	 * Reference to the temporary processing image
	 * 
	 * @var Imagick
	 */
	protected $tmpImage;
	
	/**
	 * Processing errors
	 * 
	 * @var array
	 */
	protected $errors = array();
	
	/**
	 * Executes a set of actions, defined in pairs.
	 * 
	 * @param array $actions
	 * @return bool
	 */
	public function execute($actions)
	{
		foreach ($actions as $func => $args) {
			if (!$this->$func($args)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Sanitize and normalize a geometry array based on the temporary image
	 * width and height. Valid properties are: width, height, top, left.
	 * 
	 * @param array $geometry geometry properties
	 * @return  void 
	 */
	protected function sanitizeGeometry(&$geometry)
	{
		list ($width, $height) = $this->properties();
		
		// Width and height cannot exceed current image size
		$geometry['width'] = min($geometry['width'], $width);
		$geometry['height'] = min($geometry['height'], $height);
		
		// Set standard coordinates if given, otherwise use pixel values
		if ($geometry['top'] === 'center') {
			$geometry['top'] = floor(($height / 2) - ($geometry['height'] / 2));
		} elseif ($geometry['top'] === 'top') {
			$geometry['top'] = 0;
		} elseif ($geometry['top'] === 'bottom') {
			$geometry['top'] = $height - $geometry['height'];
		}
		
		// Set standard coordinates if given, otherwise use pixel values
		if ($geometry['left'] === 'center') {
			$geometry['left'] = floor(($width / 2) - ($geometry['width'] / 2));
		} elseif ($geometry['left'] === 'left') {
			$geometry['left'] = 0;
		} elseif ($geometry['left'] === 'right') {
			$geometry['left'] = $width - $geometry['height'];
		}
	}
	
	/**
	 * Extract action of quality.
	 * 
	 * @param array $actions
	 * @return int
	 */
	protected function quality(&$actions)
	{
		$quality = null;
		if (isset($actions['quality'])) {
			$quality = $actions['quality'];
			unset($actions['quality']);
		}
		
		return $quality;
	}
	
	/**
	 * Execute command.
	 * 
	 * @param string $cmd
	 * @throws Exception
	 */
	protected function exec($cmd)
	{
		if (is_array($cmd)) {
			$cmd = implode(' ', $cmd);
		}
		
		exec($cmd, $output, $status);
		if ($status) {
			throw new Exception($output . ' CMD:' . $cmd);
		}
		
		return true;
	}
	
	/**
	 * Return the current width and height of the temporary image. This is mainly
	 * needed for sanitizing the geometry.
	 * 
	 * @return array
	 */
	abstract protected function properties();
	
	/**
	 * Process an image with a set of actions.
	 * 
	 * @param array $image image info
	 * @param array $actions
	 * @param string $file destination filename
	 * @param int $format image format
	 * @return bool
	 */
	abstract public function process($image, $actions, $file, $format);
	
	/**
	 * Flip an image. Valid directions are horizontal and vertical.
	 * 
	 * @param int $direction
	 * @return bool
	 */
	abstract function flip($direction);
	
	/**
	 * Crop an image. Valid properties are: width, height, top, left.
	 * 
	 * @param array $prop
	 * @return bool
	 */
	abstract function crop($prop);
	
	/**
	 * Resize an image. Valid properties are: width, height, and master.
	 * 
	 * @param array $prop
	 * @return bool
	 */
	abstract public function resize($prop);
	
	/**
	 * Rotate an image. Valid properties are: $degrees, and bgcolor; Valid amounts are -180 to 180.
	 * 
	 * @param array $prop
	 * @return bool
	 */
	abstract public function rotate($prop);

	/**
	 * Sharpen and image.
	 * 
	 * @param int $times
	 * @return bool
	 */
	abstract public function sharpen($times);
	
	/**
	 * Blur and image.
	 * 
	 * @param int $times
	 * @return bool
	 */
	abstract public function blur($times);

}
