<?php

namespace RZP\Models\Emi\Banks\Icici;

use Carbon\Carbon;
use Mail;
use RZP\Constants\Timezone;
use RZP\Models\Emi;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Trace\TraceCode;

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

        // This would be required in the future, so keeping it as a comment
        // $cpsData = $this->fetchDataFromCardPaymentService($input);

        foreach ($input as $emiPayment)
        {
            /*
            // If CPS does not have the for this payment, don't send the file
            if (empty($cpsData[$emiPayment['id']]) === true)
            {
                throw new Exception\LogicException(
                    'Authorization Code cannot be empty.', null,
                    [
                        'payment_id' => $emiPayment->getPublicId(),
                        'cps_data'   => $cpsData,
                    ]);
            }
            */

            // To be added in the future
            // $authData = $cpsData[$emiPayment['id']];

            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount()/100;

            $totalAmount = $totalAmount + $principalAmount;

            $totalTransactions++;

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

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

            $issuerPlanId = $emiPlan->getIssuerPlanId();

            $data[] = [
                'EMI ID'                           => $emiPayment->getId(),
                'Tx Time'                          => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                'Card PAN'                         => $this->getCardNumber($emiPayment->card,$emiPayment->getGateway()),
                'Amount'                           => $principalAmount,
                'Auth Code'                        => $this->getAuthCode($emiPayment),
                'Scheme Code'                      => substr($issuerPlanId, 0, 4).'P199'.substr($issuerPlanId, -2),
                'MID'                              => '',
                'TID'                              => '',
                'Discount/ Cashback Amount'        => 'NA',
                'Tenure'                           => $tenure,
                'RRN'                              => '',
                'Manufacturer'                     => 'Bank EMI',
                'Merchant Name'                    => $emiPayment->merchant->getName(),
                '<Aggregator> Merchant  Name'      => $emiPayment->merchant->getName(),
                'Tx Status'                        => 'Settled',
                'Status'                           => 'Ecom',
                'Description'                      => '',
                'Issuer'                           => 'ICICI Bank',
                'Address1'                         => '',
                'Store City'                       => '',
                'Store State'                      => '',
                'Acquirer'                         => $acquirer,
                'Settlement Time'                  => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Subvention Payable to Issuer'     => 'NA',
                'Subvention Amount (Rs.)'          => $subventionAmount,
                'Interest Rate'                    => $rate,
                'Customer Processing Fee'          => '',
                'Customer Processing Amount (Rs.)' => '199',
                'Product Category'                 => '',
                'Product Sub-Category 1'           => '',
                'Product Sub-Category 2'           => '',
                'Model Name'                       => '',
                'Card Hash'                        => '',
                'EMI Amount'                       => $emiAmount,
                'Loan Amount'                      => $principalAmount,
                'Discount / Cashback %'            => 'NA',
                'Is New Model'                     => '',
                'Additional Cashback'              => '',
                'Reward Point'                     => '',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => $this->bankName,
                ]
            );
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
         if($this->mode == "test")
         {
            $monthYear = Carbon::now(Timezone::IST)->format('mY');
            return "razorpay" . $monthYear;
         }

        return str_random(15);
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
            // Push this file to Beam
            $this->pushEmiFileToBeam(BeamConstants::ICIC_EMI_FILE_JOB_NAME);

            $fileData = [];
        }

        parent::sendEmiFile($fileData, $mailData);
    }
}
