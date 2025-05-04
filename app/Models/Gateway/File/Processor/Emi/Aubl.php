<?php


namespace RZP\Models\Gateway\File\Processor\Emi;

use App;
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
use RZP\Services;



class Aubl extends Base
{
    const BANK_CODE = IFSC::AUBL;
    const FILE_TYPE = FileStore\Type::AUBL_EMI_FILE;
    const FILE_NAME         = 'Razorpay_EMI';
    const DATE_FORMAT       = 'M d, Y';
    const BEAM_FILE_TYPE    = 'emi';
    const COMPRESSION_REQUIRED = false;
    protected $totalAmount;
    protected $totalTransactions;
    protected $chotaBeam = false;

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

        $formattedData = [];

        $rrn = $this->getRrnNumber($data['items']);

        foreach ($data['items'] as $emiPayment)
        {
            if ($emiPayment->terminal->isOptimizer())
            {
                continue;
            }

            $cardNumber = $emiPayment->card->getLast4();

            $terminal = $emiPayment->terminal;

            $emiTenure = $emiPayment->emiPlan['duration'];

            $emiRate = $emiPayment->emiPlan['rate'];



            $emiPercent = $emiRate/100;

            $authCode = $this->getAuthCode($emiPayment);

            $principalAmount = $emiPayment->getAmount()/100;

            $emiAmount = $this->getEmiAmount($principalAmount, $emiPercent, $emiTenure);

            $totalAmount = $totalAmount + $principalAmount;

            $formattedData[] = [
                'EMI_ID'                                => $emiPayment->getId(),
                'CARD_PAN'                              => $cardNumber,
                'ISSUER'                                => 'AU BANK' ,
                'RR_NO'                                 => $rrn[$emiPayment->getId()]['rrn'] ?? '',
                'AUTH_CODE'                             => $authCode ,
                'TX_AMOUNT'                             => $this->getFormattedAmount($principalAmount),
                'EMI_OFFER'                             => $emiTenure,
                'MANUFACTURER'                          => 'Bank Emi',
                'AGGREGATOR_MERCHANT_NAME'              =>  $emiPayment->merchant->getName(),
                'ADDRESS'                               => '',
                'STORE_CITY'                            => '',
                'STORE_STATE'                           => '',
                'ACQUIRER'                              => '',
                'MID'                                   => $terminal->getGatewayMerchantId(),
                'TID'                                   => $terminal->getGatewayTerminalId(),
                'TX_DATE'                               => $this->getFormattedDate($emiPayment->getAuthorizeTimestamp()),
                'SETTLEMENT_DATE'                       => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'CUSTOMER_PROCESSING_FEE'               => '',
                'CUSTOMER_PROCESSING_AMOUNT'            => '199',
                'SUBVENTION_PAYABLE_TO_ISSUER'          => '',
                'SUBVENTION_AMOUNT'                     => '',
                'INTEREST_RATE'                         => $emiPercent.'%',
                'TX_STATUS'                             => 'Settled',
                'PRODUCT_CATEGORY'                      => '',
                'CARD_HASH'                             => '',
                'EMI_AMOUNT'                            => $emiAmount,
                'LOAN_AMOUNT'                           => $this->getFormattedAmount($principalAmount),
                'DISCOUNT_CASHBACK_PES'                 => '',
                'DISCOUNT_CASHBACK_AMOUNT'              => '',
                'ADDITIONAL_CASHBACK'                   => '',
                'ORIGINAL_TXN_AMOUNT'                   => $this->getFormattedAmount($principalAmount),
                'ICB_AMOUNT'                            => '',
                'SUBVENTION_TYPE'                       => '',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::BANK_CODE,
                ]
            );
        }
        return $formattedData;
    }

    protected function getFileToWriteName()
    {
        $date = Carbon::now(Timezone::IST)->format('dmYHi');

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
            Service::BEAM_PUSH_JOBNAME        => BeamConstants::AUBL_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME    => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION  => $bucketConfig['region'],
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'settlements',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'AUBL EMI - File Send failure',
            'recipient' => [
                Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                Constants::MAIL_ADDRESSES[Constants::FINOPS],
                Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
            ],
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'filestore_id'  => $this->file->getId(),
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'job_name'      => BeamConstants::AUBL_EMI_FILE_JOB_NAME,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'AUBL Emi BANK',
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

    protected function getRrnNumber($data)
    {
        $CPS_PARAMS = [
            Services\CardPaymentService::RRN
        ];

        $paymentIds = array();

        foreach ($data as $payment)
        {
            array_push($paymentIds, $payment->id);
        }

        $request = [
            'fields'        => $CPS_PARAMS,
            'payment_ids'   => $paymentIds,
        ];

        $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

        return $response;
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





}
