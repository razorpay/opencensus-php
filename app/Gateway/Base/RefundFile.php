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

    protected function getFileToWriteName($ext = '.txt')
    {
        return $this->getFileToWriteNameWithoutExt() . $ext;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $mode = $this->mode;

        return static::$fileToWriteName . '_' . $mode . '_' . $time;
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row)) ;

            $count--;

           if (($ignoreLastNewline === false) or
               (($ignoreLastNewline === true) and ($count > 0)))
           {
                $txt .= "\r\n";
           }
        }

        return $txt;
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

        // TODO : move this to Mock class

        if (($this->mode === Mode::TEST) and (gettype($content) === 'string'))
        {
            $lines = substr_count($content, "\n");

            switch($lines)
            {
                case 3:
                    $store = 'invalid';
                    break;

                case 4:
                    $type = 'invalid';
                    break;

                case 5:
                    $extension = 'invalid';
                    break;
            }
        }

        $creator->extension($extension)
                ->content($content)
                ->name($fileName)
                ->store($store)
                ->type($type)
                ->save();

        return $creator;
    }
}
