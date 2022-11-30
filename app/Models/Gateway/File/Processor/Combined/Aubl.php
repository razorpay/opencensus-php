<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Services\NbPlus\Netbanking;
use RZP\Exception\GatewayFileException;
use RZP\Reconciliator\NetbankingAusf\Constants;

class Aubl extends Base
{
    const FILE_TYPE = FileStore\Type::AUBL_NETBANKING_COMBINED;
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_NAME = 'ALL_TXN_REPORT_AUBL';

    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $count = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0
        ];

        $claimsFile = [];
        $summaryFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::AUBL_NETBANKING_CLAIM);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('d.m.Y');

        $this->createCombinedFile($data);

        $summaryFile = $this->getFileData(FileStore\Type::AUBL_NETBANKING_COMBINED);

        $config = $this->app['config']->get('nodal.axis');

        $accountDetails = [
            'bankName'      => 'Axis Bank Ltd',
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $config['ifsc_code'],
        ];

        return [
            'bankName'    => 'Aubl',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'summaryFile' => $summaryFile,
            'emails'      => $this->gatewayFile->getRecipients(),
            'accountDetails' => $accountDetails
        ];
    }

    protected function getFileData(string $type)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, $type)
            ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $fileData = [
            'url'  => $signedUrl,
            'name' => basename($file->getLocation()),
        ];

        return $fileData;
    }

    protected function createCombinedFile($data)
    {
        try
        {
            $fileData = $this->formatDataForFile($data);

            $creator = new FileStore\Creator;

            $creator->extension(self::EXTENSION)
                ->content($fileData)
                ->name(self::FILE_NAME)
                ->store(FileStore\Store::S3)
                ->type(self::FILE_TYPE)
                ->entity($this->gatewayFile)
                ->headers(false)
                ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForFile($data)
    {
        $formattedData[] = [
            Constants::Date => Constants::Date,
            Constants::TRANSACTION_TYPE => Constants::TRANSACTION_TYPE,
            Constants::PAYMENT_ID => Constants::PAYMENT_ID,
            Constants::PAYMENT_ID_EXT => Constants::PAYMENT_ID_EXT,
            Constants::MERCHANT_CODE => Constants::MERCHANT_CODE,
            Constants::USERREFERENCENO => Constants::USERREFERENCENO,
            Constants::EXTERNALREFERENCEID => Constants::EXTERNALREFERENCEID,
            Constants::EXTERNALREFERENCEID_EXT => Constants::EXTERNALREFERENCEID_EXT,
            Constants::TRANSACTION_DATE => Constants::TRANSACTION_DATE,
            Constants::AMOUNT => Constants::AMOUNT,
            Constants::REFUND_AMOUNT => Constants::REFUND_AMOUNT,
            Constants::SERVICE_CHARGES_AMOUNT => Constants::SERVICE_CHARGES_AMOUNT,
            Constants::DEBIT_ACCOUNT_ID => Constants::DEBIT_ACCOUNT_ID,
            Constants::STATUS => Constants::STATUS,
            Constants::MERCHANT_ACCOUNT_NUMBER => Constants::MERCHANT_ACCOUNT_NUMBER,
            Constants::MERCHANT_URL => Constants::MERCHANT_URL,
        ];

        $refundsData = [];
        $claimsData = [];

        if (isset($data['refunds']) === true)
        {
            foreach ($data['refunds'] as $row)
            {
                $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

                $dateVerbose = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i');

                $refundsData[] = [
                    Constants::Date                     => $date,
                    Constants::TRANSACTION_TYPE         => 'REFUND',
                    Constants::PAYMENT_ID               => $row['payment']['id'],
                    Constants::PAYMENT_ID_EXT           => '',
                    Constants::MERCHANT_CODE            => 'RAZORPAY',
                    Constants::USERREFERENCENO          => $row['payment']['id'],
                    Constants::EXTERNALREFERENCEID      => $this->fetchBankPaymentId($row),
                    Constants::EXTERNALREFERENCEID_EXT  => '',
                    Constants::TRANSACTION_DATE         => $dateVerbose,
                    Constants::AMOUNT                   => $this->getFormattedAmount($row['payment']['amount']),
                    Constants::REFUND_AMOUNT            => $this->getFormattedAmount($row['refund']['amount']),
                    Constants::SERVICE_CHARGES_AMOUNT   => '0',
                    Constants::DEBIT_ACCOUNT_ID         => '',
                    Constants::STATUS                   => 'REFUND',
                    Constants::MERCHANT_ACCOUNT_NUMBER  => '2121201131751367',  //TODO: TO BE DISCUSSED
                    Constants::MERCHANT_URL             => 'www.razorpay.com',
                ];
            }
        }

        if (isset($data['claims']) === true)
        {
            foreach ($data['claims'] as $row)
            {
                $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

                $dateVerbose = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i');

                $claimsData[] = [
                    Constants::Date                     => $date,
                    Constants::TRANSACTION_TYPE         => 'PAYMENT',
                    Constants::PAYMENT_ID               => $row['payment']['id'],
                    Constants::PAYMENT_ID_EXT           => '',
                    Constants::MERCHANT_CODE            => 'RAZORPAY',
                    Constants::USERREFERENCENO          => $row['payment']['id'],
                    Constants::EXTERNALREFERENCEID      => $this->fetchBankPaymentId($row),
                    Constants::EXTERNALREFERENCEID_EXT  => '',
                    Constants::TRANSACTION_DATE         => $dateVerbose,
                    Constants::AMOUNT                   => $this->getFormattedAmount($row['payment']['amount']),
                    Constants::REFUND_AMOUNT            => '',
                    Constants::SERVICE_CHARGES_AMOUNT   => '0',
                    Constants::DEBIT_ACCOUNT_ID         => $row['gateway']['bank_account_number'],
                    Constants::STATUS                   => 'COM',
                    Constants::MERCHANT_ACCOUNT_NUMBER  => '2121201131751367',  //TODO: TO BE DISCUSSED
                    Constants::MERCHANT_URL             => 'www.razorpay.com',
                ];
            }
        }

        $formattedData = array_merge($formattedData, $claimsData, $refundsData);

        return $formattedData;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return strtr(self::FILE_NAME, ['{$date}' => $date]);
    }

    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID];
        }

        return $data['gateway']['data']['bank_payment_id'];
    }
}
