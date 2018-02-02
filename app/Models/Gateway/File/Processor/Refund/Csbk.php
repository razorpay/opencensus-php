<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Processor\FileHandler;

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

    protected function formatDataForFile(array $data)
    {
        return array_reduce(
            $data,
            function(array $carry, array $row)
            {
                $date = Carbon::createFromTimestamp(
                            $row['payment']['created_at'],
                            Timezone::IST)
                            ->format('d-m-y');

                $refundDate = Carbon::createFromTimestamp(
                                $row['refund']['created_at'],
                                Timezone::IST)
                                ->format('d-m-y');

                $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($row['payment']['id'],
                                                                                Action::AUTHORIZE);

                $carry[] = [
                    'Sr.No'              => sizeof($carry) + 1,
                    'Refund Id'          => $row['refund']['id'],
                    'Bank Id'            => self::BANK_CODE,
                    'Merchant Name'      => self::MERCHANT_NAME,
                    'Txn date'           => $date,
                    'Refund Date'        => $refundDate,
                    'Bank Merchant Code' => $netbanking['reference1'],
                    'Bank Ref No'        => $netbanking['bank_payment_id'],
                    'PGI Reference No'   => $row['payment']['id'], // TODO: Check if this is actually payment id
                    'Txn Amount(Rs Ps)'  => $row['payment']['amount'] / 100,
                    'Refund'             => $row['refund']['amount'] / 100,
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
                            $carry += $item['refund']['amount'] / 100;

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
