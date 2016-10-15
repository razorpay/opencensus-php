<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;
use RZP\Exception;

class Creator extends Base\Core
{
    /**
     * @var FileStore\Entity
     */
    protected $file;

    /**
     * Local file instance
     * @var UploadedFile
     */
    protected $localFile;

    protected $fileType;

    protected $type;

    protected $name;

    protected $delimiter;

    public function __construct()
    {
        parent::__construct();

        $this->file = new Entity;
    }

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    public function localFile($file)
    {
        $this->localFile = $file;

        return $this;
    }

    public function format($format)
    {
        $this->file->setformat($format);

        return $this;
    }

    public function delimiter($delimiter = ',')
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function save()
    {
        $result = Format::validateContentTypeForFormat($this->content, $this->file->getFormat());

        if ($result === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Content type not valid for file format specified.');
        }

        if ($this->localFile === null)
        {
            $this->writeToLocalFile();
        }

        return $this;
    }

    public function getFileId()
    {
        return $this;
    }

    protected function writeToLocalFile()
    {
        if ($this->file->getFormat() === Format::CSV)
        {
            $content = $this->content;
            $fullpath = $this->getFullFilePath($name);

            $file = fopen($fullpath, 'w');
            fwrite($file, $txt);
            fclose($file);

            chmod($fullpath, 0777);  // keep it 0777. This step is important.

            return $fullpath;
        }
    }
}
