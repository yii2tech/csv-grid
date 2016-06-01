<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\csvgrid;

use yii\base\Exception;
use yii\base\Object;

/**
 * File represents the CSV file.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class File extends Object
{
    /**
     * @var string the path of the file.
     * Note, this is a temporary file which will be automatically deleted on object destruction.
     */
    public $name;

    /**
     * @var resource file resource handler.
     */
    protected $fileHandler;


    /**
     * Destructor.
     * Removes associated temporary file if it exists.
     */
    public function __destruct()
    {
        $this->delete();
    }

    /**
     * Opens the related file for writing.
     * @throws Exception on failure.
     * @return boolean success.
     */
    public function open()
    {
        if ($this->fileHandler === null) {
            $this->fileHandler = fopen($this->name, 'w+');
            if ($this->fileHandler === false) {
                throw new Exception('Unable to create/open file "' . $this->name . '".');
            }
        }
        return true;
    }

    /**
     * Close the related file if it was opened.
     * @return boolean success.
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
     * @return boolean success.
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
     * Writes the given content into the file.
     * @param string $content content to be written.
     * @return integer the number of bytes written.
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