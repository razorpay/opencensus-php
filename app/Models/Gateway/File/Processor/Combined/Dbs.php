<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;
use Carbon\Carbon;

use RZP\Encryption;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use RZP\Models\Gateway\File\Type;
use RZP\Encryption\PGPEncryption;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Reconciliator\NetbankingDbs\Constants;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Services\Beam\Constants as BeamConstants;

class Dbs extends Base
{
    const FILE_TYPE               = FileStore\Type::DBS_NETBANKING_COMBINED;
    const FILE_TYPE_UNENCRYPTED   = FileStore\Type::DBS_NETBANKING_COMBINED_UNENCRYPTED;
    const EXTENSION               = FileStore\Format::XLSX;
    const FILE_NAME               = 'HCODI01.Razorpay_';
    const BEAM_FILE_TYPE          = 'combined';

    private $refunds_file_based = [];

    protected function formatDataForMail(array $data)
    {

        $amount = [
            'claims'            => 0,
            'refunds'           => 0,
            'refunds_manual'    => 0,
            'total'             => 0,
        ];

        $count = [
            'claims'            => 0,
            'refunds'           => 0,
            'refunds_manual'    => 0,
            'total'             => 0,
        ];

        $claimsFile = [];
        $refundsFile = [];
        $summaryFile = [];

        if (isset($data['refunds']) === true)
        {
            $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

            foreach ($data['refunds'] as $row)
            {
                if ($refundFileProcessor->getStatus($row) === 'Success')
                {
                    $amount['refunds'] += $row['refund']['amount'];
                    $count['refunds'] += 1;
                }

                else
                {
                    $amount['refunds_manual'] += $row['refund']['amount'];
                    $count['refunds_manual'] += 1;
                }
            }
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'] - $amount['refunds_manual'];

        $count['total'] = $count['refunds'] + $count['refunds_manual'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('d.m.Y');

        $summaryFile = $this->getFileData(FileStore\Type::DBS_NETBANKING_COMBINED_UNENCRYPTED);

        $amount['total'] = $this->getFormattedAmount($amount['total']);

        $amount['refunds'] = $this->getFormattedAmount($amount['refunds']);

        $amount['refunds_manual'] = $this->getFormattedAmount($amount['refunds_manual']);

        $amount['claims'] = $this->getFormattedAmount($amount['claims']);

        $config = $this->app['config']->get('nodal.axis');

        $accountDetails = [
            'bankName'      => 'Axis Bank Ltd',
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $config['ifsc_code'],
        ];

        return [
            'bankName'    => 'Dbs',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
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

    protected function getFileLocation(string $type)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, $type)
            ->first();

        $fileLocation = $file->getLocation();

        return $fileLocation;
    }

    protected function createCombinedFile($data)
    {
        try
        {
            $fileData = $this->formatDataForFile($data);

            $creator = new FileStore\Creator;

            $creator1 = new FileStore\Creator;

            $config = $this->config['gateway.netbanking_dbs'];

            $pgpConfig = [
                PGPEncryption::PUBLIC_KEY  => trim(str_replace('\n', "\n", $config['files_encryption_key']))
            ];

            $creator->extension(self::EXTENSION)
                ->content($fileData)
                ->name(self::FILE_NAME . Carbon::now(Timezone::IST)->format('dmY_His'))
                ->store(FileStore\Store::S3)
                ->type(self::FILE_TYPE)
                ->entity($this->gatewayFile)
                ->encrypt(Encryption\Type::PGP_ENCRYPTION, $pgpConfig)
                ->save();

            $file = $creator->getFileInstance();

            $creator->name(self::FILE_NAME . Carbon::now(Timezone::IST)->format('dmY_His') .'.xlsx')
                ->extension(FileStore\Format::PGP)
                ->save();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $creator1->extension(self::EXTENSION)
                ->content($fileData)
                ->name(self::FILE_NAME . Carbon::now(Timezone::IST)->format('dmY_His'))
                ->store(FileStore\Store::S3)
                ->type(self::FILE_TYPE_UNENCRYPTED)
                ->entity($this->gatewayFile)
                ->headers(false)
                ->save();

            $file = $creator1->getFileInstance();

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
            Constants::MERCHANT_ID              => Constants::MERCHANT_ID,
            Constants::MERCHANT_ORDER_ID        => Constants::MERCHANT_ORDER_ID,
            Constants::BANK_REF_NO              => Constants::BANK_REF_NO,
            Constants::TXN_AMOUNT               => Constants::TXN_AMOUNT,
            Constants::ORDER_TYPE               => Constants::ORDER_TYPE,
            Constants::STATUS                   => Constants::STATUS,
            Constants::TXN_DATE                 => Constants::TXN_DATE,
            Constants::PAYMENT_ID               => Constants::PAYMENT_ID,
            Constants::PAYMENT_BANK_REF_NO      => Constants::PAYMENT_BANK_REF_NO,
        ];

        $refundsData = [];
        $claimsData = [];
        $count = 0;

        if (isset($data['refunds']) === true)
        {
            $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

            foreach ($data['refunds'] as $row)
            {
                $date = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

                $refundsData[] = [
                    Constants::MERCHANT_ID              => 'RazorPay',
                    Constants::MERCHANT_ORDER_ID        => $row['refund']['id'],
                    Constants::BANK_REF_NO              => $row['refund']['reference1'],
                    Constants::TXN_AMOUNT               => $this->getFormattedAmount($row['refund']['amount']),
                    Constants::ORDER_TYPE               => $refundFileProcessor->getOrderType($row),
                    Constants::STATUS                   => $refundFileProcessor->getStatus($row),
                    Constants::TXN_DATE                 => $date,
                    Constants::PAYMENT_ID               => $row['payment']['id'],
                    Constants::PAYMENT_BANK_REF_NO      => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                ];

                if ($refundFileProcessor->getStatus($row) !== 'Success')
                {
                    $this->refunds_file_based[] = $row;
                }

                $count += 1;
            }
        }

        if (isset($data['claims']) === true)
        {
            foreach ($data['claims'] as $row)
            {
                $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

                $claimsData[] = [
                    Constants::MERCHANT_ID              => 'RazorPay',
                    Constants::MERCHANT_ORDER_ID        => $row['payment']['id'],
                    Constants::BANK_REF_NO              => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                    Constants::TXN_AMOUNT               => $this->getFormattedAmount($row['payment']['amount']),
                    Constants::ORDER_TYPE               => 'Order',
                    Constants::STATUS                   => 'Success',
                    Constants::TXN_DATE                 => $date,
                    Constants::PAYMENT_ID               => '',
                    Constants::PAYMENT_BANK_REF_NO      => '',
                ];

                $count += 1;
            }
        }

        $footer[] = [
            Constants::MERCHANT_ID                     => 'Total no. of txns: ' . $count,
        ];

        $formattedData = array_merge($formattedData, $claimsData, $refundsData, $footer);

        return $formattedData;
    }

    public function sendFile($data)
    {
        try
        {
            $fileInfo = [];

            $this->createCombinedFile($data);

            $fileInfo[] = $this->getFileLocation(FileStore\Type::DBS_NETBANKING_COMBINED);

            $bucketConfig = $this->getBucketConfig();

            $beamData =  [
                Service::BEAM_PUSH_FILES         => $fileInfo,
                Service::BEAM_PUSH_JOBNAME       => BeamConstants::DBS_NB_COMBINED_FILE_JOB_NAME,
                Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            ];

            // In seconds
            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'tech_alerts',
                'filetype'  => self::BEAM_FILE_TYPE,
                'subject'   => 'DBS Combined File send failure',
                'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SETTLEMENT_ALERTS]
            ];

            $this->app['beam']->beamPush($beamData, $timelines, $mailInfo);

            $mailData = $this->formatDataForMail($data);

            $dailyFileMail = new DailyFileMail($mailData);

            Mail::send($dailyFileMail);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);

            $this->reconcileNetbankingRefunds($this->refunds_file_based ?? []);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::INFO,
                TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id' => $this->gatewayFile->getId()
                ]);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
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
}
