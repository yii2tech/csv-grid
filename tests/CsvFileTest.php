<?php

namespace yii2tech\tests\unit\csvgrid;

use yii2tech\csvgrid\CsvFile;

class CsvFileTest extends TestCase
{
    /**
     * @return CsvFile CSV file instance.
     */
    protected function createCsvFile()
    {
        $file = new CsvFile();
        $file->name = $this->getTestFilePath() . DIRECTORY_SEPARATOR . 'test.csv';
        return $file;
    }

    // Tests :

    public function testWriteRow()
    {
        $csvFile = $this->createCsvFile();

        $csvFile->writeRow([
            'cell-1',
            'cell-2',
        ]);
        $csvFile->close();

        $this->assertEquals(1, $csvFile->entriesCount, 'Wrong entries count');
        $this->assertFileExists($csvFile->name, 'Unable to save file');

        $expectedContent = '"cell-1","cell-2"';
        $this->assertEquals($expectedContent, file_get_contents($csvFile->name), 'Invalid file content');
    }

    /**
     * @depends testWriteRow
     */
    public function testEscapeValue()
    {
        $csvFile = $this->createCsvFile();

        $csvFile->writeRow([
            '"quoted"',
        ]);
        $csvFile->close();

        $expectedContent = '"""quoted"""';
        $this->assertEquals($expectedContent, file_get_contents($csvFile->name), 'Invalid file content');
    }
}