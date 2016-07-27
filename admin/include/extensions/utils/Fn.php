<?php
/**
 * 辅助函数集合
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: Fn.php 244 2012-04-01 02:25:09Z mole1230 $
 */
class Fn
{
	/**
	 * 判断并转换字符编码，需 mb_string 模块支持。
	 *
	 * @param mixed $str 数据
	 * @param string $encoding 要转换成的编码类型
	 * @return mixed 转换过的数据
	 */
	public static function encodingConvert($str, $encoding = 'UTF-8')
	{
		if (is_array($str)) {
			$arr = array();
			foreach ($str as $key => $val) {
				$arr[$key] = self::encodingConvert($val, $encoding);
			}

			return $arr;
		}

		$_encoding = mb_detect_encoding($str, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
		if ($_encoding == $encoding) {
			return $str;
		}

		return mb_convert_encoding($str, $encoding, $_encoding);
	}

	/**
	 * 加密，解密方法。
	 *
	 * @param string $string
	 * @param string $key
	 * @param string $operation encode|decode
	 * @return string
	 */
	public static function crypt($string, $key, $operation = 'encode')
	{
		$keyLength = strlen($key);
		$string = (strtolower($operation) == 'decode') ? base64_decode($string) : substr(md5($string . $key) , 0, 8) . $string;
		$stringLength = strlen($string);
		$rndkey = $box = array();
		$result = '';

		for ($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($key[$i % $keyLength]);
			$box[$i] = $i;
		}

		for ($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}

		for ($a = $j = $i = 0; $i < $stringLength; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if (strtolower($operation) == 'decode') {
			if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key) , 0, 8)) {
				return substr($result, 8);
			} else {
				return '';
			}
		} else {
			return base64_encode($result);
		}
	}
	
	/**
	 * 获取IP地址，可能获取代理IP地址。
	 *
	 * @return string
	 */
	public static function getIp()
	{
		static $ip = false;

		if (false != $ip) {
			return $ip;
		}

		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED'
		);

		foreach ($keys as $item) {
			if (!isset($_SERVER[$item])) {
				continue;
			}

			$curIp = $_SERVER[$item];
			$curIp = explode('.', $curIp);
			if (count($curIp) != 4) {
				continue;
			}

			foreach ($curIp as & $sub) {
				if (($sub = intval($sub)) < 0 || $sub > 255) {
					continue 2;
				}
			}

			return $ip = implode('.', $curIp);
		}

		return $ip = $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 将byte转换成可读形式，或将可读形式转换成 byte。
	 *
	 * <code>
	 * echo formatBytes('1M'); // 1048576
	 * echo formatBytes('1048576'); // 1M
	 * </code>
	 *
	 * @param int|string $bytes
	 * @return string|int
	 */
	public static function formatBytes($bytes)
	{
		if (is_numeric($bytes)) {
			if ($bytes >= 1073741824) {
				$bytes = round($bytes / 1073741824 * 100) / 100 . 'GB';
			} elseif ($bytes >= 1048576) {
				$bytes = round($bytes / 1048576 * 100) / 100 . 'MB';
			} elseif ($bytes >= 1024) {
				$bytes = round($bytes / 1024 * 100) / 100 . 'KB';
			} else {
				$bytes = $bytes . 'Bytes';
			}
		} else {
			$match = array();
			if (preg_match('/((?P<type>G|M|K|GB|MB|KB))/i', $bytes, $match)) {
				$bytes = floatval($bytes);
				switch (strtoupper($match['type'])) {
					case 'G':
					case 'GB':
						$bytes *= 1073741824;
						break;
					case 'M':
					case 'MB':
						$bytes *= 1048576;
						break;
					case 'K':
					case 'KB':
						$bytes *= 1024;
						break;
					default:
						break;
				}
			}
		}

		return $bytes;
	}

	/**
	 * 检查上传的文件是否有效。
	 *
	 * @param string $name 字段名
	 * @param string $maxsize 文件最大值 (e.g. 1M)
	 * @param string $exts 合符要求的扩展名 (e.g. jpg,jpeg,gif,png)
	 * @return string 无错返回空字符串
	 */
	public static function checkUploadFile($name, $maxsize, $exts)
	{
		$err = '';
		$upfile = @$_FILES[$name];
		if (!isset($upfile)) {
			$err = 'B00201';
		} elseif (!empty($upfile['error'])) {
			switch ($upfile['error']) {
				case '1':
					$err = 'B00202';
					break;
				case '2':
					$err = 'B00203';
					break;
				case '3':
					$err = 'B00204';
					break;
				case '4':
					$err = 'B00205';
					break;
				case '6':
					$err = 'B00206';
					break;
				case '7':
					$err = 'B00207';
					break;
				case '8':
					$err = 'B00208';
					break;
				case '999':
				default:
					$err = 'B00209';
					break;
			}
		} elseif (empty($upfile['tmp_name']) || $upfile['tmp_name'] == 'none') {
			$err = 'B00205';
		} elseif ($upfile['size'] > self::formatBytes($maxsize)) {
			$err = 'B00210';
		} else {
			$ext = pathinfo($upfile['name'], PATHINFO_EXTENSION);
			$pattern = '/' . str_replace(',', '|', $exts) . '/i';
			if (!preg_match($pattern, $ext)) {
				$err = 'B00211';
			}
		}

		return $err;
	}

	/**
	 * 实现PHP内部函数 trim 处理多维数组。
	 *
	 * @param string|array $data
	 * @param string $charlist
	 */
	public static function retrim($data, $charlist = null)
	{
		if (is_array($data)) {
			foreach ($data as $item) {
				$data = self::retrim($item);
			}
		} else {
			$data = trim($data, $charlist);
		}

		return $data;
	}

	/**
	 * 推算过去某周的起始时间和终止时间，时间为时间戳。
	 *
	 * @param int $weeks 向前或向后推迟周数。
	 * @param int $start 将那天做为一周的开始。(0为星期日-6星期六)
	 * @return array
	 */
	public static function pastWeek($weeks = -1, $start = 0)
	{
		$time	= time();
		$index	= date('w', $time);
		$endTime	= strtotime(date('Y-m-d', $time)) - ($index - $start) * 86400;
		$startTime	= strtotime($weeks . ' week', $endTime);

		return array('start' => $startTime, 'end' => $endTime);
	}

	/**
	 * 用 mb_strimwidth 来截取字符，使中英尽量对齐。
	 *
	 * @param string $str
	 * @param int $start
	 * @param int $width
	 * @param string $trimmarker
	 * @return string
	 */
	public static function wsubstr($str, $start, $width, $trimmarker = '...')
	{
		$_encoding = mb_detect_encoding($str, array('ASCII','UTF-8','GB2312','GBK','BIG5'));
		return mb_strimwidth($str, $start, $width, $trimmarker, $_encoding);
	}

	/**
	 * 处理带有HTML标签字符串截取。
	 *
	 * @param string $str
	 * @param int $num
	 * @param bool $more
	 * @return string
	 */
	public static function substrHtml($str, $num, $more = false)
	{
		$_encoding = strtoupper(mb_detect_encoding($str, array('ASCII','UTF-8','GB2312','GBK','BIG5')));
		if ($_encoding != 'UTF-8') {
			$str = mb_convert_encoding($str, 'UTF-8', $_encoding);
		}
		$str = str_replace('&nbsp;', chr(1), $str);

		$length = strlen($str);
		if ($num >= $length) {
			return $str;
		}

		$word = 0;
		$i = 0;

		$stag = array();
		$etag = array();

		$sp = 0;
		$ep = 0;
		while ($word != $num && $i < $length) {
			$code = ord($str[$i]);
			if ($code > 128) {
				if ($code > 240) {
					$i += 4;
				} else if ($code > 224) {
					$i += 3;
				} else {
					$i += 2;
				}
				$word++;
			} else if ($str[$i] == '<') {
				if ($str[$i + 1] == '!') {
					for (; $i < $length; $i++) {
						if ($str[i] == '>') {
							break;
						}
					}
					$i += 1;
				}

				if ($str[$i + 1] == '/') {
					$ptag = &$etag;
					$k = &$ep;
					$i += 2;
				} else {
					$ptag = &$stag;
					$k = &$sp;
					$i += 1;
				}

				$ptag[$k] = array();
				for (; $i < $length; $i++) {
					if ($str[$i] == ' ') {
						for (; $i < $length; $i++) {
							if ($str[$i] == '>') {
								break;
							}
						}
					}
					if ($str[$i] != '>') {
						$ptag[$k][] = $str[$i];
						continue;
					} else {
						$ptag[$k] = implode('', $ptag[$k]);
						$k++;
						break;
					}
				}
				$i++;
				continue;
			} else {
				$word++;
				$i++;
			}
		}

		foreach ($etag as $val) {
			$key = array_search($val, $stag);
			if ($key !== false) {
				unset($stag[$key]);
			}
		}
		foreach ($stag as $key => $val) {
			if (in_array($val, array('br', 'img', 'hr', 'input'))) {
				unset($stag[$key]);
			}
		}

		$stag = array_reverse($stag);
		if (!empty($stag)) {
			$ends = '</' . implode('></', $stag) . '>';
		} else {
			$ends = '';
		}
		$re = substr($str, 0, $i) . $ends;
		if ($more) {
			$re .= '...';
		}

		$re = str_replace(chr(1), '&nbsp;', $re);
		if ($_encoding != 'UTF-8') {
			$re = mb_convert_encoding($re, $_encoding, 'UTF-8');
		}

		return $re;
	}

	/**
	 * 通过CURL库进POST数据提交
	 *
	 * @param string $postUrl  url address
	 * @param array $data  post data
	 * @param int $timeout connect time out
	 */
	public static function curlPost($postUrl, $data = array(), $timeout = 30)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $postUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLINFO_HEADER_OUT, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, 'pre_', '&'));

		$result = curl_exec($ch);
		curl_close($ch);

		if ($result === false) {
			return $result;
		}

		return trim($result);
	}
	
	/**
	 * 将秒转换成时间
	 * 
	 * @param int $time
	 * @return array|false
	 */
	public static function sec2Time($time)
	{
		if (is_numeric($time)) {
			$value = array('years' => 0, 'days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0);
			if ($time >= 31556926) {
				$value['years'] = floor($time / 31556926);
				$time = ($time % 31556926);
			}
			if ($time >= 86400) {
				$value['days'] = floor($time / 86400);
				$time = ($time % 86400);
			}
			if ($time >= 3600) {
				$value['hours'] = floor($time / 3600);
				$time = ($time % 3600);
			}
			if ($time >= 60) {
				$value['minutes'] = floor($time / 60);
				$time = ($time % 60);
			}
			$value['seconds'] = floor($time);
			return $value;
		} else {
			return false;
		}
	}
	
	/**
	 * 使用 Yiidebugtb 调试功能
	 */
	public static function debug()
	{
		define('YII_DEBUG_TB', true);
	}
	
	/**
	 * 收集二维数组中某个字段，生成一维数组，
	 * 一般用于处理数据库中返回二维数组。如收集其中的ID字段。
	 * 
	 * @param array $array
	 * @param string $key 字段名
	 * @return array
	 */
	public static function arrayCollectField($array, $key)
	{
		$data = array();
		foreach ($array as $item) {
			$data[] = $item[$key];
		}
		
		return $data;
	}
	
	/**
	 * 收集二维数组中某两个字段，并组成键值对数组，
	 * 一般用于处理数据库中返回二维数组。
	 * 
	 * @param array $array
	 * @param string $keyKey 字段名
	 * @param string $valueKey 字段名
	 * @return array
	 */
	public static function arrayCollectPair($array, $keyKey, $valueKey)
	{
		$data = array();
		foreach ($array as $item) {
			$data[$item[$keyKey]] = $item[$valueKey];
		}
		
		return $data;
	}
	
	/**
	 * 将数组转成对象。
	 * 
	 * @param array $array
	 * @param string $class 类名，默认为stdClass。 
	 * @return mixed
	 */
	public static function arrayToObject($array, $class = 'stdClass')
	{
		$object = new $class();
		
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				// Convert the array to an object
				$value = self::toObject($value, $class);
			}
			
			// Add the value to the object
			$object->{$key} = $value;
		}
		
		return $object;
	}
	
	/**
	 * 合并多个数组，数字键被追加字符键被覆盖。
	 * 
	 * @return array
	 */
	public static function arrayMerge()
	{
		$total = func_num_args();
		
		$result = array();
		for ($i = 0; $i < $total; $i++) {
			foreach (func_get_arg($i) as $key => $val) {
				if (isset($result[$key])) {
					if (is_array($val)) {
						// Arrays are merged recursively
						$result[$key] = self::merge($result[$key], $val);
					} elseif (is_int($key)) {
						// Indexed arrays are appended
						array_push($result, $val);
					} else {
						// Associative arrays are replaced
						$result[$key] = $val;
					}
				} else {
					// New values are added
					$result[$key] = $val;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * 后面数组覆盖前面数组中已存在键的值，对于不存在的键将不会被追加。
	 * 
	 * @param array $array1
	 * @return array
	 */
	public static function arrayOverwrite($array1)
	{
		foreach (array_slice(func_get_args(), 1) as $array2) {
			foreach ($array2 as $key => $value) {
				if (array_key_exists($key, $array1)) {
					$array1[$key] = $value;
				}
			}
		}
		
		return $array1;
	}
	
	/**
	 * 将多维数组转换成一维数组
	 *
	 * @param array $array
	 * @return array
	 */
	public static function arrayFlat($array)
	{
		$data = array ();
		if (is_array($array)) {
			foreach ( $array as $value ) {
				$data = array_merge($data, self::arrayFlat($value));
			}
		} else {
			$data[] = $array;
		}
		
		return $data;
	}
	
	public static function ip2long6($ipv6)
	{
		$ipv6long = '';
		$ipn = inet_pton($ipv6);
		$bits = 15;
		
		while ($bits >= 0) {
			$bin = sprintf("%08b", (ord($ipn[$bits])));
			$ipv6long = $bin . $ipv6long;
			$bits--;
		}
		
		return gmp_strval(gmp_init($ipv6long, 2), 10);
	}
	
	public static function long2ip6($ipv6long)
	{
		$bin = gmp_strval(gmp_init($ipv6long, 10), 2);
		
		if (strlen($bin) < 128) {
			$bin = str_pad($bin, 128 - strlen($bin), '0', STR_PAD_LEFT);
		}
		
		$bits = 0;
		while ($bits <= 7) {
			$bin_part = substr($bin, ($bits * 16), 16);
			$ipv6 .= dechex(bindec($bin_part)) . ":";
			$bits++;
		}
		
		return inet_ntop(inet_pton(substr($ipv6, 0, -1)));
	} 
}
