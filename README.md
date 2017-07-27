<p align="center">
    <a href="https://github.com/yii2tech" target="_blank">
        <img src="https://avatars2.githubusercontent.com/u/12951949" height="100px">
    </a>
    <h1 align="center">CSV Data Export extension for Yii2</h1>
    <br>
</p>

This extension provides ability to export data to CSV file.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/csv-grid/v/stable.png)](https://packagist.org/packages/yii2tech/csv-grid)
[![Total Downloads](https://poser.pugx.org/yii2tech/csv-grid/downloads.png)](https://packagist.org/packages/yii2tech/csv-grid)
[![Build Status](https://travis-ci.org/yii2tech/csv-grid.svg?branch=master)](https://travis-ci.org/yii2tech/csv-grid)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/csv-grid
```

or add

```json
"yii2tech/csv-grid": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides ability to export data to CSV file.
Export is performed via [[\yii2tech\csvgrid\CsvGrid]] instance, which provides interface similar to [[\yii\grid\GridView]] widget.

Example:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ArrayDataProvider;

$exporter = new CsvGrid([
    'dataProvider' => new ArrayDataProvider([
        'allModels' => [
            [
                'name' => 'some name',
                'price' => '9879',
            ],
            [
                'name' => 'name 2',
                'price' => '79',
            ],
        ],
    ]),
    'columns' => [
        [
            'attribute' => 'name',
        ],
        [
            'attribute' => 'price',
            'format' => 'decimal',
        ],
    ],
]);
$exporter->export()->saveAs('/path/to/file.csv');
```

[[\yii2tech\csvgrid\CsvGrid]] allows exporting of the [[\yii\data\DataProviderInterface]] and [[\yii\db\QueryInterface]] instances.
Export is performed via batches, which allows processing of the large data without memory overflow.

In case of [[\yii\data\DataProviderInterface]] usage, data will be split to batches using pagination mechanism.
Thus you should setup pagination with page size in order to control batch size:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'dataProvider' => new ActiveDataProvider([
        'query' => Item::find(),
        'pagination' => [
            'pageSize' => 100, // export batch size
        ],
    ]),
]);
$exporter->export()->saveAs('/path/to/file.csv');
```

> Note: if you disable pagination in your data provider - no batch processing will be performed.

In case of [[\yii\db\QueryInterface]] usage, `CsvGrid` will attempt to use `batch()` method, if it present in the query
class (for example in case [[\yii\db\Query]] or [[\yii\db\ActiveQuery]] usage). If `batch()` method is not available -
[[yii\data\ActiveDataProvider]] instance will be automatically created around given query.
You can control batch size via [[\yii2tech\csvgrid\CsvGrid::batchSize]]:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'batchSize' => 200, // export batch size
]);
$exporter->export()->saveAs('/path/to/file.csv');
```

While running web application you can use [[\yii2tech\csvgrid\ExportResult::send()]] method to send a result file to
the browser through download dialog:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;
use yii\web\Controller;

class ItemController extends Controller
{
    public function actionExport()
    {
        $exporter = new CsvGrid([
            'dataProvider' => new ActiveDataProvider([
                'query' => Item::find(),
            ]),
        ]);
        return $exporter->export()->send('items.csv');
    }
}
```


## Splitting result into several files <span id="splitting-result-into-several-files"></span>

While exporting large amount of data, you may want to split export results into several files.
This may come in handy in case you are planning to use result CSV files with program, which have a limit on
maximum rows inside single file. For example: 'Open Office' and 'MS Excel 97-2003' allows maximum 65536 rows
per CSV file, 'MS Excel 2007' - 1048576.

You may use [[\yii2tech\csvgrid\CsvGrid::maxEntriesPerFile]] to restrict maximum rows in the single result file.
In case the export result produce more then one CSV file - these files will be automatically archived into the single
archive file. For example:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'maxEntriesPerFile' => 60000, // limit max rows per single file
]);
$exporter->export()->saveAs('/path/to/archive-file.zip'); // output ZIP archive!
```

Note: you are not forced to receive multiple files result as a single archive. You can use
[[\yii2tech\csvgrid\ExportResult::csvFiles]] to manually iterate over created CSV files and process them as you like:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'maxEntriesPerFile' => 60000, // limit max rows per single file
]);
$result = $exporter->export();
foreach ($result->csvFiles as $csvFile) {
    /* @var $csvFile \yii2tech\csvgrid\CsvFile */
    copy($csvFile->name, '/path/to/dir/' . basename($csvFile->name));
}
```


## Archiving results <span id="archiving-results"></span>

Export result is archived automatically, if it contains more then one CSV file. However you may enforce archiving of the
export result via [[\yii2tech\csvgrid\ExportResult::forceArchive]]:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'resultConfig' => [
        'forceArchive' => true // always archive the results
    ],
]);
$exporter->export()->saveAs('/path/to/archive-file.zip'); // output ZIP archive!
```

**Heads up!** By default [[\yii2tech\csvgrid\ExportResult]] uses [PHP Zip](http://php.net/manual/en/book.zip.php) extension for the archive creating.
Thus it will fail, if this extension is not present in your environment.

You can setup your own archive method via [[\yii2tech\csvgrid\ExportResult::archiver]].
For example:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'resultConfig' => [
        'forceArchive' => true,
        'archiver' => function (array $files, $dirName) {
            $archiveFileName = $dirName . DIRECTORY_SEPARATOR . 'items.tar';

            foreach ($files as $fileName) {
                // add $fileName to $archiveFileName archive
            }

            return $archiveFileName;
        },
    ],
]);
$exporter->export()->saveAs('/path/to/items.tar');
```

While sending file to the browser via [[\yii2tech\csvgrid\ExportResult::send()]] there is no need to check if result
is archived or not as correct file extension will be append automatically:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;
use yii\web\Controller;

class ItemController extends Controller
{
    public function actionExport()
    {
        $exporter = new CsvGrid([
            'dataProvider' => new ActiveDataProvider([
                'query' => Item::find(), // over 1 million records
                'maxEntriesPerFile' => 60000,
            ]),
        ]);
        return $exporter->export()->send('items.csv'); // displays dialog for saving `items.csv.zip`!
    }
}
```


## Customize output format <span id="customize-output-format"></span>

Although CSV dictates particular data format (each value quoted, values separated by comma, lines separated by line break),
some cases require its changing. For example: you may need to separate values using semicolon, or may want to create
TSV (tabular separated values) file instead CSV.
You may customize format entries using [[\yii2tech\csvgrid\CsvGrid::csvFileConfig]]:

```php
use yii2tech\csvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'csvFileConfig' => [
        'cellDelimiter' => "\t",
        'rowDelimiter' => "\n",
        'enclosure' => '',
    ],
]);
$exporter->export()->saveAs('/path/to/file.txt');
```
