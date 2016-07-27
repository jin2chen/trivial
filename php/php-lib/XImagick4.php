<?php
/**
 * 缩略图处理
 *
 * @author     mole<mole1230@gmail.com>
 * @version    $Id: XImagick4.php 104 2011-03-24 02:38:38Z mole1230 $
 */

/* 生成固定高宽的图像，图片缩放后铺满，为了铺满，会有部分图像裁剪掉 */
define('XIMAGICK_MODE_CROP', 1);

/* 生成固定高宽的图像，图片缩放后不一定铺满，但保留全部图像信息，即不裁剪，而是添加补白 */
define('XIMAGICK_MODE_PADDING', 2);

/* 生成图像高宽不大于给定高宽，并且以缩放后的实际大小保持 */
define('XIMAGICK_MODE_RATIO', 3);

/* JPEG 文件格式 */
define('XIMAGICK_FORMAT_JPG', 'JPEG');

/* GIF 文件格式 */
define('XIMAGICK_FORMAT_GIF', 'GIF');

/* PNG 文件格式 */
define('XIMAGICK_FORMAT_PNG', 'PNG');

/* 水印位置，左上角 */
define('XIMAGICK_TOP_LEFT', 1);

/* 右上角 */
define('XIMAGICK_TOP_RIGHT', 2);

/* 右下角 */
define('XIMAGICK_BOTTOM_RIGHT', 3);

/* 左下角 */
define('XIMAGICK_BOTTOM_LEFT', 4);

/* VFS 存储 */
require_once 'VFS/VFS/dpool_storage.php';

class XImagick
{
	/**
	 * 图片格式
	 * 
	 * @var array
	 */
	var $formats = array(
		IMG_GIF => 'gif',
		IMG_JPG => 'jpg',
		IMG_JPEG => 'jpg',
		IMG_PNG => 'png'
	);

	/**
	 * @var string
	 */
	var $format;

	/**
	 * padding 或  rotate 时的背景色
	 *
	 * @var string
	 */
	var $bgColor = '#FFFFFF';
	
	/**
	 * 旋转角度
	 *
	 * @var int
	 */
	var $degrees = 0;

	/**
	 * 图片质量压缩
	 *
	 * @var float
	 */
	var $quality = 80;

	/**
	 * 原始宽度
	 *
	 * @var int
	 */
	var $_initWidth;

	/**
	 * 原始高度
	 *
	 * @var int
	 */
	var $_initHeight;

	/**
	 * @var MagickWand
	 */
	var $_srcMw;

	/**
	 * @var MagickWand
	 */
	var $_mw;

	/**
	 * @var string 原始文件
	 */
	var $_oriFile;

	/**
	 * @var VFS_dpool_storage 存储对象 
	 */
	var $_vfs;
	
	/**
	 * @var boolean
	 */
	var $_isValid = true;

	/**
	 * 错误信息
	 *
	 * @var array
	 */
	var $_errors = array();

	/**
	 * 图片处理过程中创建的 PixelWand 资源
	 *
	 * @var array
	 */
	var $_newPixelWands = array();

	/**
	 * 图片处理过程中创建的 MagickWand 资源
	 *
	 * @var array
	 */
	var $_newMagickWands = array();

	/**
	 * 构造函数
	 *
	 * @param string $file	图片文件路径
	 * @param int $degrees 旋转角度，正值为顺时针，负值为逆时针
	 */
	function XImagick($file, $degrees = 0)
	{
		$this->degrees = $degrees;
		$this->_oriFile = $file;

		$this->_init();
	}

	/**
	 * 对图片缩略处理
	 *
	 * @param string $size 800x800
	 * @param int $mode 缩略方式
	 *
	 * @return bool
	 */
	function thumbnail($size, $mode = XIMAGICK_MODE_RATIO)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if ($this->_mw) {
			DestroyMagickWand($this->_mw);
		}
		$this->_mw = CloneMagickWand($this->_srcMw);
		if (!$this->_mw) {
			$this->_isValid = false;
			$this->_errors[] = 'CloneMagickWand ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_srcMw);
			return $this->_isValid;
		}

