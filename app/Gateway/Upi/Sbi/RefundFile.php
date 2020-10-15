<?php

namespace RZP\Gateway\Upi\Sbi;

use RZP\Constants;
use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Gateway\Upi\Base as Upi;

class RefundFile extends Base\RefundFile
{
    /**
     * A constant used to pull out the corresponding key in the input
     */
    const GATEWAY         = 'gateway';

    /**
     * File column names are below
     * @see https://drive.google.com/a/razorpay.com/file/d/1nq0NwAL7_BYc2K0RCMd2ZCe7tsgTzrYE/view?usp=sharing
     * @see https://drive.google.com/drive/u/0/folders/0B1kf6HOmx7JBTmMzTXgwQVRrNm8
     */

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
                self::PG_MERCHANT_ID  => $row[self::GATEWAY][Upi\Entity::GATEWAY_MERCHANT_ID],
                self::REFUND_REQ_NO   => $row[Constants\Entity::REFUND][Refund\Entity::ID],
                self::TRANS_REF_NO    => $row[self::GATEWAY][Upi\Entity::GATEWAY_PAYMENT_ID],
                self::CUSTOMER_REF_NO => $row[self::GATEWAY][Upi\Entity::NPCI_REFERENCE_ID],
                self::ORDER_NO        => $row[Constants\Entity::PAYMENT][Payment\Entity::ID],
                self::REFUND_REQ_AMT  => $row[Constants\Entity::REFUND][Payment\Entity::AMOUNT] / 100,
                self::REFUND_REMARK   => 'Refund from ' . Gateway::DEFAULT_PAYEE_VPA,
            ];
        }

        return $data;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dt = Carbon::now(Timezone::IST);

        return 'SBI_UPI_ ' . $dt->format('dmY_Hi');
    }

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
