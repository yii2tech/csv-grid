<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\di\Instance;
use yii\i18n\Formatter;

/**
 * CsvGrid
 *
 * @property array|Formatter $formatter the formatter used to format model attribute values into displayable texts.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CsvGrid extends Component
{
    /**
     * @var \yii\data\DataProviderInterface the data provider for the view.
     * This property can be omitted in case [[query]] is set.
     */
    public $dataProvider;
    /**
     * @var \yii\db\QueryInterface the data source query.
     * Note: this field will be ignored in case [[dataProvider]] is set.
     */
    public $query;
    /**
     * @var array|Column[]
     */
    public $columns = [];
    /**
     * @var boolean whether to show the header section of the sheet.
     */
    public $showHeader = true;
    /**
     * @var boolean whether to show the footer section of the sheet.
     */
    public $showFooter = false;
    /**
     * @var string the HTML display when the content of a cell is empty.
     * This property is used to render cells that have no defined content,
     * e.g. empty footer or filter cells.
     *
     * Note that this is not used by the [[DataColumn]] if a data item is `null`. In that case
     * the [[nullDisplay]] property will be used to indicate an empty data value.
     */
    public $emptyCell = '';
    /**
     * @var string the text to be displayed when formatting a `null` data value.
     */
    public $nullDisplay = '';
    /**
     * @var string delimiter between the CSV file rows.
     */
    public $rowDelimiter = "\r\n";
    /**
     * @var string delimiter between the CSV file cells.
     */
    public $cellDelimiter = ',';
    /**
     * @var string the cell content enclosure.
     */
    public $enclosure = '"';

    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    private $_formatter;


    /**
     * Initializes the grid.
     * This method will initialize required property values and instantiate [[columns]] objects.
     */
    public function init()
    {
        parent::init();

        if ($this->dataProvider === null) {
            if ($this->query !== null) {
                $this->dataProvider = new ActiveDataProvider([
                    'query' => $this->query,
                    'pagination' => [
                        'pageSize' => 100,
                    ],
                ]);
            }
        }

        $this->initColumns();
    }

    /**
     * @return Formatter formatter instance
     */
    public function getFormatter()
    {
        if (!is_object($this->_formatter)) {
            if ($this->_formatter === null) {
                $this->_formatter = Yii::$app->getFormatter();
            } else {
                $this->_formatter = Instance::ensure($this->_formatter, Formatter::className());
            }
        }
        return $this->_formatter;
    }

    /**
     * @param array|Formatter $formatter
     */
    public function setFormatter($formatter)
    {
        $this->_formatter = $formatter;
    }

    /**
     * Creates column objects and initializes them.
     */
    protected function initColumns()
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = Yii::createObject(array_merge([
                    'class' => DataColumn::className(),
                    'grid' => $this,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }
    }

    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
        $models = $this->dataProvider->getModels();
        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                $this->columns[] = (string) $name;
            }
        }
    }

    /**
     * Creates a [[DataColumn]] object based on a string in the format of "attribute:format:label".
     * @param string $text the column specification string
     * @return DataColumn the column instance
     * @throws InvalidConfigException if the column specification is invalid
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        return Yii::createObject([
            'class' => DataColumn::className(),
            'grid' => $this,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'text',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);
    }
}