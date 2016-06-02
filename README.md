CSV data export extension for Yii2
==================================

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


## Splitting result into several files <span id="splitting-result-into-several-files"></span>


## Archiving results <span id="archiving-results"></span>
