<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;

class Idfc extends Base
{
    const FILE_TYPE               = FileStore\Type::IDFC_NETBANKING_SUMMARY;
    const EXTENSION               = FileStore\Format::XLS;
    static $filename              = 'Claim Summary File-RAZORPAY-IDFC';
    const BASE_STORAGE_DIRECTORY  = 'Idfc/Summary/Netbanking/';

    protected $config;

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
        $refundsFile = [];
        $summaryFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::IDFC_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::IDFC_NETBANKING_CLAIMS);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('Ymd');

        $this->createSummaryFile($amount, $count);

        $summaryFile = $this->getFileData(FileStore\Type::IDFC_NETBANKING_SUMMARY);

        return [
            'bankName'    => 'Idfc',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'summaryFile' => $summaryFile,
            'emails'      => $this->gatewayFile->getRecipients()
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

    protected function createSummaryFile($amount, $count)
    {
        $data = [
            'amount' => $amount,
            'count' => $count
        ];

        try
        {
            $fileData = $this->formatDataForFile($data);

            $creator = new FileStore\Creator;

            $fileName = $this->getFileNameToWriteWithoutExtension();

            $creator->extension(self::EXTENSION)
                ->content($fileData)
                ->name($fileName)
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
        $formattedData = $this->getAdditionalData();

        $transactionDate = $this->getTransactionDate();

        $date = Carbon::now(Timezone::IST)->format('d-M-y');

        $formattedData[] = [
            'Sr.No.',
            'Bank MerchantID',
            'Razorpay MerchantID',
            'City',
            'TransactionDate',
            'Number of Transaction',
            'Transaction Amount (Rs)',
            'Date & Time of Refund Processing:',
            'Number of Refunds',
            'Refund Amount (Rs)',
            'NET Transaction Amount (Rs)'
        ];

        $formattedData[]  = [
            '1',
            $this->getMerchantId(),
            $this->getMerchantId(),
            'Bangalore',
            $transactionDate,
            $data['count']['claims'],
            $this->formatAmount($data['amount']['claims']),
            $date,
            $data['count']['refunds'],
            $this->formatAmount($data['amount']['refunds']),
            $this->formatAmount($data['amount']['claims'] - $data['amount']['refunds']),
        ];

        $formattedData[] = [
            'Net Amt to be credited to Nodal A/c ' ,
            $this->formatAmount($data['amount']['claims'] - $data['amount']['refunds'])
        ];

        return $formattedData;
    }

    protected function getNodalAccountDetails()
    {
        $this->config = $this->app['config'];
        $nodalAccount = $this->config->get('nodal.axis');

        $accountDetails = [
            'bankName'      => 'Axis Bank Ltd',
            'accountNumber' => $nodalAccount['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $nodalAccount['ifsc_code'],
        ];

        $nodalAccountDetails = $accountDetails['bankName'] . ',' .
            'A/C No. - ' . $accountDetails['accountNumber'] . ',' .
            'A/C Name - ' . $accountDetails['accountName'] . ',' .
            'IFSC Code - ' . $accountDetails['ifsc'];

        return $nodalAccountDetails;
    }

    protected function getAdditionalData()
    {
        $nodalAccountDetails = $this->getNodalAccountDetails();

        $date = Carbon::now(Timezone::IST)->format('d-M-y');

        $transactionDate = $this->getTransactionDate();

        $additionalData[] = ['Type of File:', 'Reconciliation Summary Report'];
        $additionalData[] = ['Date & Time of Processing:', $date];
        $additionalData[] = ['PGI File Name :', $transactionDate . '.txt'];
        $additionalData[] = ['Nodal Bank Account Detail', $nodalAccountDetails];

        return $additionalData;
    }

    protected function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    protected function getTestMerchantId()
    {
        $code = null;

        if (isset($this->config['gateway.netbanking_idfc.test_merchant_id']))
        {
            $code = $this->config['gateway.netbanking_idfc.test_merchant_id'];
        }

        return $code;
    }

    protected function getLiveMerchantId()
    {
        return $this->config['gateway.netbanking_idfc.live_merchant_id'];
    }

    protected function getTransactionDate()
    {
        $transactionDate = Carbon::yesterday(Timezone::IST)->format('dmY');

        $transactionPrevDate = Carbon::yesterday(Timezone::IST)->subDay()->format('dmY');

        return $transactionPrevDate . '-' . $transactionDate;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    protected function getFileNameToWriteWithoutExtension()
    {
        return static::BASE_STORAGE_DIRECTORY . static::$filename;
    }
}
