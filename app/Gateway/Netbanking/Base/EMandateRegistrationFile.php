<?php

namespace RZP\Gateway\Netbanking\Base;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\FileStore;

class EMandateRegistrationFile extends Base\Core
{
    // In mins
    const SIGNED_URL_DURATION = 1440;

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

    protected function getFileToWriteName(string $ext): string
    {
        return $this->getFileToWriteNameWithoutExt() . '.' . $ext;
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y-H-i-s');

        return static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}