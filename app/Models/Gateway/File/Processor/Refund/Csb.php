<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Constants\Mode as RZPMode;
use RZP\Services\NbPlus\Netbanking;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Netbanking\Csb\Gateway as CsbGateway;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Csb extends Base
{
    use FileHandler;

    const FILE_NAME              = 'CBS_RAZORPAY';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::CSB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_CSB;
    const GATEWAY_CODE           = IFSC::CSBK;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Csb/Refund/Netbanking/';

    const BANK_CODE              = 'CSB';
    const DATE_FORMAT            = 'd-m-y';

    protected $config;

    protected function formatDataForFile(array $data)
    {
        $this->loadGatewayConfig();

        $content = [];

        $srNo = 1;

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                                $row[ConstantsEntity::PAYMENT][PaymentEntity::CREATED_AT],
                                Timezone::IST)
                            ->format(self::DATE_FORMAT);

            $refundDate = Carbon::createFromTimestamp(
                                      $row[ConstantsEntity::REFUND][RefundEntity::CREATED_AT],
                                      Timezone::IST)
                                  ->format(self::DATE_FORMAT);

            $paymentId = $row[ConstantsEntity::PAYMENT][PaymentEntity::ID];

            if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
            {
                $bankRefId = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
            }
            else
            {
                $bankRefId  = $row['gateway'][NetbankingEntity::BANK_PAYMENT_ID]; // payment through api
            }

            $content[] = [
                'Sr.No'              => $srNo++,
                'Refund Id'          => $row[ConstantsEntity::REFUND][RefundEntity::ID],
                'Bank Id'            => self::BANK_CODE,
                'Merchant Name'      => CsbGateway::PAYEE_ID,
                'Txn date'           => $date,
                'Refund Date'        => $refundDate,
                'Bank Merchant Code' => $this->getMerchantId($row[ConstantsEntity::TERMINAL]),
                'Bank Ref No'        => $bankRefId,
                'PGI Reference No'   => $paymentId,
                'Txn Amount(Rs Ps)'  => $row[ConstantsEntity::PAYMENT][PaymentEntity::AMOUNT] / 100,
                'Refund'             => $row[ConstantsEntity::REFUND][RefundEntity::AMOUNT] / 100,
            ];
        }

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        return static::BASE_STORAGE_DIRECTORY . self::FILE_NAME . '_' . $date;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $totalAmount = array_reduce(
                        $data,
                        function(int $carry, array $item)
                        {
                            $carry += $item[ConstantsEntity::REFUND][RefundEntity::AMOUNT];

                            return $carry;
                        },
                        0);

        $totalAmount = $totalAmount / 100;

        $totalAmount = number_format($totalAmount, 2, '.', '');

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailData = [
            'file_name'  => basename($file->getLocation()),
            'signed_url' => $signedUrl,
            'amount'     => $totalAmount,
            'count'      => count($data),
            'date'       => $today
        ];

        return $mailData;
    }

    protected function loadGatewayConfig()
    {
        $configGatewayStr = 'gateway.' . self::GATEWAY;

        $this->config = $this->app['config']->get($configGatewayStr);
    }

    protected function getMerchantId($terminal): string
    {
        $merchantId = $this->config['test_merchant_id'];

        if ($this->mode === RZPMode::LIVE)
        {
            $merchantId = $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
        }

        return $merchantId;
    }
}
