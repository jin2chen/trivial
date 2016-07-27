<?php
/**
 * 缩略图生成处理。
 *
 * @author     mole<mole1230@gmail.com>
 * @version    $Id: XImagick.php 104 2011-03-24 02:38:38Z mole1230 $
 */
class XImagick
{
	/**#@+
	 * MODE_CROP 生成固定高宽的图像，图片缩放后铺满，为了铺满，会有部分图像裁剪掉
	 * MODE_PADDING 生成固定高宽的图像，图片缩放后不一定铺满，但保留全部图像信息，即不裁剪，添加补白
	 * MODE_RATIO 生成图像高宽不大于给定高宽，并且以缩放后的实际大小保持
	 *
	 * @var int
	 */
	const MODE_CROP = 1;
	const MODE_PADDING = 2;
	const MODE_RATIO = 3;
	/**#@-*/

	/**#@+
	 * 文件格式
	 *
	 * @var string
	 */
	const FORMAT_JPG = 'JPEG';
	const FORMAT_GIF = 'GIF';
	const FORMAT_PNG = 'PNG';
	/**#@-*/

	/**#@+
	 * 水印位置
	 *
	 * @var int
	 */
	const TOP_LEFT = 1;
	const TOP_RIGHT = 2;
	const BOTTOM_RIGHT = 3;
	const BOTTOM_LEFT = 4;
	/**#@-*/

	/**
	 * 图片质量压缩
	 *
	 * @var float
	 */
	public $quality = 80;

	/**
	 * padding 或  rotate 时的背景色
	 *
	 * @var string
	 */
	public $bgColor = '#FFFFFF';

	/**
	 * 图片格式
	 * 
	 * @var array
	 */
	public $formats = array(
		IMAGETYPE_GIF => 'gif',
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG => 'png'
	);

	/**
	 * 原始文件路径
	 *
	 * @var string 
	 */
	protected $_oriFile;

	/**
	 * @var MagickWand
	 */
	protected $_srcMw;

	/**
	 * @var MagickWand
	 */
	protected $_mw;

	/**
	 * @var string
	 */
	protected $_format;

	/**
	 * @var string
	 */
	protected $_mimeType;

	/**
	 * @var boolean
	 */
	protected $_isValid = true;

	/**
	 * $_srcMw 原始图片宽
	 *
	 * @var int
	 */
	protected $_initWidth;

	/**
	 * $_srcMw 原始图片高
	 *
	 * @var int
	 */
	protected $_initHeight;

	/**
	 * 旋转角度
	 *
	 * @var int
	 */
	protected $_degrees = 0;

	/**
	 * 错误信息
	 *
	 * @var array
	 */
	protected $_error;

	/**
	 * 构造函数
	 *
	 * @param string $file  图片文件路径
	 * @param int $degrees 旋转角度，正值为顺时针，负值为逆时针
	 */
	public function __construct($file, $degrees = 0)
	{
		$this->_oriFile = $file;
		$this->_degrees = $degrees;
		$this->_init();
	}

	/**
	 * 生成缩略图
	 *
	 * @param string $size 缩略图尺寸 196x146
	 * @param int $mode 缩略方式 {@link self::MODE_RATIO}
	 *
	 * @return bool
	 */
	public function thumbnail($size, $mode = self::MODE_RATIO)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if ($this->_mw) {
			DestroyMagickWand($this->_mw);
		}
		$this->_mw = MagickGetImage($this->_srcMw);

		// 获取缩略图宽和高
		$thumbW = $thumbH = 0;
		list($thumbW, $thumbH) = explode('x', $size);
		$thumbW = intval($thumbW);
		$thumbH = intval($thumbH);
		if ($thumbW == 0 && $thumbH == 0) {
			return $this->_isValid;
		}

		// 当原始图片与缩略图大小相同时，不处理
		if (abs($this->_initWidth - $thumbW) < 1 && abs($this->_initHeight - $thumbH) < 1) {
			return $this->_isValid;
		}

		// 计算缩放比例
		$ratioW = $thumbW / $this->_initWidth;
		$ratioH = $thumbH / $this->_initHeight;

		// 对于像800x0, 0x800 作处理，按某一边缩略处理
		if ($ratioW == 0) {
			$ratioW = $ratioH;
		} else if ($ratioH == 0) {
			$ratioH = $ratioW;
		}

