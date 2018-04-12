<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Csbk extends Base
{
    use FileHandler;

    const FILE_NAME              = 'CBS_RAZORPAY';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::CSB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_CSB;
    const GATEWAY_CODE           = IFSC::CSBK;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    const BANK_CODE              = 'CSB';
    const MERCHANT_NAME          = 'RAZORPAY';
    const DATE_FORMAT            = 'd-m-y';

    protected function formatDataForFile(array $data)
    {
        return array_reduce(
            $data,
            function(array $carry, array $row)
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

                $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($paymentId,
                                                                                Action::AUTHORIZE);

                $carry[] = [
                    'Sr.No'              => (count($carry) + 1),
                    'Refund Id'          => $row[ConstantsEntity::REFUND][RefundEntity::ID],
                    'Bank Id'            => self::BANK_CODE,
                    'Merchant Name'      => self::MERCHANT_NAME,
                    'Txn date'           => $date,
                    'Refund Date'        => $refundDate,
                    'Bank Merchant Code' => $netbanking[NetbankingEntity::REFERENCE1],
                    'Bank Ref No'        => $netbanking[NetbankingEntity::BANK_PAYMENT_ID],
                    'PGI Reference No'   => $paymentId, // TODO: Check if this is actually payment id
                    'Txn Amount(Rs Ps)'  => $row[ConstantsEntity::PAYMENT][PaymentEntity::AMOUNT] / 100,
                    'Refund'             => $row[ConstantsEntity::REFUND][RefundEntity::AMOUNT] / 100,
                ];

                return $carry;
            },
            []);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        return self::FILE_NAME . '_' . $date;
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
                            $carry += $item[ConstantsEntity::REFUND][RefundEntity::AMOUNT] / 100;

                            return $carry;
                        },
                        0);

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl,
            'amount'     => $totalAmount,
            'count'      => count($data),
            'date'       => $today
        ];

        return $mailData;
    }
}
