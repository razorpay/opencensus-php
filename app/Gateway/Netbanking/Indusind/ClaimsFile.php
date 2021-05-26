<?php

namespace RZP\Gateway\Netbanking\Indusind;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Constants\Mode;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class ClaimsFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'PGClaimRazorpay';

    Const BASE_STORAGE_DIRECTORY = 'Indusind/Claims/Netbanking/';

    public function generate($input)
    {
        list($totalAmount , $count, $data) =  $this->getClaimsData($input);

        $fileText = $this->getTextData($data);

        $fileName = $this->getFileToWriteNameWithoutExt();

        // Creating a file with txt format
        $creator = $this->createFile(
            FileStore\Format::TXT,
            $fileText,
            $fileName,
            FileStore\Type::INDUSIND_NETBANKING_CLAIM);

        $file = $creator->get();

        $today = Carbon::now(Timezone::IST)->format('d_m_Y');

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];


        return [
            'total_amount'    => $totalAmount,
            'count'           => $count,
            'signed_url'      => $signedFileUrl,
            'local_file_path' => $file['local_file_path'],
        ];
    }

    protected function getClaimsData(array $input)
    {
        $totalAmount = 0;
        $count = 0;

        $data = [];

         foreach ($input['data'] as $index => $row)
        {
            $data[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::BANK_REFERENCE_ID  => $row['gateway']['bank_payment_id']
            ];

            $totalAmount += $row['payment']['amount'] / 100;

            $count++;
        }


        return [$totalAmount , $count, $data];
    }

    protected function getTextData(array $data, string $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine . $txt;

        return $txt;
    }

     /*
     * @override parent class's method
     */
    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        if ($this->mode === Mode::TEST)
        {
            return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . $time . $this->mode;
        }

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . $time;
    }
}
