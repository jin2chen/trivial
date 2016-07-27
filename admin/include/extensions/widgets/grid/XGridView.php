<?php
/**
 * XGridView class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.CBaseListView');
Yii::import('ext.widgets.pagers.XLinkPager');
Yii::import('ext.widgets.grid.XGridColumn');
Yii::import('ext.widgets.grid.XDataColumn');
Yii::import('ext.widgets.grid.XLinkColumn');
Yii::import('ext.widgets.grid.XButtonColumn');
Yii::import('ext.widgets.grid.XCheckBoxColumn');

/**
 * XGridView displays a list of data items in terms of a table.
 *
 * Each row of the table represents the data of a single data item, and a column usually represents
 * an attribute of the item (some columns may correspond to complex expression of attributes or static text).
 *
 * XGridView supports both sorting and pagination of the data items. The sorting
 * and pagination can be done in AJAX mode or normal page request. A benefit of using XGridView is that
 * when the user browser disables JavaScript, the sorting and pagination automatically degenerate
 * to normal page requests and are still functioning as expected.
 *
 * XGridView should be used together with a {@link IDataProvider data provider}, preferrably a
 * {@link CActiveDataProvider}.
 *
 * The minimal code needed to use XGridView is as follows:
 *
 * <pre>
 * $dataProvider=new CActiveDataProvider('Post');
 *
 * $this->widget('zii.widgets.grid.XGridView', array(
 * 'dataProvider'=>$dataProvider,
 * ));
 * </pre>
 *
 * The above code first creates a data provider for the <code>Post</code> ActiveRecord class.
 * It then uses XGridView to display every attribute in every <code>Post</code> instance.
 * The displayed table is equiped with sorting and pagination functionality.
 *
 * In order to selectively display attributes with different formats, we may configure the
 * {@link XGridView::columns} property. For example, we may specify only the <code>title</code>
 * and <code>create_time</code> attributes to be displayed, and the <code>create_time</code>
 * should be properly formatted to show as a time. We may also display the attributes of the related
 * objects using the dot-syntax as shown below:
 *
 * <pre>
 * $this->widget('zii.widgets.grid.XGridView', array(
 * 'dataProvider'=>$dataProvider,
 * 'columns'=>array(
 * 'title',          // display the 'title' attribute
 * 'category.name',  // display the 'name' attribute of the 'category' relation
 * 'content:html',   // display the 'content' attribute as purified HTML
 * array(            // display 'create_time' using an expression
 * 'name'=>'create_time',
 * 'value'=>'date("M j, Y", $data->create_time)',
 * ),
 * array(            // display 'author.username' using an expression
 * 'name'=>'authorName',
 * 'value'=>'$data->author->username',
 * ),
 * array(            // display a column with "view", "update" and "delete" buttons
 * 'class'=>'CButtonColumn',
 * ),
 * ),
 * ));
 * </pre>
 *
 * Please refer to {@link columns} for more details about how to configure this property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: XGridView.php 197 2011-12-13 09:20:35Z mole1230 $
 * @package zii.widgets.grid
 * @since 1.1
 */
class XGridView extends CBaseListView
{
	/**
	 * @var array the configuration for the pager. Defaults to <code>array('class'=>'XLinkPager')</code>.
	 * @see enablePagination
	 */
	public $pager = array(
		'class' => 'XLinkPager'
	);
	
	/**
	 * @see {@link CBaseListView::template}
	 * @var string
	 */
	public $template = "{items}\n{pager}";
	
	/**
	 * @var array grid column configuration. Each array element represents the configuration
	 * for one particular grid column which can be either a string or an array.
	 *
	 * When a column is specified as a string, it should be in the format of "name:type:header",
	 * where "type" and "header" are optional. A {@link XDataColumn} instance will be created in this case,
	 * whose {@link XDataColumn::name}, {@link XDataColumn::type} and {@link XDataColumn::header}
	 * properties will be initialized accordingly.
	 *
	 * When a column is specified as an array, it will be used to create a grid column instance, where
	 * the 'class' element specifies the column class name (defaults to {@link XDataColumn} if absent).
	 * Currently, these official column classes are provided: {@link XDataColumn},
	 * {@link CLinkColumn}, {@link CButtonColumn} and {@link CCheckBoxColumn}.
	 */
	public $columns = array();
	
