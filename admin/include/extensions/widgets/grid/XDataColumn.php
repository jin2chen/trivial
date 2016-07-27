<?php
/**
 * XDataColumn class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * XDataColumn represents a grid view column that is associated with a data attribute or expression.
 *
 * Either {@link name} or {@link value} should be specified. The former specifies
 * a data attribute name, while the latter a PHP expression whose value should be rendered instead.
 *
 * The property {@link sortable} determines whether the grid view can be sorted according to this column.
 * Note that the {@link name} should always be set if the column needs to be sortable. The {@link name}
 * value will be used by {@link CSort} to render a clickable link in the header cell to trigger the sorting.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: XDataColumn.php 143 2011-07-21 08:55:20Z mole1230 $
 * @package zii.widgets.grid
 * @since 1.1
 */
class XDataColumn extends XGridColumn
{
	/**
	 * @var string the attribute name of the data model. The corresponding attribute value will be rendered
	 * in each data cell. If {@link value} is specified, this property will be ignored
	 * unless the column needs to be sortable or filtered.
	 * @see value
	 * @see sortable
	 */
	public $name;
	
	/**
	 * @var string a PHP expression that will be evaluated for every data cell and whose result will be rendered
	 * as the content of the data cells. In this expression, the variable
	 * <code>$row</code> the row number (zero-based); <code>$data</code> the data model for the row;
	 * and <code>$this</code> the column object.
	 */
	public $value;
	
	/**
	 * @var string the type of the attribute value. This determines how the attribute value is formatted for display.
	 * Valid values include those recognizable by {@link CGridView::formatter}, such as: raw, text, ntext, html, date, time,
	 * datetime, boolean, number, email, image, url. For more details, please refer to {@link CFormatter}.
	 * Defaults to 'text' which means the attribute value will be HTML-encoded.
	 */
	public $type = 'text';
	
	/**
	 * @var boolean whether the column is sortable. If so, the header cell will contain a link that may trigger the sorting.
	 * Defaults to true. Note that if {@link name} is not set, or if {@link name} is not allowed by {@link CSort},
	 * this property will be treated as false.
	 * @see name
	 */
	public $sortable = true;
	
	/**
	 * @var string default value for this column.
	 */
	public $defaultValue;
	
	/**
	 * Initializes the column.
	 */
	public function init()
	{
		parent::init();
		if ($this->name === null) {
			$this->sortable = false;
		} if ($this->name === null && $this->value === null) {
			throw new CException(Yii::t('zii', 'Either "name" or "value" must be specified for XDataColumn.'));
		}
	}
	
	/**
	 * Renders the header cell content.
	 * This method will render a link that can trigger the sorting if the column is sortable.
	 */
	protected function renderHeaderCellContent()
	{
		if ($this->grid->enableSorting && $this->sortable && $this->name !== null) {
			echo $this->grid->dataProvider->getSort()->link($this->name, $this->header);
		} elseif ($this->name !== null && $this->header === null) {
			if ($this->grid->dataProvider instanceof CActiveDataProvider) {
				echo CHtml::encode($this->grid->dataProvider->model->getAttributeLabel($this->name));
			} else {
				echo CHtml::encode($this->name);
			}
		} else {
			parent::renderHeaderCellContent();
		}
	}
	
	/**
	 * Renders the data cell content.
	 * This method evaluates {@link value} or {@link name} and renders the result.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderDataCellContent($row, $data)
	{
		if ($this->value !== null) {
			$value = $this->evaluateExpression($this->value, array(
				'data' => $data, 
				'row' => $row
			));
		} elseif ($this->name !== null) {
			$value = CHtml::value($data, $this->name, $this->defaultValue);
		}
		echo $value === null ? $this->grid->nullDisplay : $this->grid->getFormatter()->format($value, $this->type);
	}
}
