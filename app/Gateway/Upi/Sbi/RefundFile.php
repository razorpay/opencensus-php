<?php

namespace RZP\Gateway\Upi\Sbi;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    const PG_MERCHANT_ID  = 'PG MERCHANT ID';
    const REFUND_REQ_NO   = 'REFUND REQ NO';
    const TRANS_REF_NO    = 'TRANS REF NO.';
    const CUSTOMER_REF_NO = 'CUSTOMER REF NO.';
    const ORDER_NO        = 'ORDER NO';
    const REFUND_REQ_AMT  = 'REFUND REQ AMT';
    const REFUND_REMARK   = 'REFUND REMARK';

    public function generate($input)
    {
        $creator = $this->getCreator($input);

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        return $fileData['file_path'];
    }

    protected function getCreator(array $input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $store = FileStore\Store::S3;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::CSV)
                ->content($data)
                ->name($fileName)
                ->store($store)
                ->type(FileStore\Type::SBI_UPI_REFUND)
                ->metadata($metadata)
                ->save();

        return $creator;
    }

    protected function getRefundData(array $input)
    {
        $data = [];

        foreach ($input['data'] as $row)
        {
            $data[] = [
                self::PG_MERCHANT_ID  => $this->getMerchantId(),
                self::REFUND_REQ_NO   => $row['refund']['id'],
                self::TRANS_REF_NO    => $row['gateway']['npci_reference_id'], // TODO: Verify this - UPI TXN REF NO
                self::CUSTOMER_REF_NO => $row['gateway']['gateway_payment_id'],
                self::ORDER_NO        => $row['payment']['id'],
                self::REFUND_REQ_AMT  => $row['refund']['amount'] / 100,
                self::REFUND_REMARK   => 'Refund'
            ];
        }

        return $data;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST);

        $fileArray = [
            $this->getMerchantId(),
            $time->format('dmY'),
            $time->format('Hi')
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

    /**
     * TODO: Make changes for this
     * @return array
     */
    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }
}
