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

    /**
     * @depends testWriteRow
     */
    public function testWriteBom()
    {
        $csvFile = $this->createCsvFile();
        $csvFile->writeBom = true;

        $csvFile->writeRow([
            'cell-1',
            'cell-2',
        ]);
        $csvFile->writeRow([
            'cell-1',
            'cell-2',
        ]);
        $csvFile->close();

        $expectedContent = pack('CCC', 0xef, 0xbb, 0xbf) . '"cell-1","cell-2"' . "\r\n" . '"cell-1","cell-2"';
        $this->assertEquals($expectedContent, file_get_contents($csvFile->name), 'Invalid file content');

        $csvFile = $this->createCsvFile();
        $csvFile->writeBom = 'BOM';

        $csvFile->writeRow([
            'cell-1',
            'cell-2',
        ]);
        $csvFile->writeRow([
            'cell-1',
            'cell-2',
        ]);
        $csvFile->close();

        $expectedContent = 'BOM' . '"cell-1","cell-2"' . "\r\n" . '"cell-1","cell-2"';
        $this->assertEquals($expectedContent, file_get_contents($csvFile->name), 'Invalid file content');
    }
}