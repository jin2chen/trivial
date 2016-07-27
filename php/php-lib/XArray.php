<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: XArray.php 185 2011-11-06 14:05:10Z mole1230 $
 */
class XArray
{
	/**
	 * Return a callback array from a string, eg: limit[10,20] would become
	 * array('limit', array('10', '20'))
	 *
	 * @param   string  callback string
	 * @return  array
	 */
	public static function callbackString($str)
	{
		// command[param,param]
		if (preg_match('/([^\[]*+)\[(.+)\]/', (string) $str, $match)) {
			$command = $match[1];
			$params = preg_split('/(?<!\\\\),/', $match[2]);
			$params = str_replace('\,', ',', $params);
		} else {
			// command
			$command = $str;
			$params = null;
		}
		
		return array(
			$command, 
			$params
		);
	}
	
	/**
	 * Rotates a 2D array clockwise.
	 * Example, turns a 2x3 array into a 3x2 array.
	 *
	 * @param   array    array to rotate
	 * @param   boolean  keep the keys in the final rotated array. the sub arrays of the source array need to have the same key values.
	 *                   if your subkeys might not match, you need to pass false here!
	 * @return  array
	 */
	public static function rotate($sourceArray, $keepKeys = true)
	{
		$newArray = array();
		foreach ($sourceArray as $key => $value) {
			$value = ($keepKeys === true) ? $value : array_values($value);
			foreach ($value as $k => $v) {
				$newArray[$k][$key] = $v;
			}
		}
		
		return $newArray;
	}
	
	/**
	 * Removes a key from an array and returns the value.
	 *
	 * @param   string  key to return
	 * @param   array   array to work on
	 * @return  mixed   value of the requested array key
	 */
	public static function remove($key, &$array)
	{
		if (!array_key_exists($key, $array)) {
			return null;
		}		
		$val = $array[$key];
		unset($array[$key]);
		
		return $val;
	}
	
	/**
	 * Extract one or more keys from an array. Each key given after the first
	 * argument (the array) will be extracted. Keys that do not exist in the
	 * search array will be null in the extracted data.
	 *
	 * @param   array   array to search
	 * @param   string  key name
	 * @return  array
	 */
	public static function extract($search, $keys)
	{
		// Get the keys, removing the $search array
		$keys = array_slice(func_get_args(), 1);
		
		$found = array();
		foreach ($keys as $key) {
			if (isset($search[$key])) {
				$found[$key] = $search[$key];
			} else {
				$found[$key] = null;
			}
		}
		
		return $found;
	}
	
	/**
	 * Because PHP does not have this function.
	 *
	 * @param   array   array to unshift
	 * @param   string  key to unshift
	 * @param   mixed   value to unshift
	 * @return  array
	 */
	public static function unshiftAssoc(&$array, $key, $val)
	{
		$array = array_reverse($array, true);
		$array[$key] = $val;
		$array = array_reverse($array, true);
		
		return $array;
	}
	
	/**
	 * Because PHP does not have this function, and array_walk_recursive creates
	 * references in arrays and is not truly recursive.
	 *
	 * @param   mixed  callback to apply to each member of the array
	 * @param   array  array to map to
	 * @return  array
	 */
	public static function mapRecursive($callback, $array)
	{
		foreach ($array as $key => $val) {
			// Map the callback to the key
			$array[$key] = is_array($val) ? self::mapRecursive($callback, $val) : call_user_func($callback, $val);
		}
		
		return $array;
	}
	
	/**
	 * Binary search algorithm.
	 *
	 * @param   mixed    the value to search for
	 * @param   array    an array of values to search in
	 * @param   boolean  return false, or the nearest value
	 * @param   mixed    sort the array before searching it
	 * @return  integer
	 */
	public static function binarySearch($needle, $haystack, $nearest = false, $sort = false)
	{
		if ($sort === true) {
			sort($haystack);
		}
		
		$high = count($haystack);
		$low = 0;
		
		while ($high - $low > 1) {
			$probe = ($high + $low) / 2;
			if ($haystack[$probe] < $needle) {
				$low = $probe;
			} else {
				$high = $probe;
			}
		}
		
		if ($high == count($haystack) or $haystack[$high] != $needle) {
			if ($nearest === false) {
				return false;
			}
			
			// return the nearest value
			$high_distance = $haystack[ceil($low)] - $needle;
			$low_distance = $needle - $haystack[floor($low)];
			
			return ($high_distance >= $low_distance) ? $haystack[ceil($low)] : $haystack[floor($low)];
		}
		
		return $high;
	}
	
	/**
	 * Emulates array_merge_recursive, but appends numeric keys and replaces
	 * associative keys, instead of appending all keys.
	 *
	 * @param   array  any number of arrays
	 * @return  array
	 */
	public static function merge()
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
	 * Overwrites an array with values from input array(s).
	 * Non-existing keys will not be appended!
	 *
	 * @param   array   key array
	 * @param   array   input array(s) that will overwrite key array values
	 * @return  array
	 */
	public static function overwrite($array1)
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
	 * Fill an array with a range of numbers.
	 *
	 * @param   integer  stepping
	 * @param   integer  ending number
	 * @return  array
	 */
	public static function range($step = 10, $max = 100)
	{
		if ($step < 1) {
			return array();
		}
		
		$array = array();
		for ($i = $step; $i <= $max; $i += $step) {
			$array[$i] = $i;
		}
		
		return $array;
	}
	
	/**
	 * Recursively convert an array to an object.
	 *
	 * @param   array   array to convert
	 * @return  object
	 */
	public static function toObject($array, $class = 'stdClass')
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
}
