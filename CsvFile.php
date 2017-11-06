<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

use yii\base\Exception;
use yii\base\BaseObject;
use yii\helpers\FileHelper;

/**
 * CsvFile represents the CSV file.
 *
 * Example:
 *
 * ```php
 * use yii2tech\csvgrid\CsvFile;
 *
 * $csvFile = new CsvFile(['name' => '/path/to/file.csv']);
 * foreach (Item::find()->all() as $item) {
 *     $csvFile->writeRow($item->attributes);
 * }
 * $csvFile->close();
 * ```
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CsvFile extends BaseObject
{
    /**
     * @var string the path of the file.
     * Note, this is a temporary file which will be automatically deleted on object destruction.
     */
    public $name;
    /**
     * @var string delimiter between the CSV file rows.
     */
    public $rowDelimiter = "\r\n";
    /**
     * @var string delimiter between the CSV file cells.
     */
    public $cellDelimiter = ',';
    /**
     * @var string the cell content enclosure.
     */
    public $enclosure = '"';
    /**
     * @var int the count of entries written into the file.
     */
    public $entriesCount = 0;
    /**
     * @var bool|string whether to write Byte Order Mark (BOM) at the beginning of the file.
     * BOM might be necessary for the unicode-encoded file to be correctly displayed at some programs.
     * Default is `false` meaning the BOM writing is disabled. If set to `true` BOM for UTF-8 encoding will be written.
     * This field can be specified as a string, which holds exact BOM to be written. For example:
     *
     * ```php
     * pack('CCC', 0xfe, 0xff); // UTF-16 (BE)
     * ```
     *
     * @since 1.0.2
     */
    public $writeBom = false;

    /**
     * @var resource file resource handler.
     */
    protected $fileHandler;


    /**
     * Destructor.
     * Makes sure the opened file is closed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Opens the related file for writing.
     * @throws Exception on failure.
     * @return bool success.
     */
    public function open()
    {
        if ($this->fileHandler === null) {
            FileHelper::createDirectory(dirname($this->name));
            $this->fileHandler = fopen($this->name, 'w+');
            if ($this->fileHandler === false) {
                throw new Exception('Unable to create/open file "' . $this->name . '".');
            }
        }
        return true;
    }

    /**
     * Close the related file if it was opened.
     * @return bool success.
     */
    public function close()
    {
        if ($this->fileHandler) {
            fclose($this->fileHandler);
            $this->fileHandler = null;
        }
        return true;
    }

    /**
     * Deletes the associated file.
     * @return bool success.
     */
    public function delete()
    {
        $this->close();
        if (file_exists($this->name)) {
            unlink($this->name);
        }
        return true;
    }

    /**
     * Writes the given row data into the file in CSV format.
     * @param mixed $rowData raw data can be array or object.
     * @return int the number of bytes written.
     */
    public function writeRow($rowData)
    {
        if ($this->writeBom !== false && $this->entriesCount === 0) {
            $bom = is_string($this->writeBom) ? $this->writeBom : pack('CCC', 0xef, 0xbb, 0xbf);
            $this->writeContent($bom);
        }
        $result = $this->writeContent($this->composeRowContent($rowData));
        $this->entriesCount++;
        return $result;
    }

    /**
     * Composes the given data into the CSV row.
     * @param array $rowData data to be composed.
     * @return string CSV format row
     */
    protected function composeRowContent($rowData)
    {
        $securedRowData = [];
        foreach ($rowData as $content) {
            $securedRowData[] = $this->encodeValue($content); // unable to use `array_map()` against objects
        }

        if ($this->entriesCount > 0) {
            $rowContent = $this->rowDelimiter;
        } else {
            $rowContent = '';
        }
        $rowContent .= implode($this->cellDelimiter, $securedRowData);
        return $rowContent;
    }

    /**
     * Secures the given value so it can be written in CSV cell.
     * @param string $value value to be secured
     * @return mixed secured value.
     */
    protected function encodeValue($value)
    {
        $value = (string)$value;

        if (empty($this->enclosure)) {
            return $value;
        }

        return $this->enclosure . str_replace($this->enclosure, str_repeat($this->enclosure, 2), $value) . $this->enclosure;
    }

    /**
     * Writes the given content into the file.
     * @param string $content content to be written.
     * @return int the number of bytes written.
     * @throws Exception on failure.
     */
    protected function writeContent($content)
    {
        $this->open();
        $bytesWritten = fwrite($this->fileHandler, $content);
        if ($bytesWritten === false) {
            throw new Exception('Unable to write file "' . $this->name . '".');
        }
        return $bytesWritten;
    }
}