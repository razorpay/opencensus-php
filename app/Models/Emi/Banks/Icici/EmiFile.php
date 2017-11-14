<?php

namespace RZP\Models\Emi\Banks\Icici;

use Carbon\Carbon;
use Mail;
use RZP\Constants\Timezone;
use RZP\Models\Emi;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Icici_Emi_File';

    protected $emailIdsToSendTo = ['icicicards.emi@razorpay.com'];

    protected $bankName  = 'Icici';

    protected $type = FileStore\Type::ICICI_EMI_FILE_SFTP;

    protected $totalAmount;

    protected $totalTransactions;

    public function __construct()
    {
        parent::__construct();

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

            $principalAmount = $emiPayment->getAmount()/100;

            $totalAmount = $totalAmount + $principalAmount;

            $totalTransactions++;

            $merchantPayback = 'NA';

            $subventionAmount = 'NA';

            $acquirer = 'NA';

            if ($emiPlan->getSubvention() === Emi\Subvention::MERCHANT)
            {
                $merchantPayback = $emiPlan->getMerchantPayback()/100;

                $amount = ($principalAmount * $merchantPayback)/100;

                $subventionAmount = number_format((float)$amount, 2, '.', '');
            }

            if (empty($emiPayment->terminal->getGatewayAcquirer()) === false)
            {
                $acquirer = Payment\Gateway::getAcquirerName($emiPayment->terminal->getGatewayAcquirer());
            }

            $rate = $emiPlan->getRate()/100;

            $tenure = $emiPlan->getDuration();

            $issuerPlanId = $emiPlan->getIssuerPlanId();

            $data[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Transaction Date/Time'        => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                'Card No.'                     => $this->getCardNumber($emiPayment->card),
                'Amount'                       => $principalAmount,
                'Auth Code/ Approval Code'     => $this->getAuthCode($emiPayment),
                'Scheme Code'                  => $issuerPlanId,
                'Tenure'                       => $tenure,
                'Interest Rate'                => $rate,
                'Merchant Subvention'          => $merchantPayback,
                'Customer Subvention'          => 'NA',
                'Discount/ Cashback Amount'    => 'NA',
                'Discount/Cashback(%)'         => 'NA',
                'Cashback (Y/N)'               => 'N',
                'Manufacturer'                 => 'NA',
                'Merchant Name'                => $emiPayment->merchant->getName(),
                'Pinelabs Merchant Name'       => 'NA',
                'Issuer'                       => 'ICICI Bank',
                'Acquirer'                     => $acquirer,
                'Settlement Time'              => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Subvention Payable to Issuer' => 'NA',
                'Subvention Amount (Rs.)'      => $subventionAmount,
                'Addition Cashback'            => 'NA',
            ];
        }

        $this->totalTransactions = $totalTransactions;

        $this->totalAmount = $totalAmount;

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
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
            $this->type = FileStore\Type::ICICI_EMI_FILE_MAIL;
        }

        $fileData = parent::generateEmiFile($emiData, $metadata);

        return $fileData;
    }

    protected function generateEmiFilePassword()
    {
        $monthYear = Carbon::now(Timezone::IST)->format('mY');
        
        return "razorpay" . $monthYear;
    }

    protected function getFileToWriteName(array $data)
    {
        $count = $this->totalTransactions;

        $date = Carbon::now(Timezone::IST)->format('dmY');

        static::$fileToWriteName = 'Razorpay_ICICIEMI_' . $date . '_' . $count;

        $filePath = '';

        // for sftp we put the file in a H2H path
        if ($this->transferMode === Base\EmiMode::SFTP)
        {
           $filePath = 'icici/outgoing/';
        }

        return $filePath . static::$fileToWriteName;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    protected function sendEmiFile(array $fileData, $mailData = null)
    {
        $body = 'Emi File Uploaded <br />';
        $body = $body . 'File Name : ' . static::$fileToWriteName . '<br />';
        $body = $body . 'Password : ' . $this->emiFilePassword . '<br />';
        $body = $body . 'Total Amount : ' . $this->totalAmount . '<br />';
        $body = $body . 'Transactions Count : ' . $this->totalTransactions;

        $mailData = ['body'  =>  $body];

        if ($this->transferMode === Base\EmiMode::SFTP)
        {
            $fileData = [];
        }

        parent::sendEmiFile($fileData, $mailData);
    }
}
