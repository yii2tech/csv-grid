<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

/**
 * SerialColumn displays a column of row numbers (1-based).
 *
 * To add a SerialColumn to the [[CsvGrid]], add it to the [[CsvGrid::columns|columns]] configuration as follows:
 *
 * ```php
 * 'columns' => [
 *     [
 *         'class' => 'yii2tech\csvgrid\SerialColumn',
 *     ],
 *     // ...
 * ]
 * ```
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0.2
 */
class SerialColumn extends Column
{
    /**
     * {@inheritdoc}
     */
    public $header = '#';


    /**
     * {@inheritdoc}
     */
    public function renderDataCellContent($model, $key, $index)
    {
        return $index + 1;
    }
}