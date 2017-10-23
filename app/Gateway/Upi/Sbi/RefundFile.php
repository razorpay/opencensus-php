<?php

namespace RZP\Gateway\Upi\Sbi;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    // TODO: Fix these constants
    const PG_MERCHANT_ID  = 'pgMerchantId';
    const REFUND_REQ_NO   = 'refundReqNo';
    const TRANS_REF_NO    = 'transRefNo';
    const CUSTOMER_REF_NO = 'customerRefNo';
    const ORDER_NO        = 'orderNo';
    const REFUND_REQ_AMT  = 'refundReqAmt';
    const REFUND_REMARK   = 'refundRemark';

    /**
     * Headers of the CSV file
     * @var array
     */
    protected static $headers = [
        self::PG_MERCHANT_ID,
        self::REFUND_REQ_NO,
        self::TRANS_REF_NO,
        self::CUSTOMER_REF_NO,
        self::ORDER_NO,
        self::REFUND_REQ_AMT,
        self::REFUND_REMARK
    ];

    public function __construct()
    {
        parent::__construct();

        $this->repo = $this->app['repo']->upi;
    }

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

        foreach ($input['data'] as $row)
        {
            $upi = $this->repo->findByPaymentIdAndActionOrFail($row['payment']['id'], Base\Action::AUTHORIZE);

            $data[] = [
                self::PG_MERCHANT_ID  => $this->getMerchantId(),
                self::REFUND_REQ_NO   => $row['refund']['id'],
                self::TRANS_REF_NO    => $upi->getNpciReferenceId(), // TODO: Verify this
                self::CUSTOMER_REF_NO => $upi->getCustomerReferenceId(),
                self::ORDER_NO        => $row['payment']['id'],
                self::REFUND_REQ_AMT  => $row['refund']['amount'] / 100,
                self::REFUND_REMARK   => 'Refund'
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