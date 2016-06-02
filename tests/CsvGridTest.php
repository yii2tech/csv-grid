<?php

namespace yii2tech\tests\unit\csvgrid;

use yii\data\ArrayDataProvider;
use yii\i18n\Formatter;
use yii2tech\csvgrid\CsvGrid;
use yii2tech\csvgrid\ExportResult;

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

    public function testExport()
    {
        $grid = $this->createCsvGrid([
            'dataProvider' => new ArrayDataProvider([
                'allModels' => [
                    [
                        'id' => 1,
                        'name' => 'first',
                    ],
                    [
                        'id' => 2,
                        'name' => 'second',
                    ],
                ],
            ])
        ]);

        $result = $grid->export();
        $this->assertTrue($result instanceof ExportResult);
        $this->assertNotEmpty($result->csvFiles, 'Empty result.');
        $fileName = $result->getResultFileName();
        $this->assertFileExists($fileName, 'Result file does not exist.');

        $this->assertContains('"Id","Name"', file_get_contents($fileName), 'Header not present in content.');
        $this->assertContains('"1","first"', file_get_contents($fileName), 'Data not present in content.');
        $this->assertContains('"2","second"', file_get_contents($fileName), 'Data not present in content.');
    }
}