	/**
	 * @var array the CSS class names for the table body rows. If multiple CSS class names are given,
	 * they will be assigned to the rows sequentially and repeatedly. This property is ignored
	 * if {@link rowCssClassExpression} is set. Defaults to <code>array('odd', 'even')</code>.
	 * @see rowCssClassExpression
	 */
	public $rowCssClass = array(
		'odd', 
		'even'
	);
	
	/**
	 * @var string a PHP expression that is evaluated for every table body row and whose result
	 * is used as the CSS class name for the row. In this expression, the variable <code>$row</code>
	 * stands for the row number (zero-based), <code>$data</code> is the data model associated with
	 * the row, and <code>$this</code> is the grid object.
	 * @see rowCssClass
	 */
	public $rowCssClassExpression;
	
	/**
	 * @var boolean whether to display the table even when there is no data. Defaults to true.
	 * The {@link emptyText} will be displayed to indicate there is no data.
	 */
	public $showTableOnEmpty = true;
	
	/**
	 * @var string the text to be displayed in a data cell when a data value is null. This property will NOT be HTML-encoded
	 * when rendering. Defaults to an HTML blank.
	 */
	public $nullDisplay = '&nbsp;';
	
	/**
	 * @var string the text to be displayed in an empty grid cell. This property will NOT be HTML-encoded when rendering. Defaults to an HTML blank.
	 * This differs from {@link nullDisplay} in that {@link nullDisplay} is only used by {@link XDataColumn} to render
	 * null data values.
	 */
	public $blankDisplay = '&nbsp;';
	
	/**
	 * @var boolean whether to hide the header cells of the grid. When this is true, header cells
	 * will not be rendered, which means the grid cannot be sorted anymore since the sort links are located
	 * in the header. Defaults to false.
	 */
	public $hideHeader = false;
	
	public $tableHtmlOptions = array();
	
	/**
	 * @var CFormatter
	 */
	private $_formatter;
	
	/**
	 * Initializes the grid view.
	 * This method will initialize required property values and instantiate {@link columns} objects.
	 */
	public function init()
	{
		parent::init();
		
		if (!isset($this->htmlOptions['class'])) {
			$this->htmlOptions['class'] = 'grid-view';
		}
		
		$this->initColumns();
	}
	
	/**
	 * Creates column objects and initializes them.
	 */
	protected function initColumns()
	{
		if ($this->columns === array()) {
			if ($this->dataProvider instanceof CActiveDataProvider) {
				$this->columns = $this->dataProvider->model->attributeNames();
			} else { 
				if ($this->dataProvider instanceof IDataProvider) {
					// use the keys of the first row of data as the default columns
					$data = $this->dataProvider->getData();
					if (isset($data[0]) && is_array($data[0])) {
						$this->columns = array_keys($data[0]);
					}
				}
			}
		}
		
		$id = $this->getId();
		foreach ($this->columns as $i => $column) {
			if (is_string($column)) {
				$column = $this->createDataColumn($column);
			} else {
				if (!isset($column['class'])) {
					$column['class'] = 'XDataColumn';
				}
				$column = Yii::createComponent($column, $this);
			}
			if (!$column->visible) {
				unset($this->columns[$i]);
				continue;
			}
			if ($column->id === null) {
				$column->id = $id . '_c' . $i;
			}
			$this->columns[$i] = $column;
		}
		
		foreach ($this->columns as $column) {
			$column->init();
		}
	}
	
