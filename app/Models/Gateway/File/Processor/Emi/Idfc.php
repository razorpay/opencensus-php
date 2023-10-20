<?php


namespace RZP\Models\Gateway\File\Processor\Emi;


use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Mail\Base\Constants;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\Detail;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Gateway\File\Constants as GatewayFileConstants;
use RZP\Models\Gateway\File\Type;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service;
use RZP\Trace\TraceCode;

class Idfc extends Base
{
    const BANK_CODE = IFSC::IDFB;
    const FILE_TYPE = FileStore\Type::IDFC_EMI_FILE;
    const FILE_NAME         = 'Razorpay_instalments';
    const DATE_FORMAT       = 'dmYHis';
    const BEAM_FILE_TYPE    = 'emi';
    const EXTENSION         = FileStore\Format::TXT;
    const COMPRESSION_REQUIRED = false;
    protected $totalAmount;
    protected $emiFilePassword;
    protected $totalTransactions;
    protected $chotaBeam = true;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        return $this->repo
            ->payment
            ->fetchEmiPaymentsOfCobrandingPartnerAndBankWithRelationsBetween(
                $begin,
                $end,
                null,
                static::BANK_CODE,
                [
                    'card.globalCard',
                    'emiPlan',
                    'merchant.merchantDetail',
                    'terminal'
                ]);
    }

    protected function formatDataForFile($data)
    {
        $totalAmount = 0;

        $totalTransactions = 0;

        $headerRecord = [
            'H' .
            '|'.
            Carbon::now()->setTimezone(Timezone::IST)->format('dmY') .
            Carbon::now()->setTimezone(Timezone::IST)->format('His').
            '|'
        ];

        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            $cardNumber = $emiPayment->card->getLast4();

            $emiTenure = $emiPayment->emiPlan['duration'];

            $emiRate = $emiPayment->emiPlan['rate'];

            $emiPercent = $emiRate/100;

            $merchantDetail = $emiPayment->merchant->merchantDetail;

            $businessName = $this->getBusinessName($merchantDetail);

            $authCode = $this->getAuthCode($emiPayment);

            $principalAmount = $emiPayment->getAmount()/100;

            $totalAmount = $totalAmount + $principalAmount;

            $totalTransactions++;

            $formattedData[] = [
                'Card number'                  => $cardNumber,
                'Transaction datetime'         => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Installment short code'       => 'INSTAP'. $this->numpad($emiPercent, 3) ,
                'Tenure'                       => $this->numpad($emiTenure, 2) ,
                'Merchant name'                => $businessName ,
                'Merchant ID'                  => substr(hash("sha256",$emiPayment->merchant['id']), 0, 15),
                'Authorization code'           => substr($authCode, 0, 6),
                'CAVV'                         => '',
                'Transaction serno'            => '',
                'Transaction Partition key'    => '',
                'Source'                       => '10',
                'MCC'                          => '',
                'EMI ID'                       => '',
                'RRN'                          => '',
                'Loan amount'                  => $this->getFormattedAmount($principalAmount),
                'TID'                          => '',
                'Tx Status'                    => '',
                'Subvention payable to Issuer' => '',
                'Subvention Amount'            => '',
                'Issuer'                       => '',
                'Manufacturer'                 => '',
                'Address1'                     => '',
                'Store City'                   => '',
                'Store State'                  => '',
                'Acquirer'                     => '',
                'Settlement Time'              => '',
                'Customer Processing Fee'      => '',
                'Customer Processing Amount'   => '',
                'Interest Rate'                => '',
                'Product Category'             => '',
                'Product Sub Category 1'       => '',
                'Product Sub Category 2'       => '',
                'Model Name'                   => '',
                'EMI Amount'                   => '',
                'Discount'                     => '',
                'Discount Amount'              => '',
                'Is New Model'                 => '',
                'Additional Cashback'          => '',
                'Reward Point'                 => '',
                'External Application ID'      => '',
                'Transaction Ref'              => '',
                'Card Pan'                     => '',
                'Filler 1'                     => '',
                'Filler 2'                     => '',
                'Filler 3'                     => '',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::BANK_CODE,
                ]
            );
        }

        $data = [];

        foreach ($formattedData as $headers => $body)
        {
            $data[] = implode("|", $body);
        }

        $this->totalTransactions = $totalTransactions;

        $this->totalAmount = $this->getFormattedAmount($totalAmount);

        $tailRecord = [
            'T' . '|'.
            $this->totalTransactions . '|'.
            $this->totalAmount.'|'

        ];

        $textRows = array_merge($headerRecord, $data, $tailRecord);

        return implode("\r\n", $textRows);
    }

    protected function getFileToWriteName()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::FILE_NAME . '_' . $date;
    }

    protected function sendEmiPassword($data)
    {
        return;
    }

    protected function sendEmiFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data = [
            Service::BEAM_PUSH_FILES          => $fileInfo,
            Service::BEAM_PUSH_JOBNAME        => BeamConstants::IDFC_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME    => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION  => $bucketConfig['region'],
            Service::CHOTABEAM_FLAG           => $this->chotaBeam,
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'settlements',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'IDFC EMI - File Send failure',
            'recipient' => [
                Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                Constants::MAIL_ADDRESSES[Constants::FINOPS],
                Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
            ],
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['error']) === true) and
            (empty($beamResponse['error']) === false))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'filestore_id'  => $this->file->getId(),
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'job_name'      => BeamConstants::IDFC_EMI_FILE_JOB_NAME,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'IDFC FIRST BANK',
                ]
            );
        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        $bucketConfig['name'] = Config::get('applications.chota_beam.bucket_name');

        return $bucketConfig;
    }



    //-------------------------- Helpers ------------------------------------//

    private function numpad($num, $count)
    {
        return strtoupper(str_pad($num, $count, '0', STR_PAD_LEFT));
    }

    protected function getFormattedAmount($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }

    protected function getBusinessName($merchantDetails)
    {
        $replaceArray = [
            '.',
            '!',
            '@',
            '#',
            '$',
            '%',
            '^',
            '&',
            '*',
            '(',
            ')',
            '~',
            '`',
            '_',
            '+',
            '=',
            '|',
            '\\',
            '\'',
            ':',
            ';',
            '<',
            '>',
            '?',
            '/',
            '{',
            '}',
            '-',
            '_',
            '@',
            ',',
            '[',
            ']',
            '®',
        ];

        $name = str_replace($replaceArray, " ", $merchantDetails[Detail\Entity::BUSINESS_NAME]);

        return substr($name, 0, 25);
    }

}
