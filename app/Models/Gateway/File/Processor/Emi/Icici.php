<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Str;
use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Mail\Base\Constants;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\Emi;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Payment;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;

class Icici extends Base
{
    const BANK_CODE   = IFSC::ICIC;

    const FILE_TYPE   = FileStore\Type::ICICI_EMI_FILE_SFTP;

    protected static $fileToWriteName = 'Icici_Emi_File';

    const BEAM_FILE_TYPE    = 'emi';

    protected $emailIdsToSendTo = ['icicicards.emi@razorpay.com'];

    protected $bankName  = 'Icici';

    protected $totalAmount;

    protected $emiFilePassword;

    protected $totalTransactions;

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::fetchEntities().
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();
        $gateway = Payment\Gateway::AMEX;
        $acquirer = Payment\Gateway::AMEX;

        $emiPaymentsForBank = $this->repo
            ->payment
            ->fetchEmiPaymentsWithCardTerminalsBetween(
                $begin,
                $end,
                static::BANK_CODE);

        $emiPaymentsForAmexGateway = $this->repo
            ->payment
            ->fetchEmiPaymentsWithGatewayAndAcquirerBetween(
                $begin,
                $end,
                static::BANK_CODE,
                Payment\Gateway::AMEX,
                $acquirer);

        $emiPaymentsForBank->merge($emiPaymentsForAmexGateway);

        $emiPaymentsForMpgsGateway = $this->repo
            ->payment
            ->fetchEmiPaymentsWithGatewayAndAcquirerBetween(
                $begin,
                $end,
                static::BANK_CODE,
                Payment\Gateway::MPGS,
                $acquirer);

