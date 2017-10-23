<?php

namespace RZP\Gateway\Upi\Sbi;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    public function generate($input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::CSV,
            $data,
            $fileName,
            FileStore\Type::SBI_UPI_REFUND);

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        // TODO: Upload the file

        return $fileData['file_path'];
    }

    protected function getRefundData(array $input)
    {
        $data = [];

        $fileName = $this->getFileToWriteName(FileStore\Format::CSV);

        foreach ($input['data'] as $row)
        {
            // TODO: Is this needed?
//            if (isset($row['gateway']) === false)
//            {
//                continue;
//            }

            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('Y-m-d');

            $data[] = [
                // TODO: Add the data here
            ];
        }

        return $data;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $fileArray = [
            $this->getMerchantId(),
            Carbon::now(Timezone::IST)->format('dmY'),
            Carbon::now(Timezone::IST)->format('Hi')
        ];

        return implode('_', $fileArray);
    }

    protected function getMerchantId()
    {
        return $this->getGatewayClass()->getMerchantId();
    }

    protected function getGatewayClass()
    {
        return new Gateway();
    }
}