		switch ($mode) {
			case self::MODE_CROP:
				$this->_isValid = $this->_cropThumbnail($thumbW, $thumbH, $ratioW, $ratioH);
				break;
			case self::MODE_PADDING:
				$this->_isValid = $this->_padThumbnail($thumbW, $thumbH, $ratioW, $ratioH);
				break;
			default:
				$ratio = min($ratioW, $ratioH);
				if ($ratio < 1) {
					$cropW = $this->_initWidth * $ratio;
					$cropH = $this->_initHeight * $ratio;
					$this->_isValid = $this->_resizeImage($this->_mw, $cropW, $cropH);
				}
				break;
		}

		return $this->_isValid;
	}

	/**
	 * 给图片制做水印
	 *
	 * @param string $file 水印图片路径
	 * @param int $mode 水印位置 {@link self::BOTTOM_RIGHT}
	 *
	 * @return bool
	 */
	public function waterMark($file, $mode = self::BOTTOM_RIGHT)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if (!$this->_mw) {
			$this->_mw = MagickGetImage($this->_srcMw);
		}

		$wmw = NewMagickWand();
		$this->_isValid = MagickReadImage($wmw, $file);
		if (!$this->_isValid) {
			$this->_debug('MagickReadImage', __FILE__, __LINE__, $wmw);
			return $this->_isValid;
		}

		$pos = $this->_makePosition($this->_mw, $wmw, $mode);
		if (is_array($pos)) {
			$pos['x'] = intval($pos['x']);
			$pos['y'] = intval($pos['y']);
			$this->_isValid = MagickCompositeImage($this->_mw, $wmw, MW_OverCompositeOp, $pos['x'], $pos['y']);
			if (!$this->_isValid) {
				$this->_debug('MagickCompositeImage', __FILE__, __LINE__, $this->_mw);
				return $this->_isValid;
			}	
		}

		if ($wmw) {
			DestroyMagickWand($wmw);
		}

		return $this->_isValid;
	}

	/**
	 * 如果图片处理过程中出错，获取出错信息
	 *
	 * @return array
	 */
	public function getError()
	{
		return $this->_error;
	}

	/**
	 * 获取原始图片格式
	 *
	 * @return string
	 */
	public function getFormat()
	{
		return $this->_format;
	}

	/**
	 * 获取原始图片的 mime类型
	 * 
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->_mimeType;
	}

	/**
	 * 检测处理过程是否失败
	 *
	 * @return bool
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * 获取图片处理后的二进制数据。
	 *
	 * @param string $type 处理后要生成的图片格式
	 *
	 * @return bool
	 */
	public function getThumb($type = null)
	{
		if ($this->_tidyThumbData($type)) {
			$blob = MagickGetImageBlob($this->_mw);
			if ($blob === false) {
				$this->_isValid = false;
				$this->_debug('MagickGetImageBlob', __FILE__, __LINE__, $this->_mw);
				return $this->_isValid;
			} else {
				return $blob;
			}
		}

		return $this->_isValid;
	}

	/**
	 * 获取当前缩略图长宽，最好在 getThumb后调用此函数。
	 *
	 * @return array
	 */
	public function getThumbDimension()
	{
		$width = MagickGetImageWidth($this->_mw);
		$height = MagickGetImageHeight($this->_mw);

		return array(
			'width' => intval($width),
			'height' => intval($height)
		);
	}

	/**
	 * 保存处理后的图片
	 *
	 * @param string $file 文件名，如果没有给出文件名，则直接输出图片
	 * @return 
	 */
	public function save($file = '')
	{
		if (!$this->_tidyThumbData()) {
			return $this->_isValid;
		}

		if ($file) {
			if (!MagickWriteImage($this->_mw, $file)) {
				$this->_debug('MagickWriteImage', __FILE__, __LINE__, $this->_mw);
				$this->_isValid = false;
				return $this->_isValid;
			}
		} else {
			header('Content-type: ' . $this->getMimeType());
			header('Cache-Control: no-cache, must-revalidate');
			MagickEchoImageBlob($this->_mw);
		}
	}

	/**
	 * 清除处理过程中产生的 resource
	 *
	 * @return void
	 */
	public function destroy()
	{
		if ($this->_srcMw) {
			DestroyMagickWand($this->_srcMw);
		}

		if ($this->_mw) {
			DestroyMagickWand($this->_mw);
		}
	}

	/**
	 * 析构函数
	 *
	 * @return void 
	 */
	public function __destruct()
	{
		$this->destroy();
	}

	/**
	 * 获取图片原始width, height，并产旋转
	 *
	 * @return  bool
	 */
	protected function _init()
	{
		// fixed for gif
		$info = @getimagesize($this->_oriFile);
		if (!$info) {
			$this->_isValid = false;
			$this->_error = "Read file {$this->_oriFile} failure.";
			return $this->_isValid;
		}

		if (!isset($this->formats[$info[2]])) {
			$this->_isValid = false;
			$this->_error = "Image's format is invalid.";
			return $this->_isValid;
		}

		$this->_initWidth = intval($info[0]);
		$this->_initHeight = intval($info[1]);
		$this->_format = $this->formats[$info[2]];
		$this->_mimeType = $info['mime'];

		$this->_srcMw = NewMagickWand();
		$this->_isValid = MagickReadImage($this->_srcMw, $this->_oriFile);
		if (!$this->_isValid) {
			$this->_debug('MagickReadImage', __FILE__, __LINE__, $this->_srcMw);
			return $this->_isValid;
		}

		if ($this->_degrees) {
			$bg = NewPixelWand($this->bgColor);
			$this->_isValid = MagickRotateImage($this->_srcMw, $bg, $this->_degrees);
			if (!$this->_isValid) {
				$this->_debug('MagickRotateImage', __FILE__, __LINE__, $this->_srcMw);
				return $this->_isValid;
			} else {
				$this->_initWidth = intval(MagickGetImageWidth($this->_srcMw));
				$this->_initHeight= intval(MagickGetImageHeight($this->_srcMw));
			}

			if ($bg) {
				DestroyPixelWand($bg);
			}
		}

		return $this->_isValid;
	}

	/**
	 * 调试
	 * 
	 * @param string $func
	 * @param string $file
	 * @param int $line
	 * @param resource $wand
	 */
	protected function _debug($func, $file, $line, $wand = null)
	{
		$file = basename($file);
		$this->_error = "{$func} in file {$file} on line {$line} ";
		if ($wand !== null) {
			$this->_error .= WandGetExceptionString($wand);
		}
	}

	/**
	 * 对缩略图进行文件格式设定与质量压缩，并去掉无用信息，减少图片大小
	 *
	 * @param string $type 设定图片保存格式
	 *
	 * @return bool
	 */
	protected function _tidyThumbData($type = null)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if (!$this->_mw) {
			$this->_mw = MagickGetImage($this->_srcMw);
		}

		$type = !empty($type) ? $type : $this->_format;
		MagickSetFormat($this->_mw, $type);
		MagickSetImageCompressionQuality($this->_mw, $this->quality);
		MagickStripImage($this->_mw);

		return $this->_isValid;
	}

	/**
	 * 图片缩放处理
	 *
	 * @param resource $mw
	 * @param int $width
	 * @param int $height
	 *
	 * @return bool
	 */
	protected function _resizeImage(&$mw, $width, $height)
	{
		$width = intval($width);
		$height = intval($height);
		$this->_isValid = MagickScaleImage($mw, $width, $height);
		if (!$this->_isValid) {
			$this->_debug('MagickScaleImage', __FILE__, __LINE__, $mw);
		}

		return $this->_isValid;
	}

	/**
	 * 图片补白处理
	 *
	 * @param resource $overMw
	 * @param int $overW
	 * @param int $overH
	 * @param int $bgW
	 * @param int $bgH
	 *
	 * @return bool
	 */
	protected function _padImage(&$overMw, $overW, $overH, $bgW, $bgH)
	{
		$overW = intval($overW);
		$overH = intval($overH);
		$bgW = intval($bgW);
		$bgH = intval($bgH);
		$bgMw = NewMagickWand();
		$this->_isValid = MagickNewImage($bgMw, $bgW, $bgH, $this->bgColor);
		if (!$this->_isValid) {
			$this->_debug('MagickNewImage', __FILE__, __LINE__, $bgMw);
			return $this->_isValid;
		}

		$cropX = intval(abs($bgW - $overW) / 2);
		$cropY = intval(abs($bgH - $overH) / 2);
		$this->_isValid = MagickCompositeImage($bgMw, $overMw, MW_OverCompositeOp, $cropX, $cropY);
		if (!$this->_isValid) {
			$this->_debug('MagickCompositeImage', __FILE__, __LINE__, $bgMw);
			return $this->_isValid;
		} else {
			if ($overMw) {
				DestroyMagickWand($overMw);
			}
			$overMw = MagickGetImage($bgMw);
			if ($bgMw) {
				DestroyMagickWand($bgMw);
			}
		}

		return $this->_isValid;
	}

	/**
	 * 计算水印位置
	 *
	 * @param resource $mw
	 * @param resource $wmw
	 * @param int $mode
	 *
	 * @return array
	 */
	protected function _makePosition(&$mw, &$wmw, $mode)
	{
		$mW = MagickGetImageWidth($mw);
		$mH = MagickGetImageHeight($mw);
		$wW = MagickGetImageWidth($wmw);
		$wH = MagickGetImageHeight($wmw);

		if ($wW > $mW || $wH > $mH) {
			return false;
		}

		$posX = $posY = 0;
		switch ($mode) {
			case self::TOP_LEFT:
				break;
			case self::TOP_RIGHT:
				$posX = $mW - $wW;
				$posY = 0;
				break;
			case self::BOTTOM_RIGHT:
				$posX = $mW - $wW;
				$posY = $mH - $wH;
				break;
			case self::BOTTOM_LEFT:
				$posX = 0;
				$posY = $mH - $wH;
				break;
			default:
				break;
		}

		return array(
			'x' => $posX,
			'y' => $posY
		);
	}

	/**
	 * 剪切缩略处理
	 *
	 * @param int $thumbW
	 * @param int $thumbH
	 * @param float $ratioW
	 * @param float $ratioH
	 *
	 * @return bool
	 */
	protected function _cropThumbnail($thumbW, $thumbH, $ratioW, $ratioH)
	{
		if ($ratioW < 1 && $ratioH < 1) {
			// 均缩小
			$ratio = max($ratioW, $ratioH);
			$cropW = $this->_initWidth * $ratio;
			$cropH = $this->_initHeight * $ratio;
			if (!$this->_resizeImage($this->_mw, $cropW, $cropH)) {
				return $this->_isValid;
			}
			$cropX = intval(($cropW - $thumbW) / 2);
			$cropY = intval(($cropH - $thumbH) / 2);
			$this->_isValid = MagickCropImage($this->_mw, $thumbW, $thumbH, $cropX, $cropY);
			if (!$this->_isValid) {
				$this->_debug('MagickCropImage', __FILE__, __LINE__, $this->_mw);
				return $this->_isValid;
			}
		} else if ($ratioW > 1 && $ratioH > 1) {
			// 均不够，补白
			if (!$this->_padImage($this->_mw, $this->_initWidth, $this->_initWidth, $thumbW, $thumbH)) {
				return $this->_isValid;
			}
		} else {
			// 一边长，一边短，先剪切，然后补白
			$cropW = min($this->_initWidth, $thumbW);
			$cropH = min($this->_initHeight, $thumbH);
			$cropX = intval($this->_initWidth - $cropW) / 2;
			$cropY = intval($this->_initHeight - $cropH) / 2;
			$this->_isValid = MagickCropImage($this->_mw, $cropW, $cropH, $cropX, $cropY);
			if (!$this->_isValid) {
				$this->_debug('MagickCropImage', __FILE__, __LINE__, $this->_mw);
				return $this->_isValid;
			}
			if (!$this->_padImage($this->_mw, $cropW, $cropH, $thumbW, $thumbH)) {
				return $this->_isValid;
			}
		}

		return $this->_isValid;
	}

	/**
	 * 补白缩略图处理
	 *
	 * @param int $thumbW
	 * @param int $thumbH
	 * @param float $ratioW
	 * @param float $ratioH
	 *
	 * @return bool
	 */
	protected function _padThumbnail($thumbW, $thumbH, $ratioW, $ratioH)
	{
		$ratio = min($ratioW, $ratioH);
		if ($ratio < 1) {
			// 只要其中有一边要收缩
			$cropW = $this->_initWidth * $ratio;
			$cropH = $this->_initHeight * $ratio;
			if (!$this->_resizeImage($this->_mw, $cropW, $cropH)) {
				return $this->_isValid;
			}
			if (!$this->_padImage($this->_mw, $cropW, $cropH, $thumbW, $thumbH)) {
				return $this->_isValid;
			}
		} else {
			// 两边都不收缩
			if (!$this->_padImage($this->_mw, $this->_initWidth, $this->_initHeight, $thumbW, $thumbH)) {
				return $this->_isValid;
			}
		}

		return $this->_isValid;
	}
}
