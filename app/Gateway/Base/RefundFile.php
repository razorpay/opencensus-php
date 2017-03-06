<?php

namespace RZP\Gateway\Base;

use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

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
}
