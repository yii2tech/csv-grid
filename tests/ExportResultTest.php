<?php

namespace yii2tech\tests\unit\csvgrid;

use yii\helpers\FileHelper;
use yii2tech\csvgrid\CsvFile;
use yii2tech\csvgrid\ExportResult;

class ExportResultTest extends TestCase
{
    /**
     * @return ExportResult export result instance
     */
    protected function createExportResult()
    {
        $exportResult = new ExportResult();
        $exportResult->basePath = $this->getTestFilePath();
        return $exportResult;
    }

    // Tests :

    public function testNewCsvFile()
    {
        $exportResult = $this->createExportResult();

        $file = $exportResult->newCsvFile([
            'cellDelimiter' => '#'
        ]);
        $this->assertTrue($file instanceof CsvFile);
        $this->assertEquals('#', $file->cellDelimiter);

        $file->writeRow(['foo']);

        $file->close();

        $files = FileHelper::findFiles($exportResult->getDirName());
        $this->assertCount(1, $files);
    }

    /**
     * @depends testNewCsvFile
     */
    public function testResultFileName()
    {
        $exportResult = $this->createExportResult();
        $file = $exportResult->newCsvFile();
        $file->writeRow(['foo']);
        $file->close();

        $this->assertEquals($file->name, $exportResult->getResultFileName());
    }

    /**
     * @depends testNewCsvFile
     */
    public function testArchiveResultFileName()
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('PHP "zip" extension required');
        }

        $exportResult = $this->createExportResult();

        $file = $exportResult->newCsvFile();
        $file->writeRow(['first']);
        $file->close();

        $file = $exportResult->newCsvFile();
        $file->writeRow(['second']);
        $file->close();

        $archiveFile = $exportResult->getResultFileName();
        $this->assertNotEmpty($archiveFile);
        $this->assertFileExists($archiveFile);
    }

    /**
     * @depends testNewCsvFile
     */
    public function testArchiveResultFileNameCallback()
    {
        $exportResult = $this->createExportResult();
        $exportResult->archiver = function ($files, $dirName) {
            return 'mock.tar';
        };

        $file = $exportResult->newCsvFile();
        $file->writeRow(['first']);
        $file->close();

        $file = $exportResult->newCsvFile();
        $file->writeRow(['second']);
        $file->close();

        $archiveFile = $exportResult->getResultFileName();
        $this->assertEquals('mock.tar', $archiveFile);
    }

    /**
     * @depends testArchiveResultFileNameCallback
     */
    public function testForceArchive()
    {
        $exportResult = $this->createExportResult();
        $exportResult->forceArchive = true;
        $exportResult->archiver = function ($files, $dirName) {
            return 'force.tar';
        };

        $file = $exportResult->newCsvFile();
        $file->writeRow(['first']);
        $file->close();

        $archiveFile = $exportResult->getResultFileName();
        $this->assertEquals('force.tar', $archiveFile);
    }

    /**
     * @depends testNewCsvFile
     */
    public function testDelete()
    {
        $exportResult = $this->createExportResult();
        $file = $exportResult->newCsvFile();
        $file->writeRow(['foo']);
        $file->close();

        $this->assertTrue($exportResult->delete());
        $this->assertFalse(file_exists($file->name));
        $this->assertFalse(file_exists($exportResult->getDirName()));
    }

    /**
     * @depends testDelete
     */
    public function testAutoDelete()
    {
        $exportResult = $this->createExportResult();
        $file = $exportResult->newCsvFile();
        $file->writeRow(['foo']);
        $file->close();

        $fileName = $file->name;
        $dirName = $exportResult->getDirName();

        unset($exportResult);

        $this->assertFalse(file_exists($dirName));
        $this->assertFalse(file_exists($fileName));
    }

    /**
     * @depends testResultFileName
     */
    public function testCopy()
    {
        $exportResult = $this->createExportResult();
        $file = $exportResult->newCsvFile();
        $file->writeRow(['foo']);
        $file->close();

        $destinationFileName = $this->ensureTestFilePath() . '/destination.csv';
        $exportResult->copy($destinationFileName);

        $this->assertTrue(file_exists($destinationFileName));
        $this->assertTrue(file_exists($exportResult->getResultFileName()));
    }

    /**
     * @depends testResultFileName
     */
    public function testMove()
    {
        $exportResult = $this->createExportResult();
        $file = $exportResult->newCsvFile();
        $file->writeRow(['foo']);
        $file->close();

        $destinationFileName = $this->ensureTestFilePath() . '/destination.csv';
        $exportResult->move($destinationFileName);

        $this->assertTrue(file_exists($destinationFileName));
        $this->assertFalse(file_exists($exportResult->getResultFileName()));
    }
}