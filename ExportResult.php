<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

use Yii;
use yii\base\Exception;
use yii\base\BaseObject;
use yii\helpers\FileHelper;
use yii\web\Response;
use ZipArchive;

/**
 * ExportResult represents CSV export result.
 *
 * @see CsvGrid
 *
 * @property string $dirName temporary files directory name
 * @property string $resultFileName result file name
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ExportResult extends BaseObject
{
    /**
     * @var string base path for the temporary directory and files.
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
     * @var callable|null PHP callback, which should be used to archive result files.
     * Signature:
     *
     * ```php
     * function (array $files, string $dirName) {
     *     return string // archive file name
     * }
     * ```
     *
     * If not set ZIP archive will be created using PHP 'zip' extension.
     */
    public $archiver;
    /**
     * @var bool whether to always archive result, even if has only single file.
     */
    public $forceArchive = false;

    /**
     * @var string temporary files directory name
     */
    private $_dirName;
    /**
     * @var string name of the result file.
     */
    private $_resultFileName;


    /**
     * Destructor.
     * Makes sure the temporary directory removed.
     */
    public function __destruct()
    {
        $this->delete();
    }

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
                    $files = [];
                    foreach ($this->csvFiles as $csvFile) {
                        $files[] = $csvFile->name;
                    }
                    $this->_resultFileName = $this->archiveFiles($files);
                } else {
                    $csvFile = reset($this->csvFiles);
                    if ($this->forceArchive) {
                        $this->_resultFileName = $this->archiveFiles([$csvFile->name]);
                    } else {
                        $this->_resultFileName = $csvFile->name;
                    }
                }
            }
        }
        return $this->_resultFileName;
    }

    /**
     * Creates new CSV file in result set.
     * @param array $config file instance configuration.
     * @return CsvFile file instance.
     */
    public function newCsvFile($config = [])
    {
        $selfFileName = $this->fileBaseName . '-' . str_pad((count($this->csvFiles) + 1), 3, '0', STR_PAD_LEFT);

        /* @var $file CsvFile */
        $file = Yii::createObject(array_merge(['class' => CsvFile::className()], $config));
        $file->name = $this->getDirName() . DIRECTORY_SEPARATOR . $selfFileName . '.csv';

        $this->csvFiles[] = $file;

        return $file;
    }

    /**
     * Deletes associated directory with all internal files.
     * @return bool whether file has been deleted.
     */
    public function delete()
    {
        if (!empty($this->_dirName)) {
            $this->csvFiles = []; // allow running destructor of file objects first
            FileHelper::removeDirectory($this->_dirName);
            return true;
        }
        return false;
    }

    /**
     * Copies result file into another location.
     * @param string $destinationFileName destination file name (may content path alias).
     * @return bool whether operation was successful.
     */
    public function copy($destinationFileName)
    {
        $destinationFileName = $this->prepareDestinationFileName($destinationFileName);
        return copy($this->getResultFileName(), $destinationFileName);
    }

    /**
     * Moves result file into another location.
     * @param string $destinationFileName destination file name (may content path alias).
     * @return bool whether operation was successful.
     */
    public function move($destinationFileName)
    {
        $destinationFileName = $this->prepareDestinationFileName($destinationFileName);
        $result = rename($this->getResultFileName(), $destinationFileName);
        $this->delete();
        return $result;
    }

    /**
     * Saves this file.
     * @param string $file destination file name (may content path alias).
     * @param bool $deleteTempFile whether to delete associated temp file or not.
     * @return bool whether operation was successful.
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($deleteTempFile) {
            return $this->move($file);
        }
        return $this->copy($file);
    }

    /**
     * Prepares response for sending a result file to the browser.
     * Note: this method works only while running web application.
     * @param string $name the file name shown to the user. If null, it will be determined from [[tempName]].
     * @param array $options additional options for sending the file. See [[\yii\web\Response::sendFile()]] for more details.
     * @return Response application response instance.
     */
    public function send($name = null, $options = [])
    {
        $response = Yii::$app->getResponse();
        $response->on(Response::EVENT_AFTER_SEND, [$this, 'delete']);
        return $response->sendFile($this->getResultFileName(), $name, $options);
    }

    /**
     * Prepares raw destination file name for the file copy/move operation:
     * resolves path alias and creates missing directories.
     * @param string $destinationFileName destination file name
     * @return string real destination file name
     */
    protected function prepareDestinationFileName($destinationFileName)
    {
        $destinationFileName = Yii::getAlias($destinationFileName);
        $destinationPath = dirname($destinationFileName);
        FileHelper::createDirectory($destinationPath);
        return $destinationFileName;
    }

    /**
     * Creates an archive files from given files.
     * @param array $files source file names
     * @return string archive file name
     * @throws Exception on failure.
     */
    protected function archiveFiles($files)
    {
        if ($this->archiver !== null) {
            return call_user_func($this->archiver, $files, $this->getDirName());
        }

        $archiveFileName = $this->getDirName() . DIRECTORY_SEPARATOR . $this->fileBaseName . '.zip';

        $zip = new ZipArchive();
        $zipStatus = $zip->open($archiveFileName, ZipArchive::CREATE);
        if ($zipStatus !== true) {
            throw new Exception('Unable to create ZIP archive: error#' . $zipStatus);
        }

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        return $archiveFileName;
    }
}