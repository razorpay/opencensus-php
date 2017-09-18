<?php

namespace RZP\Models\Emi\Banks\Yesb;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Card;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Models\Emi\Banks\Base;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Yesb_Emi_File';

    protected $emailIdsToSendTo = ['yesb.emi@razorpay.com'];

    protected $bankName  = 'Yesb';

    protected $type = FileStore\Type::YES_EMI_FILE_SFTP;

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

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $emiTenure = $emiPlan['duration'];

            $emiPercent = $emiPlan['rate']/100;

            $notApplicable = 'NA';

            $acquirer = 'NA';

            if (empty($emiPayment->terminal->getGatewayAcquirer()) === false)
            {
                $acquirer = Payment\Gateway::getAcquirerName($emiPayment->terminal->getGatewayAcquirer());
            }

            $data[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Card Pan'                     => $this->getCardNumber($emiPayment->card),
                'Issuer'                       => 'YES',
                'RRN'                          => $notApplicable,
                'Auth Code'                    => $this->getAuthCode($emiPayment),
                'Tx Amount'                    => $emiPayment->getAmount()/ 100,
                'EMI_Offer'                    => $emiTenure.' Months',
                'Manufacturer'                 => $notApplicable,
                'Merchant Name'                => $notApplicable,
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
                'Subvention Amount (Rs.)'      => '0.00',
                'Interest Rate'                => $emiPercent.'%',
                'Tx Status'                    => 'Settled',
                'Status'                       => 'online',
                'Product Category'             => $notApplicable,
                'Product Sub-Category 1'       => $notApplicable,
                'Product Sub-Category 2'       => $notApplicable,
                'Model Name'                   => $notApplicable,
                'Card Hash'                    => $notApplicable,
                'EMI Amount'                   => $notApplicable,
                'Loan Amount'                  => $notApplicable,
                'Discount / Cashback %'        => $notApplicable,
                'Discount / Cashback Amount'   => $notApplicable,
                'Is New Model'                 => $notApplicable,
                'Additional Cashback'          => $notApplicable,
                'Reward Point'                 => $notApplicable,
            ];
        }

        return $data;
    }

    protected function generateEmiFile(array $emiData, array $metadata = [])
    {
        $fileData = null;

        if ($this->transferMode === Base\EmiMode::MAIL)
        {
            $this->type = FileStore\Type::YES_EMI_FILE_MAIL;
        }

        $fileData = parent::generateEmiFile($emiData);

        return $fileData;
    }

    protected function getFileToWriteName(array $data)
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        static::$fileToWriteName = 'Razorpay_YESEMI_' . $date;

        $filePath = '';

        // for sftp we put the file in a H2H path
        if ($this->transferMode === Base\EmiMode::SFTP)
        {
           $filePath = 'yes/outgoing/';
        }

        return $filePath . static::$fileToWriteName;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('j/n/Y');
    }

    protected function getEncryptionParams()
    {
        return ['secret' => 'C45858B3041DA910EBFB51D16037D95C5D0C7902'];
    }
}
