<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

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

    public function __construct()
    {
        parent::__construct();
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
        $this->format = $format;

        return $this;
    }

    public function save()
    {
        ;
    }
}
