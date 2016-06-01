<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

use Closure;
use yii\base\Object;

/**
 * Column is the base class of all [[CsvGrid]] column classes.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Column extends Object
{
    /**
     * @var CsvGrid the exporter object that owns this column.
     */
    public $grid;
    /**
     * @var string the header cell content.
     */
    public $header;
    /**
     * @var string the footer cell content.
     */
    public $footer;
    /**
     * @var callable This is a callable that will be used to generate the content of each cell.
     * The signature of the function should be the following: `function ($model, $key, $index, $column)`.
     * Where `$model`, `$key`, and `$index` refer to the model, key and index of the row currently being rendered
     * and `$column` is a reference to the [[Column]] object.
     */
    public $content;
    /**
     * @var boolean whether this column is visible. Defaults to true.
     */
    public $visible = true;


    /**
     * Renders the header cell.
     * @return string the rendering result
     */
    public function renderHeaderCell()
    {
        return $this->renderHeaderCellContent();
    }

    /**
     * Renders the footer cell.
     * @return string the rendering result
     */
    public function renderFooterCell()
    {
        return $this->renderFooterCellContent();
    }

    /**
     * Renders a data cell.
     * @param mixed $model the data model being rendered
     * @param mixed $key the key associated with the data model
     * @param integer $index the zero-based index of the data item among the item array returned by [[GridView::dataProvider]].
     * @return string the rendering result
     */
    public function renderDataCell($model, $key, $index)
    {
        return $this->renderDataCellContent($model, $key, $index);
    }

    /**
     * Renders the header cell content.
     * The default implementation simply renders [[header]].
     * This method may be overridden to customize the rendering of the header cell.
     * @return string the rendering result
     */
    public function renderHeaderCellContent()
    {
        return trim($this->header) !== '' ? $this->header : $this->grid->emptyCell;
    }

    /**
     * Renders the footer cell content.
     * The default implementation simply renders [[footer]].
     * This method may be overridden to customize the rendering of the footer cell.
     * @return string the rendering result
     */
    public function renderFooterCellContent()
    {
        return trim($this->footer) !== '' ? $this->footer : $this->grid->emptyCell;
    }

    /**
     * Renders the data cell content.
     * @param mixed $model the data model
     * @param mixed $key the key associated with the data model
     * @param integer $index the zero-based index of the data model among the models array returned by [[GridView::dataProvider]].
     * @return string the rendering result
     */
    public function renderDataCellContent($model, $key, $index)
    {
        if ($this->content !== null) {
            return call_user_func($this->content, $model, $key, $index, $this);
        } else {
            return $this->grid->emptyCell;
        }
    }

    /**
     * Renders the filter cell content.
     * The default implementation simply renders a space.
     * This method may be overridden to customize the rendering of the filter cell (if any).
     * @return string the rendering result
     */
    public function renderFilterCellContent()
    {
        return $this->grid->emptyCell;
    }
}