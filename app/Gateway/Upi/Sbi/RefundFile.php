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
            $upi = $this->repo->findByPaymentIdAndActionOrFail($row['payment']['id'], Base\Action::AUTHORIZE);

            $data[] = [
                self::PG_MERCHANT_ID  => $this->getMerchantId(),
                self::REFUND_REQ_NO   => $row['refund']['id'],
                self::TRANS_REF_NO    => $upi->getNpciReferenceId(), // TODO: Verify this - UPI TXN REF NO
                self::CUSTOMER_REF_NO => $upi->getGatewayPaymentId(),
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