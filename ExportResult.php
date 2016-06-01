<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

use Yii;
use yii\base\Object;

/**
 * ExportResult
 *
 * @property string $dirName files directory name
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ExportResult extends Object
{
    /**
     * @var string base path for the temporary files.
     */
    public $basePath = '@runtime/csv-grid';
    /**
     * @var string base name, which should be used for the created files.
     */
    public $fileBaseName = 'data';
    /**
     * @var CsvFile[] related CSV files.
     */
    public $csvFiles = [];

    /**
     * @var string files directory name
     */
    private $_dirName;
    /**
     * @var string name of the result file.
     */
    private $_resultFileName;


    /**
     * @return string files directory name
     */
    public function getDirName()
    {
        if ($this->_dirName === null) {
            $this->_dirName = Yii::getAlias($this->basePath) . DIRECTORY_SEPARATOR . uniqid(time(), true);
        }
        return $this->_dirName;
    }

    /**
     * @param string $dirName files directory name
     */
    public function setDirName($dirName)
    {
        $this->_dirName = $dirName;
    }

    /**
     * @return string result file name
     */
    public function getResultFileName()
    {
        if ($this->_resultFileName === null) {
            if (!empty($this->csvFiles)) {
                if (count($this->csvFiles) > 1) {
                    // TODO archive results
                } else {
                    $csvFile = reset($this->csvFiles);
                    $this->_resultFileName = $csvFile->name;
                }
            }
        }
        return $this->_resultFileName;
    }

    /**
     * Creates new CSV file in result set.
     * @return CsvFile file instance.
     */
    public function newCsvFile()
    {
        $selfFileName = $this->fileBaseName . '-' . str_pad((count($this->csvFiles) + 1), 3, '0', STR_PAD_LEFT);

        $file = new CsvFile();
        $file->name = $this->getDirName() . DIRECTORY_SEPARATOR . $selfFileName . '.csv';

        $this->csvFiles[] = $file;

        return $file;
    }
}