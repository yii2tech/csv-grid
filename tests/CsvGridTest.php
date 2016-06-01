<?php

namespace yii2tech\tests\unit\csvgrid;

use yii\data\ArrayDataProvider;
use yii\i18n\Formatter;
use yii2tech\csvgrid\CsvGrid;

class CsvGridTest extends TestCase
{
    /**
     * @param array $config CSV grid configuration
     * @return CsvGrid CSV grid instance
     */
    protected function createCsvGrid(array $config = [])
    {
        if (!isset($config['dataProvider']) && !isset($config['query'])) {
            $config['dataProvider'] = new ArrayDataProvider();
        }
        return new CsvGrid($config);
    }

    // Tests :

    public function testSetupFormatter()
    {
        $grid = $this->createCsvGrid();

        $formatter = new Formatter();
        $grid->setFormatter($formatter);
        $this->assertSame($formatter, $grid->getFormatter());
    }
}