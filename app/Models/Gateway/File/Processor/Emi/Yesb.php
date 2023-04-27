<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Trace\TraceCode;
use Str;
use Mail;
use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\Emi;
use RZP\Mail\Emi as EmiMail;
use RZP\Encryption\PGPEncryption;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Payment;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service;

class Yesb extends Base
{
    const BANK_CODE   = IFSC::YESB;

    protected static $fileToWriteName = 'Yesb_Emi_File';

    protected $emailIdsToSendTo = ['yesbcards.emi@razorpay.com'];

    protected $bankName  = 'Yesb';

    const FILE_TYPE = FileStore\Type::YES_EMI_FILE_SFTP;

    const COMPRESSION_REQUIRED = false;

    const BEAM_FILE_TYPE = 'emi';

    protected $totalAmount;

    protected $totalTransactions;

    protected $shouldEncrypt = true;

    protected function formatDataForFile($input)
    {
        $data = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        foreach ($input['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $emiTenure = $emiPlan['duration'];

            $emiPercent = $emiPlan['rate']/100;

            $principalAmount = $emiPayment->getAmount()/100;

            $totalAmount = $totalAmount + $principalAmount;

            $totalTransactions++;

            $subventionAmount = '0.00';

            if ($emiPlan->getSubvention() === Emi\Subvention::MERCHANT)
            {
                $merchantPayback = $emiPlan->getMerchantPayback()/100;

                $amount = ($principalAmount * $merchantPayback)/100;

                $subventionAmount = $this->getFormattedAmount($amount);
            }

            $emiAmount = $this->getFormattedAmount($this->getEmiAmount($principalAmount, $emiPercent, $emiTenure));

            $notApplicable = 'NA';

            $acquirer = 'NA';

            if (empty($emiPayment->terminal->getGatewayAcquirer()) === false)
            {
                $acquirer = Payment\Gateway::getAcquirerName($emiPayment->terminal->getGatewayAcquirer());
            }

            $data[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Tokenised Card'               => str_repeat('*', 12) . $emiPayment->card->getLast4(),
                'Issuer'                       => 'YES',
                'RRN'                          => $notApplicable,
                'Auth Code'                    => $this->getAuthCode($emiPayment),
                'Tx Amount'                    => $this->getFormattedAmount($principalAmount),
                'EMI_Offer'                    => $emiTenure.' Months',
                'Manufacturer'                 => $notApplicable,
                'Merchant Name'                => $emiPayment->merchant->getName(),
                'Address1'                     => $notApplicable,
                'Store City'                   => $notApplicable,
                'Store State'                  => $notApplicable,
                'Acquirer'                     => $acquirer,
                'MID'                          => $notApplicable,
                'TID'                          => $notApplicable,
                'Tx Time'                      => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Settlement Time'              => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Customer Processing Fee'      => '0.00%',
                'Customer Processing Amt'      => '0.00',
                'Subvention payable to Issuer' => '0.0%',
                'Subvention Amount (Rs.)'      => $subventionAmount,
                'Interest Rate'                => $emiPercent.'%',
                'Tx Status'                    => 'Settled',
                'Status'                       => 'online',
                'Product Category'             => $notApplicable,
                'Product Sub-Category 1'       => $notApplicable,
                'Product Sub-Category 2'       => $notApplicable,
                'Model Name'                   => $notApplicable,
                'Card Hash'                    => $notApplicable,
                'EMI Amount'                   => $emiAmount,
                'Loan Amount'                  => $notApplicable,
                'Discount / Cashback %'        => $notApplicable,
                'Discount / Cashback Amount'   => $notApplicable,
                'Is New Model'                 => $notApplicable,
                'Additional Cashback'          => $notApplicable,
                'Reward Point'                 => $notApplicable,
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::BANK_CODE,
                ]
            );
        }

        $this->totalTransactions = $totalTransactions;

        $this->totalAmount = $this->getFormattedAmount($totalAmount);

        return $data;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('j/n/Y');
    }

    protected function getFileToWriteName()
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        static::$fileToWriteName = 'MEMI_MANI_' . $date;

        $filePath = 'yesbank/outgoing/';

        return $filePath . static::$fileToWriteName . '.' . FileStore\Format::XLSX;
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        return $bucketConfig;
    }

    protected function sendEmiFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data =  [
            Service::BEAM_PUSH_FILES         => $fileInfo,
            Service::BEAM_PUSH_JOBNAME       => BeamConstants::YESBANK_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'tech_alerts',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'YESB EMI - File Send failure',
            'recipient' => [
                Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                Constants::MAIL_ADDRESSES[Constants::FINOPS],
                Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
            ],
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);

        $this->sendConfirmationMail();
    }

    protected function sendEmiPassword($data)
    {

    }

    protected function sendConfirmationMail()
    {
        $recipients = $this->gatewayFile->getRecipients();

        $body = 'Emi File Uploaded <br />';
        $body = $body . 'File Name : ' . static::$fileToWriteName . '<br />';
        $body = $body . 'Total Amount : ' . $this->totalAmount . '<br />';
        $body = $body . 'Transactions Count : ' . $this->totalTransactions;

        $data = [
            'body' => $body
        ];

        $emiFileMail = new EmiMail\File(
            'Yesb',
            [],
            $recipients,
            $data
        );

        Mail::queue($emiFileMail);
    }

    protected function getEncryptionParams()
    {
        $publicKey = Config::get('applications.emi.yesb_encryption_key');

        $publicKey = trim(str_replace('\n', "\n", $publicKey));

        return [PGPEncryption::PUBLIC_KEY => $publicKey];
    }

}
