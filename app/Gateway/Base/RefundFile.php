<?php

namespace RZP\Gateway\Base;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;

class RefundFile extends Base\Core
{
    /**
     * Minutes for which Signed Url is valid
     */
    const SIGNED_URL_DURATION = '1440';

    public function __construct()
    {
        parent::__construct();
    }

    public function generate($input)
    {
        ;
    }

    protected function sendRefundEmail($fileData = [])
    {
        ;
    }

    protected function createFile(
                        string $extension,
                        $content,
                        string $fileName,
                        string $type,
                        string $store = FileStore\Store::S3)
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

    protected function getFileToWriteName($ext = FileStore\Format::TXT)
    {
        return $this->getFileToWriteNameWithoutExt() . '.' . $ext;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        //TODO : Move it to common place
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            $count--;

           if (($ignoreLastNewline === false) or
               (($ignoreLastNewline === true) and ($count > 0)))
           {
                $txt .= "\r\n";
           }
        }

        return $txt;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