        return $emiPaymentsForBank->merge($emiPaymentsForMpgsGateway);
    }

    protected function getFileToWriteName()
    {
        $count = $this->totalTransactions;

        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        static::$fileToWriteName = 'ICICI_CC_EMI_Razorpay_' . $date . '_TID';

        $filePath = '';

        $filePath = 'icici/outgoing/';

        return $filePath . static::$fileToWriteName;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
    }

    protected function formatDataForFile($input)
    {
        $data = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        foreach ($input['items'] as $emiPayment)
        {
                $emiPlan = $emiPayment->emiPlan;

                $principalAmount = $emiPayment->getAmount() / 100;

                $totalAmount = $totalAmount + $principalAmount;

                $totalTransactions++;

                $subventionAmount = 'NA';

                $acquirer = 'NA';

                if ($emiPlan->getSubvention() === Emi\Subvention::MERCHANT) {
                    $merchantPayback = $emiPlan->getMerchantPayback() / 100;

                    $amount = ($principalAmount * $merchantPayback) / 100;

                    $subventionAmount = number_format((float)$amount, 2, '.', '');
                }

                if (empty($emiPayment->terminal->getGatewayAcquirer()) === false) {
                    $acquirer = Payment\Gateway::getAcquirerName($emiPayment->terminal->getGatewayAcquirer());
                }

                $rate = $emiPlan->getRate() / 100;

                $tenure = $emiPlan->getDuration();

                $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

                $issuerPlanId = $emiPlan->getIssuerPlanId();

                $card = $emiPayment->card;

                if (isset($emiPayment->card->trivia) && isset($emiPayment->token))
                {
                    $card = $emiPayment->token->card;
                }

                $gateway = $emiPayment->getGateway();

                $tid = $emiPayment->terminal->getGatewayTerminalId();

                $mid = $emiPayment->terminal->getGatewayMerchantId();

                $payment_acquirer = $emiPayment->terminal->getGatewayAcquirer();

                if($gateway === 'hitachi' && $payment_acquirer === 'ratn')
                {
                    $finalTid = $tid;
                    $finalMid = $mid;
                }
                else if($gateway === 'paysecure' && $payment_acquirer === 'ratn')
                {
                    $finalTid = $tid;
                    $finalMid = $mid;
                }
                else if($gateway === 'fulcrum' && $payment_acquirer === 'ratn')
                {
                    $finalTid = strtoupper($tid);
                    $finalMid = $mid;
                }
                else if($gateway === 'hdfc' && $payment_acquirer === 'hdfc')
                {
                    $finalTid = $tid;
                    $finalMid = $mid;
                }
                else if($gateway === 'isg' && $payment_acquirer === 'kotak')
                {
                    $finalTid = $tid;
                    $finalMid = $mid;
                }
                else if($gateway === 'mpgs' && $payment_acquirer === 'hdfc')
                {
                    $finalTid = $mid;
                    $finalMid = $mid;
                }
                else if($gateway === 'mpgs' && $payment_acquirer === 'icic')
                {
                    $finalTid = $mid;
                    $finalMid = $mid;
                }
                else if($gateway === 'first_data' && $payment_acquirer === 'icic')
                {
                    $finalTid = substr($mid,2);
                    $finalMid = $mid;
                }
                else if($gateway === 'cybersource' && $payment_acquirer === 'hdfc')
                {
                    if(substr($mid,0,5) === "hdfc_")
                    {
                        $finalTid = substr($mid,5);
                    }
                    else
                    {
                        $finalTid = $mid;
                    }
                    $finalMid = $mid;
                }
                else if($gateway === 'card_fss' && $payment_acquirer === 'sbin')
                {
                    $finalTid = $mid;
                    $finalMid = $mid;
                }
                else if($gateway === 'amex' && $payment_acquirer === 'amex')
                {
                    $finalTid = $tid;
                    $finalMid = $mid;
                }
                else if($gateway === 'mpgs' && $payment_acquirer === 'amex')
                {
                    $finalTid = $mid;
                    $finalMid = $mid;
                }
                else
                {
                    $this->trace->info(TraceCode::PAYMENT_WITH_INCORRECT_TID, [
                        'Payment with incorrect TID'=> $emiPayment->getId(),
                    ]);

                    continue;
                }

                $data[] = [
                    'EMI ID' => $emiPayment->getId(),
                    'Tx Time' => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                    'Card PAN' => $card->getLast4(),
                    'Amount' => $this->getFormattedAmount($principalAmount),
                    'Auth Code' => str_pad($this->getAuthCode($emiPayment), 6, '0', STR_PAD_LEFT),
                    'Scheme Code' => substr($issuerPlanId, 0, 4) . 'P199' . substr($issuerPlanId, -2),
                    'MID' => $finalMid,
                    'TID' => $finalTid,
                    'Discount/ Cashback Amount' => 'NA',
                    'Tenure' => $tenure,
                    'RRN' => '',
                    'Manufacturer' => 'Bank EMI',
                    'Merchant Name' => $emiPayment->merchant->getName(),
                    '<Aggregator> Merchant  Name' => $emiPayment->merchant->getName(),
                    'Tx Status' => 'Settled',
                    'Status' => 'Ecom',
                    'Description' => '',
                    'Issuer' => 'ICICI Bank',
                    'Address1' => '',
                    'Store City' => '',
                    'Store State' => '',
                    'Acquirer' => $acquirer,
                    'Settlement Time' => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                    'Subvention Payable to Issuer' => 'NA',
                    'Subvention Amount (Rs.)' => $subventionAmount,
                    'Interest Rate' => $rate,
                    'Customer Processing Fee' => '',
                    'Customer Processing Amount (Rs.)' => '199',
                    'Product Category' => '',
                    'Product Sub-Category 1' => '',
                    'Product Sub-Category 2' => '',
                    'Model Name' => '',
                    'Card Hash' => '',
                    'EMI Amount' => $this->getFormattedAmount($emiAmount),
                    'Loan Amount' => $this->getFormattedAmount($principalAmount),
                    'Discount / Cashback %' => 'NA',
                    'Is New Model' => '',
                    'Additional Cashback' => '',
                    'Reward Point' => '',
                ];

                $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                    [
                        'payment_id' => $emiPayment->getId(),
                        'bank'       => static::BANK_CODE,
                    ]
                );
            }

        $this->totalTransactions = $totalTransactions;

        $this->totalAmount = $totalAmount;

        return $data;
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
            Service::BEAM_PUSH_JOBNAME       => BeamConstants::ICIC_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'tech_alerts',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'ICICI EMI - File Send failure',
            'recipient' => [
                Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                Constants::MAIL_ADDRESSES[Constants::FINOPS],
                Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
            ],
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);

        $this->sendConfirmationMail();
    }


    protected function generateEmiFilePassword()
    {
        return $this->emiFilePassword = Str::random(15);
    }

    protected function sendConfirmationMail()
    {
        $recipients = $this->gatewayFile->getRecipients();

        $body = 'Emi File Uploaded <br />';
        $body = $body . 'File Name : ' . static::$fileToWriteName . '<br />';
        $body = $body . 'Password : ' . $this->emiFilePassword . '<br />';
        $body = $body . 'Total Amount : ' . $this->totalAmount . '<br />';
        $body = $body . 'Transactions Count : ' . $this->totalTransactions;

        $data = [
            'body' => $body
        ];

        $emiFileMail = new EmiMail\File(
            'ICIC',
            [],
            $recipients,
            $data
        );

        Mail::queue($emiFileMail);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount, 2,'.', '');
    }

}
