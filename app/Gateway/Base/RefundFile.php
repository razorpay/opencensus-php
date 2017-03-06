<?php

namespace RZP\Gateway\Base;

use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;

class RefundFile extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->mail = Mail::getFacadeRoot();
    }

    public function generate($input)
    {
        ;
    }

    protected function sendRefundEmail($fileData = [])
    {
        ;
    }

    protected function createFile(string $extension, $content, string $fileName, string $type, string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($extension)
                ->content($content)
                ->name($fileName)
                ->store($store)
                ->type($type)
                ->save();

        return $creator;
    }

    protected function getFileToWriteName($ext = '.txt')
    {
        return $this->getFileToWriteNameWithoutExt() . $ext;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        return static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
