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
 * CsvGrid allows export of data into CSV files.
 * It supports exporting of the [[\yii\data\DataProviderInterface]] and [[\yii\db\QueryInterface]] instances.
 *
 * Example:
 *
 * ```php
 * use yii2tech\csvgrid\CsvGrid;
 * use yii\data\ArrayDataProvider;
 *
 * $exporter = new CsvGrid([
 *     'dataProvider' => new ArrayDataProvider([
 *         'allModels' => [
 *             [
 *                 'name' => 'some name',
 *                 'price' => '9879',
 *             ],
 *             [
 *                 'name' => 'name 2',
 *                 'price' => '79',
 *             ],
 *         ],
 *     ]),
 *     'columns' => [
 *         [
 *             'attribute' => 'name',
 *         ],
 *         [
 *             'attribute' => 'price',
 *             'format' => 'decimal',
 *         ],
 *     ],
 * ]);
 * $exporter->export()->saveAs('/path/to/file.csv');
 * ```
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
     * @var int the number of records to be fetched in each batch.
     * This property takes effect only in case of [[query]] usage.
     */
    public $batchSize = 100;
    /**
     * @var array|Column[] grid column configuration. Each array element represents the configuration
     * for one particular grid column. For example:
     *
     * ```php
     * [
     *     ['class' => SerialColumn::className()],
     *     [
     *         'class' => DataColumn::className(), // this line is optional
     *         'attribute' => 'name',
     *         'format' => 'text',
     *         'header' => 'Name',
     *     ],
     * ]
     * ```
     *
     * If a column is of class [[DataColumn]], the "class" element can be omitted.
     */
    public $columns = [];
    /**
     * @var bool whether to show the header section of the sheet.
     */
    public $showHeader = true;
    /**
     * @var bool whether to show the footer section of the sheet.
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
     * @var int the maximum entries count allowed in single file.
     * You may use this parameter to split large export results into several smaller files.
     *
     * For example: 'Open Office' and 'MS Excel 97-2003' allows maximum 65536 rows per CSV file,
     * 'MS Excel 2007' - 1048576.
     *
     * In case several files are generated during export the results will be archived into a single file.
     *
     * If this value is empty - no limit checking will be performed.
     */
    public $maxEntriesPerFile;
    /**
     * @var array configuration for [[CsvFile]] instances created in process.
     * For example:
     *
     * ```php
     * [
     *     'rowDelimiter' => "\n",
     *     'cellDelimiter' => ';',
     * ]
     * ```
     *
     * @see CsvFile
     */
    public $csvFileConfig = [];
    /**
     * @var array configuration for [[ExportResult]] instance created in process result.
     *
     * For example:
     *
     * ```php
     * [
     *     'forceArchive' => true
     * ]
     * ```
     *
     * @see ExportResult
     */
    public $resultConfig = [];

    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    private $_formatter;
    /**
     * @var array|null internal iteration information
     */
    private $batchInfo;


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
                        'pageSize' => $this->batchSize,
                    ],
                ]);
            }
        }
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
     * @param array $model list of single row model
     */
    protected function initColumns($model)
    {
        if (empty($this->columns)) {
            $this->guessColumns($model);
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
     * @param array $model list of model
     */
    protected function guessColumns($model)
    {
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

    /**
     * Performs data export.
     * @return ExportResult export result.
     * @throws InvalidConfigException if invalid [[resultConfig]] value.
     */
    public function export()
    {
        /** @var ExportResult $result */
        $result = Yii::createObject(array_merge([
            'class' => ExportResult::className(),
        ], $this->resultConfig));

        $columnsInitialized = false;

        $maxEntriesPerFile = false;
        if (!empty($this->maxEntriesPerFile)) {
            $maxEntriesPerFile = $this->maxEntriesPerFile;
            if ($this->showFooter) {
                $maxEntriesPerFile--;
            }
        }

        $csvFile = null;
        $rowIndex = 0;
        while (($data = $this->batchModels()) !== false) {
            list($models, $keys) = $data;

            if (!$columnsInitialized) {
                $this->initColumns(reset($models));
                $columnsInitialized = true;
            }

            foreach ($models as $index => $model) {
                if (!is_object($csvFile)) {
                    $csvFile = $result->newCsvFile($this->csvFileConfig);
                    if ($this->showHeader) {
                        $csvFile->writeRow($this->composeHeaderRow());
                    }
                }

                $key = isset($keys[$index]) ? $keys[$index] : $index;
                $csvFile->writeRow($this->composeBodyRow($model, $key, $rowIndex));
                $rowIndex++;

                if ($maxEntriesPerFile !== false && $csvFile->entriesCount >= $maxEntriesPerFile) {
                    if ($this->showFooter) {
                        $csvFile->writeRow($this->composeFooterRow());
                    }
                    $csvFile->close();
                    $csvFile = null;
                }
            }

            $this->gc();
        }

        if (is_object($csvFile)) {
            if ($this->showFooter) {
                $csvFile->writeRow($this->composeFooterRow());
            }
            $csvFile->close();
        }

        if (empty($result->csvFiles)) {
            $csvFile = $result->newCsvFile($this->csvFileConfig);
            if ($this->showHeader) {
                $csvFile->writeRow($this->composeHeaderRow());
            }
            if ($this->showFooter) {
                $csvFile->writeRow($this->composeFooterRow());
            }
            $csvFile->close();
        }

        return $result;
    }

    /**
     * Iterates over [[query]] or [[dataProvider]] returning data by batches.
     * @return array|false data batch: first element - models list, second model keys list.
     */
    protected function batchModels()
    {
        if ($this->batchInfo === null) {
            if ($this->query !== null && method_exists($this->query, 'batch')) {
                $this->batchInfo = [
                    'queryIterator' => $this->query->batch($this->batchSize)
                ];
            } else {
                $this->batchInfo = [
                    'pagination' => $this->dataProvider->getPagination(),
                    'page' => 0
                ];
            }
        }

        if (isset($this->batchInfo['queryIterator'])) {
            /* @var $iterator \Iterator */
            $iterator = $this->batchInfo['queryIterator'];
            $iterator->next();

            if ($iterator->valid()) {
                return [$iterator->current(), []];
            }

            $this->batchInfo = null;
            return false;
        }

        if (isset($this->batchInfo['pagination'])) {
            /* @var $pagination \yii\data\Pagination|bool */
            $pagination = $this->batchInfo['pagination'];
            $page = $this->batchInfo['page'];

            if ($pagination === false || $pagination->pageCount === 0) {
                if ($page === 0) {
                    $this->batchInfo['page']++;
                    return [
                        $this->dataProvider->getModels(),
                        $this->dataProvider->getKeys()
                    ];
                }
            } else {
                if ($page < $pagination->pageCount) {
                    $pagination->setPage($page);
                    $this->dataProvider->prepare(true);
                    $this->batchInfo['page']++;
                    return [
                        $this->dataProvider->getModels(),
                        $this->dataProvider->getKeys()
                    ];
                }
            }

            $this->batchInfo = null;
            return false;
        }

        return false;
    }

    /**
     * Composes header row contents.
     * @return array cell contents.
     */
    protected function composeHeaderRow()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            $cells[] = $column->renderHeaderCellContent();
        }
        return $cells;
    }

    /**
     * Composes header row contents.
     * @return array cell contents.
     */
    protected function composeFooterRow()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            $cells[] = $column->renderFooterCellContent();
        }
        return $cells;
    }

    /**
     * Composes body row contents.
     * @param mixed $model the data model
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data model among the models array returned by [[GridView::dataProvider]].
     * @return array cell contents.
     */
    protected function composeBodyRow($model, $key, $index)
    {
        $cells = [];
        foreach ($this->columns as $column) {
            $cells[] = $column->renderDataCellContent($model, $key, $index);
        }
        return $cells;
    }

    /**
     * Performs PHP memory garbage collection.
     */
    protected function gc()
    {
        if (!gc_enabled()) {
            gc_enable();
        }
        gc_collect_cycles();
    }
}