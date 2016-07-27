<?php
/**
 * GD Image Driver.
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: Gd.php 210 2011-12-31 05:36:56Z mole1230 $
 */
class GdDriver extends ImageDriver
{
	public function process($image, $actions, $file, $format)
	{
		$this->image = $image;
		switch ($this->image['type']) {
			case IMAGETYPE_GIF:
				$this->tmpImage = imagecreatefromgif($this->image['file']);
				break;
			case IMAGETYPE_JPEG:
				$this->tmpImage = imagecreatefromjpeg($this->image['file']);
				break;
			case IMAGETYPE_PNG:
				$this->tmpImage = imagecreatefrompng($this->image['file']);
				break;
			default:
				break;
		}
		
		$quality = $this->quality($actions);
		$quality = $quality ? $quality : 90;
		$status = $this->execute($actions);

		if ($status) {
			$type = $this->image['type'];
			if (!empty($format)) {
				$type = $format;
			}
			if (empty($file)) {
				header('Content-Type: ' . image_type_to_mime_type($type));
				switch ($type) {
					case IMAGETYPE_GIF:
						imagegif($this->tmpImage);
						break;
					case IMAGETYPE_JPEG:
						imagejpeg($this->tmpImage, null, $quality);
						break;
					case IMAGETYPE_PNG:
						imagepng($this->tmpImage);
						break;
				}
			} else {
				switch ($type) {
					case IMAGETYPE_GIF:
						imagegif($this->tmpImage, $file);
						break;
					case IMAGETYPE_JPEG:
						imagejpeg($this->tmpImage, $file, $quality);
						break;
					case IMAGETYPE_PNG:
						imagepng($this->tmpImage, $file);
						break;
				}
			}
		}
		
		imagedestroy($this->tmpImage);
		$this->tmpImage = null;
		return $status;
	}
	
