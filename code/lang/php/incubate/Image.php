<?php
// define some useful constants
define('IMAGE_BASE_PATH', dirname(__FILE__));
define('IMAGE_DRIVERS_PATH', IMAGE_BASE_PATH . '/drivers');

/**
 * Include ImageDriver class.
 */
require_once IMAGE_BASE_PATH . '/ImageDriver.php';

/**
 * Manipulate images using standard methods such as resize, crop, rotate, etc.
 * This class must be re-initialized for every image you wish to manipulate.
 *
 * @author mole <mole1230@gmail.com>
 * @version $Id: Image.php 210 2011-12-31 05:36:56Z mole1230 $
 */
class Image
{
	/**
	 * Resize ratio dimension
	 * 
	 * @var int
	 */
	const RESIZE_FIXED = 1;
	const RESIZE_AUTO = 2;
	const RESIZE_HEIGHT = 3;
	const RESIZE_WIDTH = 4;
	const RESIZE_CROP = 5;
	
	/**
	 * Flip direction
	 * 
	 * @var int
	 */
	const FLIP_HORIZONTAL = 11;
	const FLIP_VERTICAL = 12;
		
	/**
	 * Allowed image types
	 * 
	 * @var array
	 */
	public static $allowedTypes = array(
		IMAGETYPE_GIF => 'gif', 
		IMAGETYPE_JPEG => 'jpg', 
		IMAGETYPE_PNG => 'png'
	);
	
	/**
	 * <code>
	 * array(
	 * 	'driver' => 'imagick',
	 * 	'params' => array()
	 * )
	 * </code>
	 * 
	 * @var array
	 */
	private $_config = array(
		'driver' => 'imagick', 
		'params' => array()
	);
	
	/**
	 * Which driver used
	 * 
	 * @var ImageDriver
	 */
	private $_driver;
	
	/**
	 * @var array
	 */
	private $_actions = array();
	
	/**
	 * File info of image
	 * 
	 * @var array
	 */
	private $_image = array();
	
	/**
	 * Get image format.
	 * 
	 * @param string $file
	 * @return int|false
	 */
	public static function imageType($file)
	{
		$suffix = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if ($suffix == 'jpeg') {
			$suffix = 'jpg';
		}
		return array_search($suffix, self::$allowedTypes);
	}
	
	/**
	 * Creates a new image editor instance.
	 *
	 * @param string $image filename of image
	 * @param array $config non-default configurations
	 * @throws Exception
	 */
	public function __construct($image, $config = array())
	{
		static $check = null;
		
		// Make the check exactly once
		($check === null) && $check = function_exists('getimagesize');
		
		if ($check === false) {
			throw new Exception('function getimagesize missing');
		}
		
		// Check to make sure the image exists
		if (!is_file($image)) {
			throw new Exception('image file not found');
		}
		
		// Fetch the image size and mime type
		$imageInfo = @getimagesize($image);
		
		// Make sure that the image is readable and valid
		if (!is_array($imageInfo) || count($imageInfo) < 3) {
			throw new Exception('image file unreadable');
		}
		
		// Check to make sure the image type is allowed
		if (!isset(self::$allowedTypes[$imageInfo[2]])) {
			throw new Exception('image type not allowed');
		}
		
		// Image has been validated, load it
		$this->_image = array(
			'file' => str_replace('\\', '/', realpath($image)), 
			'width' => $imageInfo[0], 
			'height' => $imageInfo[1], 
			'type' => $imageInfo[2], 
			'ext' => self::$allowedTypes[$imageInfo[2]], 
			'mime' => $imageInfo['mime']
		);
		
		$this->_config = array_merge($this->_config, $config);
		
		// Set driver class name
		$filename = IMAGE_DRIVERS_PATH . '/' . ucfirst($this->_config['driver']) . '.php';
		$classname= ucfirst($this->_config['driver']) . 'Driver';
		require_once $filename;
	
		// Initialize the driver
		$this->_driver = new $classname($this->_config['params']);
		
		// Validate the driver
		if (!($this->_driver instanceof ImageDriver)) {
			throw new Exception('image driver must be implement ImageDriver class');
		}
	}

	/**
	 * Resize an image to a specific width and height. By default, method will
	 * maintain the aspect ratio using the width as the master dimension. If you
	 * wish to use height as master dim, set $image->master_dim = Image::HEIGHT
	 * This method is chainable.
	 * 
	 * @param int $width
	 * @param int $height
	 * @param int $master one of: self::NONE, self::AUTO, self::WIDTH, self::HEIGHT
	 * @param bool $enlarge 
	 * @return  Image
	 * @throws Exception
	 */
	public function resize($width, $height, $master = null, $enlarge = false)
	{
		if (!$this->validSize('width', $width)) {
			throw new Exception('image invalid width');
		}
		if (!$this->validSize('height', $height)) {
			throw new Exception('image invalid height');
		}
		if ($master === null) {
			// Maintain the aspect ratio by default
			$master = self::RESIZE_AUTO;
		} elseif (!$this->validSize('master', $master)) {
			throw new Exception('image invalid master');
		}
		
		$this->_actions['resize'] = array(
			'width' => $width, 
			'height' => $height, 
			'master' => $master,
			'enlarge' => $enlarge
		);
		
		return $this;
	}
	
