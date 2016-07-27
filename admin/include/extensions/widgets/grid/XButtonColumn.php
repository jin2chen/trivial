<?php
/**
 * XButtonColumn class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * XButtonColumn represents a grid view column that renders one or several buttons.
 *
 * By default, it will display three buttons, "view", "update" and "delete", which triggers the corresponding
 * actions on the model of the row.
 *
 * By configuring {@link buttons} and {@link template} properties, the column can display other buttons
 * and customize the display order of the buttons.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: XButtonColumn.php 149 2011-07-22 18:39:37Z mole1230 $
 * @package zii.widgets.grid
 * @since 1.1
 */
class XButtonColumn extends XGridColumn
{	
	/**
	 * @var string the template that is used to render the content in each data cell.
	 * These default tokens are recognized: {view}, {update} and {delete}. If the {@link buttons} property
	 * defines additional buttons, their IDs are also recognized here. For example, if a button named 'preview'
	 * is declared in {@link buttons}, we can use the token '{preview}' here to specify where to display the button.
	 */
	public $template = '';
		
	/**
	 * @var array the configuration for additional buttons. Each array element specifies a single button
	 * which has the following format:
	 * <pre>
	 * 'buttonID' => array(
	 * 'label'=>'...',     // text label of the button
	 * 'url'=>'...',       // a PHP expression for generating the URL of the button
	 * 'imageUrl'=>'...',  // image URL of the button. If not set or false, a text link is used
	 * 'options'=>array(...), // HTML options for the button tag
	 * 'click'=>'...',     // a JS function to be invoked when the button is clicked
	 * 'visible'=>'...',   // a PHP expression for determining whether the button is visible
	 * )
	 * </pre>
	 * In the PHP expression for the 'url' option and/or 'visible' option, the variable <code>$row</code>
	 * refers to the current row number (zero-based), and <code>$data</code> refers to the data model for
	 * the row.
	 *
	 * Note that in order to display these additional buttons, the {@link template} property needs to
	 * be configured so that the corresponding button IDs appear as tokens in the template.
	 */
	public $buttons = array();
	
	/**
	 * Initializes the column.
	 * This method registers necessary client script for the button column.
	 */
	public function init()
	{
		foreach ($this->buttons as $id => $button) {
			if (strpos($this->template, '{' . $id . '}') === false) {
				unset($this->buttons[$id]);
			}
		}
	}
	
	/**
	 * Renders the data cell content.
	 * This method renders the view, update and delete buttons in the data cell.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderDataCellContent($row, $data)
	{
		$tr = array();
		ob_start();
		foreach ($this->buttons as $id => $button) {
			$this->renderButton($id, $button, $row, $data);
			$tr['{' . $id . '}'] = ob_get_contents();
			ob_clean();
		}
		ob_end_clean();
		echo strtr($this->template, $tr);
	}
	
	/**
	 * Renders a link button.
	 * @param string $id the ID of the button
	 * @param array $button the button configuration which may contain 'label', 'url', 'imageUrl' and 'options' elements.
	 * See {@link buttons} for more details.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data object associated with the row
	 */
	protected function renderButton($id, $button, $row, $data)
	{
		if (isset($button['visible']) && !$this->evaluateExpression($button['visible'], array('row' => $row, 'data' => $data))) {
			return;
		}
		
		$url = isset($button['url']) ? $this->evaluateExpression($button['url'], array('data' => $data, 'row' => $row)) : 'javascript:;';
		$options = isset($button['options']) ? $button['options'] : array();
		
		if (isset($button['evalLabel'])) {
			$label = $this->evaluateExpression($button['evalLabel'], array('row' => $row, 'data' => $data));
		} else {
			$label = isset($button['label']) ? $button['label'] : $id;
		}
		if (isset($button['evalData'])) {
			$evalData = (array) $this->evaluateExpression($button['evalData'], array('row' => $row, 'data' => $data));
		} else {
			$evalData = array();
		}
		if (isset($button['attrData'])) {
			$options['data-attr'] = json_encode(array_merge($button['attrData'], $evalData));
		}
		echo CHtml::link($label, $url, $options);
	}
}