	/**
	 * Creates a {@link XDataColumn} based on a shortcut column specification string.
	 * @param string $text the column specification string
	 * @return XDataColumn the column instance
	 */
	protected function createDataColumn($text)
	{
		if (!preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
			throw new CException(Yii::t('zii', 'The column must be specified in the format of "Name:Type:Label", where "Type" and "Label" are optional.'));
		}
		
		$column = new XDataColumn($this);
		$column->name = $matches[1];
		if (isset($matches[3]) && $matches[3] !== '') {
			$column->type = $matches[3];
		}
			
		if (isset($matches[5])) {
			$column->header = $matches[5];
		}
		return $column;
	}
	
	/**
	 * Renders the data items for the grid view.
	 */
	public function renderItems()
	{
		if ($this->dataProvider->getItemCount() > 0 || $this->showTableOnEmpty) {
			$this->tableHtmlOptions = array_merge(array(
				'width' => '100%',
				'cellspacing' => '1',
				'cellpadding' => '3',
				'border' => '0',
				'align' => 'center'
			), $this->tableHtmlOptions);
			echo CHtml::openTag('table', $this->tableHtmlOptions);
			$this->renderTableHeader();
			ob_start();
			$this->renderTableBody();
			$body = ob_get_clean();
			$this->renderTableFooter();
			echo $body; // TFOOT must appear before TBODY according to the standard.
			echo "</table>";
		} else {
			$this->renderEmptyText();
		}
	}
	
	/**
	 * Renders the table header.
	 */
	public function renderTableHeader()
	{
		if (!$this->hideHeader) {
			echo "<thead>\n";
			echo "<tr>\n";
			foreach ($this->columns as $column) {
				$column->renderHeaderCell();
			}
			echo "</tr>\n";
			echo "</thead>\n";
		}
	}
	
	/**
	 * Renders the table footer.
	 */
	public function renderTableFooter()
	{
//		$hasFooter = $this->getHasFooter();
//		if ($hasFilter || $hasFooter) {
//			echo "<tfoot>\n";
//			if ($hasFooter) {
//				echo "<tr>\n";
//				foreach ($this->columns as $column)
//					$column->renderFooterCell();
//				echo "</tr>\n";
//			}
//			if ($hasFilter)
//				$this->renderFilter();
//			echo "</tfoot>\n";
//		}
	}
	
	/**
	 * Renders the table body.
	 */
	public function renderTableBody()
	{
		$data = $this->dataProvider->getData();
		$n = count($data);
		echo "<tbody>\n";
		
		if ($n > 0) {
			for ($row = 0; $row < $n; ++$row) {
				$this->renderTableRow($row);
			}
		} else {
			echo '<tr><td colspan="' . count($this->columns) . '">';
			$this->renderEmptyText();
			echo "</td></tr>\n";
		}
		echo "</tbody>\n";
	}
	
	/**
	 * Renders a table body row.
	 * @param integer $row the row number (zero-based).
	 */
	public function renderTableRow($row)
	{
		if ($this->rowCssClassExpression !== null) {
			$data = $this->dataProvider->data[$row];
			echo '<tr class="' . $this->evaluateExpression($this->rowCssClassExpression, array(
				'row' => $row, 
				'data' => $data
			)) . '">';
		} elseif (is_array($this->rowCssClass) && ($n = count($this->rowCssClass)) > 0) {
			echo '<tr class="' . $this->rowCssClass[$row % $n] . '">';
		} else {
			echo '<tr>';
		}
		foreach ($this->columns as $column) {
			$column->renderDataCell($row);
		}
		echo "</tr>\n";
	}
	
	/**
	 * @return boolean whether the table should render a footer.
	 * This is true if any of the {@link columns} has a true {@link CGridColumn::hasFooter} value.
	 */
	public function getHasFooter()
	{
		foreach ($this->columns as $column) {
			if ($column->getHasFooter()) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @return CFormatter the formatter instance. Defaults to the 'format' application component.
	 */
	public function getFormatter()
	{
		if ($this->_formatter === null) {
			$this->_formatter = Yii::app()->format;
		}
		return $this->_formatter;
	}
	
	/**
	 * @param CFormatter $value the formatter instance
	 */
	public function setFormatter($value)
	{
		$this->_formatter = $value;
	}
}