	/**
	 * Crop an image to a specific width and height. You may also set the top
	 * and left offset.
	 * This method is chainable.
	 * 
	 * @param int $width
	 * @param int $height
	 * @param int|string $top top offset, pixel value or one of: top, center, bottom
	 * @param int|string $left left offset, pixel value or one of: left, center, right
	 * @return  Image
	 * @throws Exception
	 */
	public function crop($width, $height, $top = 'center', $left = 'center')
	{
		if (!$this->validSize('width', $width)) {
			throw new Exception('image invalid width', $width);
		}
		if (!$this->validSize('height', $height)) {
			throw new Exception('image invalid height', $height);
		}
		if (!$this->validSize('top', $top)) {
			throw new Exception('image invalid top', $top);
		}
		if (!$this->validSize('left', $left)) {
			throw new Exception('image invalid left', $left);
		}
		if (empty($width) && empty($height)) {
			throw new Exception('image invalid dimensions');
		}
		
		$this->_actions['crop'] = array(
			'width' => $width, 
			'height' => $height, 
			'top' => $top, 
			'left' => $left
		);
		
		return $this;
	}
	
	/**
	 * Allows rotation of an image by 180 degrees clockwise or counter clockwise.
	 * 
	 * @param int $degrees
	 * @param string $bgcolor e.g. #336699
	 * @return Image
	 */
	public function rotate($degrees, $bgcolor = null)
	{
		$degrees = (int) $degrees;
		
		if ($degrees > 180) {
			do {
				// Keep subtracting full circles until the degrees have normalized
				$degrees -= 360;
			} while ($degrees > 180);
		}
		
		if ($degrees < -180) {
			do {
				// Keep adding full circles until the degrees have normalized
				$degrees += 360;
			} while ($degrees < -180);
		}
		
		$this->_actions['rotate'] = array(
			'degrees' => $degrees,
			'bgcolor' => $bgcolor
		);
		
		return $this;
	}
	
	/**
	 * Flip an image horizontally or vertically.
	 * 
	 * @param int $direction one of self::FLIP_HORIZONTAL, self::FLIP_VERTICAL
	 * @return Image
	 * @throws Exception
	 */
	public function flip($direction)
	{
		if ($direction !== self::FLIP_HORIZONTAL and $direction !== self::FLIP_VERTICAL) {
			throw new Exception('image invalid flip');
		}
		
		$this->_actions['flip'] = $direction;
		
		return $this;
	}
	
	/**
	 * Change the quality of an image.
	 *
	 * @param int $amount
	 * @return Image
	 */
	public function quality($amount)
	{
		$this->_actions['quality'] = (int) max(1, min($amount, 100));
		
		return $this;
	}
	
	/**
	 * Sharpen an image.
	 * 
	 * @param int $times times of sharpen
	 * @return Image
	 */
	public function sharpen($times)
	{
		$this->_actions['sharpen'] = (int) $times;
		
		return $this;
	}
	
	/**
	 * Blur an image.
	 * 
	 * @param int $times times of Blur
	 * @return Image
	 */
	public function blur($times)
	{
		$this->_actions['blur'] = (int) $times;
		
		return $this;
	}
	
	/**
	 * Watermark an image
	 * 
	 * @param string $file
	 * @param int $position
	 * @return Image
	 */
	public function watermark($file, $position)
	{
		$this->_actions['watermark'] = array(
			'file' => $file,
			'pos' => $position
		);
		
		return $this;
	}
	
	/**
	 * Save the image to a new image or output the image to the browser.
	 * 
	 * @param string $file
	 * @param int $format IMAGETYPE_GIF|IMAGETYPE_JPEG|IMAGETYPE_PNG
	 * @param bool $keepActions keep or discard image process actions
	 * @return bool
	 */
	public function save($file = null, $format = null, $keepActions = false)
	{
		if (!empty($file)) {
			$dir = pathinfo($file, PATHINFO_DIRNAME);
			if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
				throw new Exception(strtr('Can not make directory "{dir}" is unwritable', array('{dir}' => $dir)));
			} elseif (!is_writable($dir)) {
				throw new Exception(strtr('Directory "{dir}" is unwritable', array('{dir}' => $dir)));
			}
			if (($format = self::imageType($file)) === false) {
				throw new Exception(strtr('File "{file}" format is not allowed.', array('{file}' => $file)));
			}
		} elseif (!empty($format)) {
			if (!isset(self::$allowedTypes[$format])) {
				throw new Exception(strtr('Invalied format "{format}"', array('{format}' => $format)));
			}
		}
		$status = $this->_driver->process($this->_image, $this->_actions, $file, $format);
		
		// Reset actions. Subsequent save() or render() will not apply previous actions.
		if ($keepActions === false) {
			$this->_actions = array();
		}
		
		return $status;
	}
	
	/**
	 * Sanitize a given value type.
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return bool
	 */
	protected function validSize($type, &$value)
	{
		if (is_null($value)) {
			return true;
		}
		
		if (!is_scalar($value)) {
			return false;
		}
		
		switch ($type) {
			case 'width':
			case 'height':
				if (is_string($value) && !ctype_digit($value)) {
					// Only numbers and percent signs
					if (!preg_match('/^[0-9]++%$/D', $value)) {
						return false;
					}
				} else {
					$value = (int) $value;
				}
				break;
			case 'top':
				if (is_string($value) && !ctype_digit($value)) {
					if (!in_array($value, array('top', 'bottom', 'center'))) {
						return false;
					}
				} else {
					$value = (int) $value;
				}
				break;
			case 'left':
				if (is_string($value) && !ctype_digit($value)) {
					if (!in_array($value, array('left', 'right', 'center'))) {
						return false;
					}
				} else {
					$value = (int) $value;
				}
				break;
			case 'master':
				if ($value !== self::RESIZE_FIXED 
				&& $value !== self::RESIZE_AUTO 
				&& $value !== self::RESIZE_HEIGHT 
				&& $value !== self::RESIZE_WIDTH
				&& $value !== self::RESIZE_CROP) {
					return false;
				}
				break;
		}
		
		return true;
	}
}