	public function resize($prop)
	{
		$width = imagesx($this->tmpImage);
		$height = imagesy($this->tmpImage);
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
					$status = $this->thumbnailImage($prop['width'], 0, false);
				}
				break;
			case Image::RESIZE_HEIGHT:
				if ($prop['enlarge'] || $prop['height'] < $height) {
					$status = $this->thumbnailImage(0, $prop['height'], false);
				}
				break;
			case Image::RESIZE_AUTO:
				if ($prop['enlarge'] || $prop['width'] < $width || $prop['height'] < $height) {
					$status = $this->thumbnailImage($prop['width'], $prop['height'], true);
				}
				break;
			case Image::RESIZE_FIXED:
				if ($prop['enlarge'] || $prop['width'] < $width && $prop['height'] < $height) {
					$status = $this->thumbnailImage($prop['width'], $prop['height'], false);
				}
				break;
			case Image::RESIZE_CROP:
				if ($prop['enlarge'] || $prop['width'] < $width || $prop['height'] < $height) {
					$status = $this->cropThumbnailImage($prop['width'], $prop['height']);
				}
				break;
			default:
				break;
		}

		return $status;
	}
	
	public function flip($direction)
	{
		$width = imagesx($this->tmpImage);
		$height = imagesy($this->tmpImage);
		
		$img = imagecreatetruecolor($width, $height);
		if ($direction == Image::FLIP_HORIZONTAL) {
			for ($px = 0; $px < $width; $px++) {
				for ($py = 0; $py < $height; $py++) {
					imagecopy($img, $this->tmpImage, $width - $px - 1, $py, $px, $py, 1, 1);
				}
			}
		} else {
			for ($px = 0; $px < $width; $px++) {
				for ($py = 0; $py < $height; $py++) {
					imagecopy($img, $this->tmpImage, $px, $height - $py - 1, $px, $py, 1, 1);
				}
			}
		}
		imagedestroy($this->tmpImage);
		$this->tmpImage = $img;
		
		return true;
	}
	
	public function crop($prop)
	{
		$this->sanitizeGeometry($prop);
		$width = imagesx($this->tmpImage);
		$height = imagesy($this->tmpImage);
		$size = array(
			'w' => $prop['width'],
			'h' => $prop['height'],
			'x' => $prop['left'],
			'y' => $prop['top']
		);
		
		if ($prop['left'] > $width) {
			$size = array(
				'w' => 1,
				'h' => 1,
				'x' => 0,
				'y' => 0
			);
		} elseif ($prop['top'] > $height) {
			$size = array(
				'w' => 1,
				'h' => 1,
				'x' => 0,
				'y' => 0
			);
		} else {
			if ($prop['left'] + $prop['width'] > $width) {
				$size['w'] = $width - $size['x'];
			}
			if ($prop['top'] + $prop['height'] > $height) {
				$size['h'] = $height - $size['y'];
			}
		}

		$img = imagecreatetruecolor($size['w'], $size['h']);
		imagecopyresampled($img, $this->tmpImage, 0, 0, $size['x'], $size['y'], $size['w'], $size['h'], $size['w'], $size['h']);
		imagedestroy($this->tmpImage);
		$this->tmpImage = $img;
		
		return true;
	}

	public function rotate($prop)
	{
		switch ($prop['degrees']) {
			case 90:
			case -90:
				$this->rotateFixed($prop['degrees']);
				break;
			case 180:
			case -180:
				$this->rotateFixed($prop['degrees']);
				break;
			default:
				$this->rotateFree($prop['degrees'], $prop['bgcolor']);
				break;
		}
		return true;
	}
	
	protected function rotateFixed($degrees)
	{
		$width = imagesx($this->tmpImage);
		$height = imagesy($this->tmpImage);
		$img = imagecreatetruecolor($height, $width);
		
		if ($degrees > 0) {
			for ($px = 0; $px < $width; $px++) {
				for ($py = 0; $py < $height; $py++) {
					imagecopy($img, $this->tmpImage, $height - $py - 1, $px, $px, $py, 1, 1);
				}
			}
		} else {
			for ($px = 0; $px < $width; $px++) {
				for ($py = 0; $py < $height; $py++) {
					imagecopy($img, $this->tmpImage, $py, $width - $px - 1, $px, $py, 1, 1);
				}
			}
		}
		imagedestroy($this->tmpImage);
		$this->tmpImage = $img;
		return true;
	}
	
	protected function rotateFree($degrees, $bgcolor)
	{
		$width = imagesx($this->tmpImage);
		$height = imagesy($this->tmpImage);
		if (empty($bgcolor)) {
			$bgcolor = '#000000';
		}
		
		if ($degrees != 0) {
			$centerx = floor($width / 2);
			$centery = floor($height / 2);
			$maxsizex = ceil(abs(cos(deg2rad($degrees)) * $width) + abs(sin(deg2rad($degrees)) * $height));
			$maxsizey = ceil(abs(sin(deg2rad($degrees)) * $width) + abs(cos(deg2rad($degrees)) * $height));
			if ($maxsizex & 1) {
				$maxsizex += 3;
			} else {
				$maxsizex += 2;
			}
			if ($maxsizey & 1) {
				$maxsizey += 3;
			} else {
				$maxsizey += 2;
			}
			$img = imagecreatetruecolor($maxsizex, $maxsizey);
			imagefilledrectangle($img, 0, 0, $maxsizex, $maxsizey, imagecolorallocate($img, hexdec(substr($bgcolor, 1, 2)), hexdec(substr($bgcolor, 3, 2)), hexdec(substr($bgcolor, 5, 2))));
			$newcenterx = $maxsizex / 2;
			$newcentery = $maxsizey / 2;
			$degrees += 180;
			for ($px = 0; $px < $maxsizex; $px++) {
				for ($py = 0; $py < $maxsizey; $py++) {
					$vectorx = floor(($newcenterx - $px) * cos(deg2rad($degrees)) + ($newcentery - $py) * sin(deg2rad($degrees)));
					$vectory = floor(($newcentery - $py) * cos(deg2rad($degrees)) - ($newcenterx - $px) * sin(deg2rad($degrees)));
					if (($centerx + $vectorx) > -1 && ($centerx + $vectorx) < ($centerx * 2) && ($centery + $vectory) > -1 && ($centery + $vectory) < ($centery * 2)) {
						imagecopy($img, $this->tmpImage, $px, $py, $centerx + $vectorx, $centery + $vectory, 1, 1);
					}
				}
			}
			imagedestroy($this->tmpImage);
			$this->tmpImage = $img;
		}
		
		return true;
	}
	
	public function sharpen($times)
	{
		$matrix = array(
			array(-1, -1, -1), 
			array(-1, 16, -1), 
			array(-1, -1, -1)
		);
		$divisor = 8;
		for ($i = 0; $i < $times; $i++) {
			imageconvolution($this->tmpImage, $matrix, $divisor, 0);
		}
		
		return true;
	}
	
	public function blur($times)
	{
		$matrix = array(
			array(1, 2, 1),
			array(2, 4, 2),
			array(1, 2, 1)
		);
		$divisor = 16;
		for ($i = 0; $i < $times; $i++) {
			imageconvolution($this->tmpImage, $matrix, $divisor, 0);
		}
		
		return true;
	}
	
	protected function properties()
	{
		return array(
			imagesx($this->tmpImage),
			imagesy($this->tmpImage)
		);
	}
	
	protected function thumbnailImage($width, $height, $bestfit = false)
	{
		$sw = imagesx($this->tmpImage);
		$sh = imagesy($this->tmpImage);
		$dw = $width;
		$dh = $height;
		
		$size = array(
			'w' => $dw,
			'h' => $dh
		);
		if (empty($dw)) {
			$size = array(
				'w' => intval($dh / $sh * $sw),
				'h' => $dh
			);
		} elseif (empty($dh)) {
			$size = array(
				'w' => $dw,
				'h' => intval($dw / $sw * $sh)
			);
		} elseif ($bestfit) {
			$r = min($dw / $sw, $dh / $sh);
			$size = array(
				'w' => intval($sw * $r),
				'h' => intval($sh * $r)
			);
		}
		
		return $this->resizeImage($sw, $sh, $size['w'], $size['h']);
	}
	
	protected function cropThumbnailImage($width, $height)
	{
		$sw = imagesx($this->tmpImage);
		$sh = imagesy($this->tmpImage);
		$dw = $width;
		$dh = $height;
		
		$r = max($dw / $sw, $dh / $sh);
		$size = array(
			'w' => intval($sw * $r),
			'h' => intval($sh * $r)
		);
		$this->resizeImage($sw, $sh, $size['w'], $size['h']);
		
		$x = 0;
		$y = 0;
		if ($size['w'] > $dw) {
			$x = intval(($size['w'] - $dw) / 2);
		} elseif ($size['h'] > $dh) {
			$y = intval(($size['h'] - $dh) / 2); 
		}
		
		$img = imagecreatetruecolor($dw, $dh);
		imagecopyresampled($img, $this->tmpImage, 0, 0, $x, $y, $dw, $dh, $dw, $dh);
		imagedestroy($this->tmpImage);
		$this->tmpImage = $img;
		
		return true;
	}
	
	protected function resizeImage($sw, $sh, $dw, $dh)
	{
		//@todo exception handle
		$img = imagecreatetruecolor($dw, $dh);
		imagecopyresampled($img, $this->tmpImage, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
		imagedestroy($this->tmpImage);
		$this->tmpImage = $img;
		
		return true;
	}
}
