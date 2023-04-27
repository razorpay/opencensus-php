<?php

namespace RZP\Models\Emi\Banks\Yesb;

use Config;
use Carbon\Carbon;
use RZP\Models\Emi;
use RZP\Constants\Timezone;
use RZP\Models\Card;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Models\Emi\Banks\Base;
use RZP\Encryption\PGPEncryption;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Trace\TraceCode;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Yesb_Emi_File';

    protected $emailIdsToSendTo = ['yesbcards.emi@razorpay.com'];

    protected $bankName  = 'Yesb';

    protected $type = FileStore\Type::YES_EMI_FILE_SFTP;

    protected $totalAmount;

    protected $totalTransactions;

    public function __construct()
    {
        parent::__construct();

        $this->shouldCompress = false;

        $this->shouldEncrypt = true;

        $this->transferMode = Base\EmiMode::SFTP;
    }

    protected function getEmiData($input)
    {
        $data = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        foreach ($input as $emiPayment)
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
                    'bank'       => $this->bankName,
                ]
            );
        }

        $this->totalTransactions = $totalTransactions;

        $this->totalAmount = $this->getFormattedAmount($totalAmount);

        return $data;
    }

    protected function generateEmiFile(array $emiData, array $metadata = [])
    {
        $fileData = null;

        // for sftp file is uploaded to
        if ($this->transferMode === Base\EmiMode::SFTP)
        {
            $metadata = $this->getH2HMetadata();
        }
        else
        {
            $this->type = FileStore\Type::YES_EMI_FILE_MAIL;
        }

        $fileData = parent::generateEmiFile($emiData, $metadata);

        return $fileData;
    }

    protected function getFileToWriteName(array $data)
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        static::$fileToWriteName = 'MEMI_MANI_' . $date;

        $filePath = '';

        // for sftp we put the file in a H2H path
        if ($this->transferMode === Base\EmiMode::SFTP)
        {
           $filePath = 'yesbank/outgoing/';
        }

        return $filePath . static::$fileToWriteName . '.' . FileStore\Format::XLSX;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('j/n/Y');
    }

    protected function getEncryptionParams()
    {
        $publicKey = Config::get('applications.emi.yesb_encryption_key');

        $publicKey = trim(str_replace('\n', "\n", $publicKey));

        return [PGPEncryption::PUBLIC_KEY => $publicKey];
    }

    protected function sendEmiFile(array $fileData, $mailData = null)
    {
        $body = 'Emi File Uploaded <br />';
        $body = $body . 'File Name : ' . static::$fileToWriteName . '<br />';
        $body = $body . 'Total Amount : ' . $this->totalAmount . '<br />';
        $body = $body . 'Transactions Count : ' . $this->totalTransactions;

        $mailData = ['body'  =>  $body];

        if ($this->transferMode === Base\EmiMode::SFTP)
        {
            $this->pushEmiFileToBeam(BeamConstants::YESBANK_EMI_FILE_JOB_NAME);

            $fileData = [];
        }

        parent::sendEmiFile($fileData, $mailData);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10004',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }
}
