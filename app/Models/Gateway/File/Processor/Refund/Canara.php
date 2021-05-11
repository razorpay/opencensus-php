<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Gateway\Netbanking\Canara\RefundFileFields;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Services\NbPlus\Netbanking;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;


use Config;

class Canara extends Base
{
    use FileHandler;

    const FILE_NAME              = 'RPGREF';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::CANARA_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_CANARA;
    const GATEWAY_CODE           = [IFSC::CNRB, IFSC::SYNB];
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BANK_CODE              = 'CNRB';
    const HEADERS                = RefundFileFields::COLUMN_HEADERS;
    const BASE_STORAGE_DIRECTORY = 'Canara/Refund/Netbanking/';

    //protected $config;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row[ConstantsEntity::PAYMENT][PaymentEntity::CREATED_AT],
                                                          Timezone::IST)
                                                          ->format('d-m-Y H:i:s');

            $refundDate = Carbon::createFromTimestamp($row[ConstantsEntity::REFUND][RefundEntity::CREATED_AT],
                                                     Timezone::IST)
                                                     ->format('d-m-Y H:i:s');

            $paymentId = $row[ConstantsEntity::PAYMENT][PaymentEntity::ID];

            $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($paymentId,
                                                                           Action::AUTHORIZE);

            $content[] = [
                            $transactionDate,
                            $refundDate,
                            $this->fetchBankAccountNumber($row, $netbanking),
                            $paymentId,
                            $row[ConstantsEntity::REFUND][RefundEntity::ID],
                            $this->getFormattedAmount($row[ConstantsEntity::PAYMENT]
                                                        [PaymentEntity::AMOUNT]),
                            $this->getFormattedAmount($row[ConstantsEntity::REFUND]
                                                         [RefundEntity::AMOUNT])
                        ];
        }

        $initialLine = $this->getInitialLine('|');

        $formattedData = $this->getTextData($content, $initialLine, '|');

        return $formattedData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $date;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'  => basename($file->getLocation()),
            'signed_url' => $signedUrl,
        ];

        return $mailData;
    }
    protected function fetchBankAccountNumber($data, $netbanking)
    {
        if ($data['payment']['cps_route'] === PaymentEntity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $netbanking[NetbankingEntity::BANK_PAYMENT_ID];
    }
}
