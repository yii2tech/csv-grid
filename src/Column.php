<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

use yii\base\BaseObject;

/**
 * Column is the base class of all [[CsvGrid]] column classes.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Column extends BaseObject
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
     * @var bool whether this column is visible. Defaults to true.
     */
    public $visible = true;


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
     * @param int $index the zero-based index of the data model among the models array returned by [[GridView::dataProvider]].
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
}