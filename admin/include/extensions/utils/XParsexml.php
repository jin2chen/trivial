<?php
/**
 * 将XML解析为数组
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: XParsexml.php 189 2011-12-05 15:09:48Z mole1230 $
 *
 * <code>
 * $px = new XParsexml();
 * $px->loadFileXml($filename);
 * $px->toArray();
 * </code>
 */
class XParsexml extends CComponent
{
	/**
	 * Xml 内容。
	 *
	 * @var string
	 */
	private $_content = '';

	/**
	 * Xml 解析后结果。
	 *
	 * @var array
	 */
	private $_data = array();

	/**
	 * @var bool
	 */
	private $_isParsed = false;

	/**
	 * 是否解析属性。
	 * @var bool
	 */
	private $_parseAttributes = false;

	/**
	 * 属性和值优先级。
	 *
	 * @var string 		tag|attrubite
	 */
	private $_parsePriority = 'tag';

	/**
	 * @param string $file
	 * @return void
	 */
	public function loadFileXml($file)
	{
		$this->_isParsed = false;
		$this->_content = file_get_contents($file);
	}

	/**
	 * @param string $xml
	 * @return void
	 */
	public function loadXml($xml)
	{
		$this->_isParsed = false;
		$this->_content = $xml;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$this->parse();
		return $this->_data;
	}

	/**
	 * @return string
	 */
	public function toJson()
	{
		$this->parse();
		return json_encode($this->_data);
	}

	/**
	 * xml解析核心函数，需 xml_parser_create 支持。
	 *
	 * @return void
	 */
	protected function parse()
	{
		if ($this->_isParsed) {
			return;
		}

		if (!function_exists('xml_parser_create')) {
			$this->_isParsed = true;
			trigger_error('function xml_parser_create is required', E_USER_ERROR);
			return;
		}

		$xmlValues = array();
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($this->_content) , $xmlValues);
		xml_parser_free($parser);

		if (empty($xmlValues)) {
			$this->_isParsed = true;
			return;
		}

		//Initializations
		$xmlArray = array();
		$parents = array();
		$opendTags = array();
		$arr = array();

		$current = & $xmlArray;
		$attributes = $tag = $value = $type = $level = null;
		$repeatedTagIndex = array();
		foreach ($xmlValues as $data) { //print_r($data);
			unset($attributes);
			$value = '';

			// This command will extract these variables into the foreach scope
			// tag(string), type(string), level(int), attributes(array).
			// We could use the array by itself, but this cooler.
			extract($data);
			$result = array();
			$attributesData = array();

			if (isset($value)) {
				if ($this->_parsePriority == 'tag') {
					$result = $value;
				} else {
					// Put the value in a assoc array if we are in the 'Attribute' mode
					$result['value'] = $value;
				}
			}

			// Set the attributes too.
			if (isset($attributes) && $this->_parseAttributes) {
				foreach ($attributes as $attr => $val) {
					if ($this->_parsePriority == 'tag') {
						$attributesData[$attr] = $val;
					} else {
						// Set all the attributes in a array called 'attr'
						$result['attr'][$attr] = $val;
					}
				}
			}

			// See tag status and do the needed.
			if ($type == "open") { //The starting of the tag '<tag>'
				$parents[$level - 1] = & $current;
				if (!is_array($current) || (!in_array($tag, array_keys($current)))) { //Insert New tag
					$current[$tag] = $result;
					if ($attributesData) {
						$current[$tag . '_attr'] = $attributesData;
					}
					$repeatedTagIndex[$tag . '_' . $level] = 1;
					$current = & $current[$tag];

				} else { //There was another element with the same tag name
					if (isset($current[$tag][0])) { //If there is a 0th element it is already an array
						$current[$tag][$repeatedTagIndex[$tag . '_' . $level]] = $result;
						$repeatedTagIndex[$tag . '_' . $level]++;

					} else { //This section will make the value an array if multiple tags with the same name appear together
						$current[$tag] = array(
							$current[$tag],
							$result
						);
						//This will combine the existing item and the new item together to make an array
						$repeatedTagIndex[$tag . '_' . $level] = 2;

						if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset($current[$tag . '_attr']);
						}
					}

					$lastItemIndex = $repeatedTagIndex[$tag . '_' . $level] - 1;
					$current = & $current[$tag][$lastItemIndex];
				}

			} elseif ($type == "complete") {
				//Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if (!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
					$repeatedTagIndex[$tag . '_' . $level] = 1;
					if ($this->_parsePriority == 'tag' && $attributesData) {
						$current[$tag . '_attr'] = $attributesData;
					}

				} else {
					//If taken, put all things inside a list(array)
					if (isset($current[$tag][0]) && is_array($current[$tag])) {
						//If it is already an array...
						// ...push the new element into that array.
						$current[$tag][$repeatedTagIndex[$tag . '_' . $level]] = $result;

						if ($this->_parsePriority == 'tag' && $this->_parseAttributes && $attributesData) {
							$current[$tag][$repeatedTagIndex[$tag . '_' . $level] . '_attr'] = $attributesData;
						}
						$repeatedTagIndex[$tag . '_' . $level]++;

					} else { //If it is not an array...
						$current[$tag] = array(

							$current[$tag],
							$result
						);
						//.Make it an array using using the existing value and the new value
						$repeatedTagIndex[$tag . '_' . $level] = 1;
						if ($this->_parsePriority == 'tag' && $this->_parseAttributes) {
							if (isset($current[$tag . '_attr'])) {
								//The attribute of the last(0th) tag must be moved as well
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset($current[$tag . '_attr']);
							}

							if ($attributesData) {
								$current[$tag][$repeatedTagIndex[$tag . '_' . $level] . '_attr'] = $attributesData;
							}
						}
						$repeatedTagIndex[$tag . '_' . $level]++; //0 and 1 index is already taken

					}
				}

			} elseif ($type == 'close') { //End of tag '</tag>'
				$current = & $parents[$level - 1];
			}
		}

		$this->_data = array_shift($xmlArray);
		$this->_isParsed = true;
	}

}

