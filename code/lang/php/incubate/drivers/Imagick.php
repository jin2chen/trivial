<?php
/**
 * Imagick Image Driver.
 *
 * @author mole <mole1230@gmail.com>
 * @version $Id: Imagick.php 210 2011-12-31 05:36:56Z mole1230 $
 */
class ImagickDriver extends ImageDriver
{
	public function process($image, $actions, $file, $format)
	{
		$this->image = $image;
		$this->tmpImage = new Imagick($this->image['file']);

		$quality = $this->quality($actions);
		$quality = $quality ? $quality : 90;
		$status = $this->execute($actions);

		if ($status) {
			$type = $this->image['type'];
			if (!empty($format)) {
				$type = $format;
			}
			if ($type == IMAGETYPE_JPEG) {
				$this->tmpImage->setcompressionquality($quality);
			}
			$this->tmpImage->stripimage();
			$this->tmpImage->setformat(Image::$allowedTypes[$type]);
			
			if (empty($file)) {
				header('Content-Type: ' . image_type_to_mime_type($type));
				echo $this->tmpImage;
			} else {
				$this->tmpImage->writeImage($file);
			}
		}
		
		$this->tmpImage->destroy();
		$this->tmpImage = null;
		return $status;
	}
	
	public function resize($prop)
	{
		// Get the current width and height
		$width = $this->tmpImage->getImageWidth();
		$height = $this->tmpImage->getImageHeight();
		
		if (substr($prop['width'], -1) === '%') {
			$prop['width'] = round($width * (substr($prop['width'], 0, -1) / 100));
		}
		if (substr($prop['height'], -1) === '%') {
			$prop['height'] = round($height * (substr($prop['height'], 0, -1) / 100));
		}
		
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
				if ($prop['enlarge'] || $prop['width'] < $width) {
					$status = $this->tmpImage->thumbnailImage($prop['width'], 0, false);
				}
				break;
			case Image::RESIZE_HEIGHT:
				if ($prop['enlarge'] || $prop['height'] < $height) {
					$status = $this->tmpImage->thumbnailImage(0, $prop['height'], false);
				}
				break;
			case Image::RESIZE_AUTO:
				if ($prop['enlarge'] || $prop['width'] < $width || $prop['height'] < $height) {
					$status = $this->tmpImage->thumbnailImage($prop['width'], $prop['height'], true);
				}
				break;
			case Image::RESIZE_FIXED:
				if ($prop['enlarge'] || $prop['width'] < $width && $prop['height'] < $height) {
					$status = $this->tmpImage->thumbnailImage($prop['width'], $prop['height'], false);
				}
				break;
			case Image::RESIZE_CROP:
				if ($prop['enlarge'] || $prop['width'] < $width || $prop['height'] < $height) {
					$status = $this->tmpImage->cropThumbnailImage($prop['width'], $prop['height']);
				}
				break;
			default:
				break;
		}

		return $status;
	}
	
	public function rotate($prop)
	{
		if (empty($prop['bgcolor'])) {
			$prop['bgcolor'] = $this->tmpImage->getImageBackgroundColor();
		} else {
			$prop['bgcolor'] = new ImagickPixel($prop['bgcolor']);
		}
		return $this->tmpImage->rotateImage($prop['bgcolor'], $prop['degrees']);
	}
	
	public function flip($direction)
	{
		if ($direction === Image::FLIP_VERTICAL) {
			return $this->tmpImage->flipImage();
		}
		
		return $this->tmpImage->flopImage();
	}
	
	public function crop($prop)
	{
		$this->sanitizeGeometry($prop);
		return $this->tmpImage->cropImage($prop['width'], $prop['height'], $prop['left'], $prop['top']);
	}
	
	public function sharpen($times)
	{
		for ($i = 0; $i < $times; $i++) {
			$this->tmpImage->sharpenImage(2, 1);
		}
		
		return true;
	}
	
	public function blur($times)
	{
		for ($i = 0; $i < $times; $i++) {
			$this->tmpImage->blurimage(2, 1);
		}
		
		return true;
	}
	
	protected function properties()
	{
		return array(
			$this->tmpImage->getImageWidth(),
			$this->tmpImage->getImageHeight()
		);
	}
}