		$thumbW = $thumbH = 0;
		list ($thumbW, $thumbH) = explode('x', $size);
		if (abs($this->_initWidth - $thumbW) < 1 && abs($this->_initHeight - $thumbH) < 1) {
			return $this->_isValid;
		}

		$ratioW = $thumbW / $this->_initWidth;
		$ratioH = $thumbH / $this->_initHeight;
		if ($mode === XIMAGICK_MODE_RATIO) {
			if ($ratioH == 0) {
				$ratioH = $ratioW;
			} else if ($ratioW == 0) {
				$ratioW = $ratioH;
			}
		}
		
		switch ($mode) {
			case XIMAGICK_MODE_CROP:
				$this->_cropThumbnail($thumbW, $thumbH, $ratioW, $ratioH);
				break;
			case XIMAGICK_MODE_PADDING:
				$this->_padThumbnail($thumbW, $thumbH, $ratioW, $ratioH);
				break;
			default:
				$ratio = min($ratioW, $ratioH);
				if ($ratio < 1) {
					$cropW = (int) $this->_initWidth * $ratio;
					$cropH = (int) $this->_initHeight * $ratio;
					$this->_resizeImage($this->_mw, $cropW, $cropH);
				}
				break;
		}

		return $this->_isValid;
	}

	/**
	 * 打水印
	 *
	 * @param string $file 水印文件
	 * @param int $mode  水印位置
	 * @return boolean
	 */
	function waterMark($file, $mode = XIMAGICK_BOTTOM_RIGHT)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if (!$this->_mw) {
			$this->_mw = CloneMagickWand($this->_srcMw);
			if (!$this->_mw) {
				$this->_isValid = false;
				$this->_errors[] = 'CloneMagickWand ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_srcMw);
				return $this->_isValid;
			}
		}

		$wmw = NewMagickWand();
		$this->_isValid = MagickReadImage($wmw, $file);
		if (!$this->_isValid) {
			$this->_errors[] = 'MagickReadImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($wmw);
			return $this->_isValid;
		}

		$pos = $this->_makePosition($this->_mw, $wmw, $mode);
		if (is_array($pos)) {
			$this->_isValid = MagickCompositeImage($this->_mw, $wmw, MW_OverCompositeOp, $pos['x'], $pos['y']);
			if (!$this->_isValid) {
				$this->_errors[] = 'MagickCompositeImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
			}
		}

		if ($wmw) {
			DestroyMagickWand($wmw);
		}

		return $this->_isValid;
	}

	/**
	 * 获取错误信息，一般用于调用
	 */
	function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * 获取缩略图二进投制数据
	 *
	 * @param $type 文件格式
	 * @return binary|bool
	 */
	function getThumb($type = XIMAGICK_FORMAT_JPG)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if ($this->_tidyThumbData($type)) {
			$blob = MagickGetImageBlob($this->_mw);
			if ($blob === false) {
				$this->_isValid = false;
				$this->_errors[] = 'MagickGetImageBlob ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
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
	function getThumbDimension()
	{
		$width = MagickGetImageWidth($this->_mw);
		$height = MagickGetImageHeight($this->_mw);
		
		return array('width' => $width, 'height' => $height);
	}

	/**
	 * 判断缩略过程是否有错误出现，一般用于调试。
	 *
	 * @return bool
	 */
	function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * 保存缩略图片或直接输出
	 *
	 * @param string $file 文件名
	 * @param string $type 文件格式
	 */
	function save($file = '', $type = XIMAGICK_FORMAT_JPG)
	{
		$this->_tidyThumbData($type);
		if ($file) {
			MagickWriteImage($this->_mw, $file);
		} else {
			header('Content-type: image/jpeg');
			header('Cache-Control: no-cache, must-revalidate');
			MagickEchoImageBlob($this->_mw);
		}
	}
	
	function saves($basename, $thumbs, $number = NULL)
	{
		$watermark = $GLOBALS['CONF']['upload']['watermark'];
		$base = $GLOBALS['CONF']['upload']['storagePath'];
		$infos = array();
		
		foreach ($thumbs as $thumb) {
			$this->thumbnail($thumb['size'], $thumb['mode']);
			if (!empty($thumb['pos'])) {
				$this->waterMark($watermark, $thumb['pos']);
			}

			$dir = $base . $this->mkHashDir($basename);
			$realname  = $basename;
			if ($number !== NULL) {
				$realname .= '_' . $number;
			}
			if (isset($thumb['prefix'])) {
				$realname = $thumb['prefix'] . $realname;
			}
			if (isset($thumb['suffix'])) {
				$realname = $realname . $thumb['suffix'];
			}
			$realname .= '.jpg';
			$ret = $this->storage('/' . $realname, $dir);
			if ($ret !== true) {
				// 错误日志
				$msg = "{$ret} pic handle error\n";
				$dbw = &dbw();
				$sql = "UPDATE `stylephoto` SET "
					. "`document` = CONCAT(`document`, '" . $dbw->escapeSimple($msg) . "') "
					. "WHERE `id` = {$GLOBALS['CONF']['statid']}";
				$dbw->query($sql);

				return array();
			}
			
			$info = array();
			$info['name'] = $realname;
			$info['dir']  = $dir;
			$info['path'] = preg_replace('/^' . preg_quote($base, '/') . '/', '', $info['dir'] . '/' . $info['name']);
			$infos[] = $info;
		}
		
		return $infos;
	}
	
	function storage($file, $dir)
	{ 
   // 	$tmpdir = $_SERVER['SINASRV_CACHE_DIR'] . '/'; . PROJECT;
   // 	if (!is_dir($tmpdir)) {
   // 		rmkdir($tmpdir);
   // 	}
    	$tmpdir = $_SERVER['SINASRV_CACHE_DIR'] . '/';
		$tmp = tempnam($tmpdir, "TMP_IMG");
		$msg = '';
		$ret = true;
		if (!$this->_tidyThumbData(XIMAGICK_FORMAT_JPG)) {
			// 错误日志
			$msg = implode("\n", $this->getErrors()) . "\n";
			$ret = -1;
		} else {
   // 		$this->_isValid = MagickWriteImage($this->_mw, $tmp);
   // 		if (!$this->_isValid) {
   // 			$this->_errors[] = 'MagickWriteImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
   // 			$msg = implode("\n", $this->getErrors()) . "\n";
   // 			$ret = -2;
   // 		} else {
   // 			$ret = $this->_vfs->write($dir, $file, $tmp, true);
   // 			if (is_a($ret, 'PEAR_Error')) {
   // 				$this->_isValid = false;
   // 				$msg = $ret->getMessage() . "\n";
   // 				$ret = -3;
   // 			}
   // 		}
			MagickWriteImage($this->_mw, $tmp);
			$ret = $this->_vfs->write($dir, $file, $tmp, true);
			if (is_a($ret, 'PEAR_Error')) {                        		
				$this->_isValid = false;                           
				$msg = $ret->getMessage() . "\n";                  		
				$ret = -3;
			}
		}

		if (!$this->isValid()) {
			$dbw = &dbw();
			$sql = "UPDATE `stylephoto` SET "
				. "`document` = CONCAT(`document`, '" . $dbw->escapeSimple($msg) . "') "
				. "WHERE `id` = {$GLOBALS['CONF']['statid']}";
			$dbw->query($sql);
		} else { 
			$size= filesize($tmp);
			$msg = "{$dir}{$file} {$size}\n";
			$dbw = &dbw();
			$sql = "UPDATE `stylephoto` SET "
				. "`huxing_desc` = CONCAT(`huxing_desc`, '" . $dbw->escapeSimple($msg) . "') "
				. "WHERE `id` = {$GLOBALS['CONF']['statid']}";
			$dbw->query($sql);
		}

		@unlink($tmp);
		return $ret;
	}
	
	/**
	 * 根据文件名，产生HASH目录
	 *
	 * @param string $filename
	 * @param int $deep 目录深度
	 *
	 * @return string
	 */
	function mkHashDir($filename, $deep = 2)
	{
		$m = strtolower(md5($filename));
		switch ($deep) {
			case 1:
				$d = $m[0] . $m[3];
				break;
			case 2:
				$d = $m[0] . $m[3] . '/' . $m[1] . $m[2];
				break;
			default:
				break;
		}
	
		return '/' . $d;
	}

	/**
	 * 销毁图片处理过程中产生的对象
	 */
	function destroy()
	{
		if ($this->_srcMw) {
			DestroyMagickWand($this->_srcMw);
		}

		if ($this->_mw) {
			DestroyMagickWand($this->_mw);
		}

		foreach ($this->_newMagickWands as $mw) {
			if ($mw) {
				DestroyMagickWand($mw);
			}
		}

		foreach ($this->_newPixelWands as $pm) {
			if ($pm) {
				DestroyPixelWand($pm);
			}
		}
	}

	
	/**
	 * 在图片输出前，设定输出格式，对图片质量压缩，
	 * 并去掉图片无用信息以减小图片大小
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	function _tidyThumbData($type = null)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if (!$this->_mw) {
			$this->_mw = CloneMagickWand($this->_srcMw);
			if (!$this->_mw) {
				$this->_isValid = false;
				$this->_errors[] = 'CloneMagickWand ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_srcMw);
				return $this->_isValid;
			}
		}

		$type = $this->format;
		//$type = !empty($type) ? $type : $this->format;
		$this->_isValid = MagickSetImageFormat($this->_mw, $type);
		if (!$this->_isValid) {
			$this->_errors[] = 'MagickSetFormat ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
			return $this->_isValid;
		}
//
//		$this->_isValid = MagickSetImageCompressionQuality($this->_mw, $this->quality);
//		if (!$this->_isValid) {
//			$this->_errors[] = 'MagickSetImageCompressionQuality ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
//			return $this->_isValid;
//		}
//
//		$this->_isValid = MagickStripImage($this->_mw);
//		if (!$this->_isValid) {
//			$this->_errors[] = 'MagickStripImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
//			return $this->_isValid;
//		}

		return $this->_isValid;
	}

	/**
	 * 缩放图片
	 *
	 * @param resource $mw
	 * @param int $width
	 * @param int $height
	 *
	 * @return bool
	 */
	function _resizeImage(&$mw, $width, $height)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		$this->_isValid = MagickScaleImage($mw, $width, $height);
		if (!$this->_isValid) {
			$this->_errors[] = 'MagickScaleImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($mw);
			return $this->_isValid;
		}

		return $this->_isValid;
	}
	
	/**
	 * 对图片进行剪切。
	 *
	 * @param int $thumbW
	 * @param int $thumbH
	 * @param float $ratioW
	 * @param float $ratioH
	 *
	 * @return bool
	 */
	function _cropThumbnail($thumbW, $thumbH, $ratioW, $ratioH)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		if ($ratioW < 1 && $ratioH < 1) {
			// 均缩小
			$ratio = max($ratioW, $ratioH);
			$cropW = (int) $this->_initWidth * $ratio;
			$cropH = (int) $this->_initHeight * $ratio;
			if (!$this->_resizeImage($this->_mw, $cropW, $cropH)) {
				return $this->_isValid;
			}
			$cropX = ($cropW - $thumbW) / 2;
			$cropY = ($cropH - $thumbH) / 2;
			$this->_isValid = MagickCropImage($this->_mw, $thumbW, $thumbH, $cropX, $cropY);
			if (!$this->_isValid) {
				$this->_errors[] = 'MagickCropImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
				return $this->_isValid;
			}
		} else if ($ratioW > 1 && $ratioH > 1) {
			// 均不够，补白
			if (!$this->_padImage($this->_mw, $this->_initWidth, $this->_initHeight, $thumbW, $thumbH)) {
				return $this->_isValid;
			}
		} else {
			// 一边长，一边短，先剪切，然后补白
			$cropW = min($this->_initWidth, $thumbW);
			$cropH = min($this->_initHeight, $thumbH);
			$cropX = (int) ($this->_initWidth - $cropW) / 2;
			$cropY = (int) ($this->_initHeight - $cropH) / 2;
			$this->_isValid = MagickCropImage($this->_mw, $cropW, $cropH, $cropX, $cropY);
			if (!$this->_isValid) {
				$this->_errors[] = 'MagickCropImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_mw);
				return $this->_isValid;
			}
			if (!$this->_padImage($this->_mw, $cropW, $cropH, $thumbW, $thumbH)) {
				return $this->_isValid;
			}
		}

		return $this->_isValid;
	}

	/**
	 * 对缩略补白操作
	 *
	 * @param int $thumbW
	 * @param int $thumbH
	 * @param float $ratioW
	 * @param float $ratioH
	 *
	 * @return bool
	 */
	function _padThumbnail($thumbW, $thumbH, $ratioW, $ratioH)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}
		
		$ratio = min($ratioW, $ratioH);
		if ($ratio < 1) {
			// 只要其中有一边要收缩
			$cropW = (int) $this->_initWidth * $ratio;
			$cropH = (int) $this->_initHeight * $ratio;
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
	
	/**
	 * 对图片进行补白
	 *
	 * @param resource $overMw
	 * @param int $overW
	 * @param int $overH
	 * @param int $bgW
	 * @param int $bgH
	 *
	 * @return bool
	 */
	function _padImage(&$overMw, $overW, $overH, $bgW, $bgH)
	{
		if (!$this->_isValid) {
			return $this->_isValid;
		}

		$bgMw = NewMagickWand();
		$this->_isValid = MagickNewImage($bgMw, $bgW, $bgH, $this->bgColor);
		if (!$this->_isValid) {
			$this->_errors[] = 'MagickNewImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($bgMw);
			return  $this->_isValid;
		}

		$cropX = (int) abs($bgW - $overW) / 2;
		$cropY = (int) abs($bgH - $overH) / 2;
		$this->_isValid = MagickCompositeImage($bgMw, $overMw, MW_OverCompositeOp, $cropX, $cropY);
		if (!$this->_isValid) {
			$this->_errors[] = 'MagickCompositeImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($overMw);
			return $this->_isValid;
		} else {
			if ($overMw) {
				DestroyMagickWand($overMw);
			}

			$overMw = $bgMw;
		}

		return $this->_isValid;
	}

	/**
	 * 计算水印坐标。
	 *
	 * @param resource $mw
	 * @param resource $wmw
	 * @param int $mode
	 *
	 * @return bool|array 当水印图片有一边较缩略图大时，不加水印
	 */
	function _makePosition(&$mw, &$wmw, $mode)
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
			case XIMAGICK_TOP_LEFT:
				break;
			case XIMAGICK_TOP_RIGHT:
				$posX = $mW - $wW;
				$posY = 0;
				break;
			case XIMAGICK_BOTTOM_RIGHT:
				$posX = $mW - $wW;
				$posY = $mH - $wH;
				break;
			case XIMAGICK_BOTTOM_LEFT:
				$posX = 0;
				$posY = $mH - $wH;
				break;
			default:
				break;
		}

		return array('x' => $posX, 'y' => $posY);
	}

	/**
	 * 初始化图片信息，并对图片旋转，如果设定了旋转角度。
	 *
	 * @return bool
	 */
	function _init()
	{
		$info = @getimagesize($this->_oriFile);
		if (!$info) {
			$this->_isValid = false;
			$this->_errors[] = "Read file {$this->_oriFile} failure.";
			return $this->_isValid;
		}

		if (!isset($this->formats[$info[2]])) {
			$this->_isValid = false;
			$this->_errors[] = "Image's format is invalid.";
			return $this->_isValid;
		}
		
		$this->_initWidth = $info[0];
		$this->_initHeight = $info[1];
		if ($this->format === null) {
			$this->format = $this->formats[$info[2]];
		}
		
		$this->_srcMw = NewMagickWand();
		$this->_isValid = MagickReadImage($this->_srcMw, $this->_oriFile);
		if (!$this->_isValid) {
			$this->_errors[] = 'MagickReadImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_srcMw);
			return $this->_isValid;
		}
		
		if ($this->degrees) {
			$bg = NewPixelWand($this->bgColor);
			$this->_newPixelWands[] = &$bg;
			$this->_isValid = MagickRotateImage($this->_srcMw, $bg, $this->degrees);
			if (!$this->_isValid) {
				$this->_errors[] = 'MagickRotateImage ' . basename(__FILE__) . ' ' . __LINE__ . ' ' . WandGetExceptionString($this->_srcMw);
				return $this->_isValid;
			}
		}

		return $this->_isValid;
	}
}
