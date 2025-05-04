<?php

namespace RZP\Models\Settlement\Processor\HDFC;

use Carbon\Carbon;
use Exception;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Models\BankAccount\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Method;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Settlement\Processor\Base;
use RZP\Models\FileStore;
use RZP\Models\Feature;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Gateway;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Mail\Base\Constants as BaseConstants;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core as MerchantCore;


class GifuFile extends Base\BaseGifuFile
{
    protected $fileToWriteName;

    protected $bankName  = 'HDFCCollectNow';

    protected $store = FileStore\Store::S3;  // Adding this so that a file store entity gets created

    protected $totalAmount;

    protected $totalRefundsAmount;

    protected $mailAddress = BaseConstants::MAIL_ADDRESSES[BaseConstants::BANKING_POD_TECH];

    protected $chotaBeam;

    protected $jobNameStage = BeamConstants::HDFC_COLLECT_NOW_JOB_NAME;

    protected $jobNameProd = BeamConstants::HDFC_COLLECT_NOW_JOB_NAME;

    protected $type = FileStore\Type::HDFC_COLLECT_NOW_SETTLEMENT_FILE;

    protected $cardsCutoffTimestamp;

    protected $upiCutoffTimestamp;

    protected $refundCutoffTimestamp;

    public function __construct()
    {
        parent::__construct();

        $date = Carbon::now()->format('d-m-Y');

        $fileDate = Carbon::parse($date)->isoFormat("DD_MM");

        $this->fileToWriteName = 'GEFU' . '_' . $fileDate ;

        $this->transferMode = Base\TransferMode::SFTP;

        $this->chotaBeam = true;
    }

    protected function customFormattingForFile($path,FileStore\Creator $creator = null)
    {

        /*
         * Converts csv file
         * from
         * "this","is","a","test","file"
         * to
         * this,is,a,test,file
         * */

        $stringDataFromFile = file_get_contents($path,FILE_USE_INCLUDE_PATH);

        $stringDataForFile = str_replace('"', "", $stringDataFromFile);

        file_put_contents($path,$stringDataForFile);

        if(is_null($creator) === false)
        {
            $creator->localFilePath($path);

            $creator->save();
        }
    }


    /**
     * @throws \RZP\Exception\BadRequestException
     */
    public function getGifuData($input, $fromTimestamp = null, $toTimestamp = null, $manualGifuTimeRange = null): array
    {
        $data = [];

        $failedMids = [];

        $totalAmount = 0;

        $failedRefundsMids = [];

        $totalAmountForRefunds = 0;

        $modData = [];

        if (Holidays::isWorkingDay(Carbon::today(Timezone::IST)) === false)
        {
            $this->trace->info(TraceCode::GIFU_FILE_DS_PAYMENT_MATRIX,
                [
                    'no gifu generation due to holiday' => Carbon::today(Timezone::IST),
                ]
            );

            return $data;
        }

        $date = Carbon::now()->format('d-m-Y');

        $refundDate = Carbon::yesterday(Timezone::IST)->format('d-m-Y');

        $prevWorkingDay = Holidays::getPreviousWorkingDay(Carbon::today(Timezone::IST));

        $from = $fromTimestamp ?? $prevWorkingDay->copy()->addHour(13)->getTimestamp(); // 1 pm

        $to = $toTimestamp ?? Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(TraceCode::GIFU_FILE_DS_PAYMENT_MATRIX,
            [
                'payload fromTimestamp'=>$fromTimestamp,
                'payload toTimestamp'=> $toTimestamp,
                'payload manualGifuTimeRange' => $manualGifuTimeRange,
                'start for settlement' => $from,
                'end for settlement' => $to,
            ]
        );

        $dataFetch = $this->repo->settlement->getSettlementsBetweenTimePeriodForMerchantIds($input,$from,$to);

        $this->groupSettlementsByMid($dataFetch,$modData);

        $orgId = (new Merchant\Repository)->getMerchantOrg(current($input));

        $requestData = '{"org_id":"' . $orgId . '"}';

        $properties = [
            'id'            => $orgId,
            'experiment_id' => $this->app['config']->get('app.gifu_custom_experiment'),
            'request_data'  => json_encode(['org_id' => $orgId, 'mode' => $this->mode]),
        ];

        $isGifuCustomEnabled =  (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');

         $this->trace->info(TraceCode::SPLITZ_EXPERIMENT_RESULT, [
             'org_id'    => $orgId,
             'experiment_result' => $isGifuCustomEnabled,
             'mode'       => $this->mode,
         ]);

        $dataPayments = [];

        if ($isGifuCustomEnabled === true) {

            // fetching payments for cards DS
            $beginForCardsFromCache = $manualGifuTimeRange['from_card_ds_timestamp'] ?? (new AdminService)->getConfigKey([
                'key' => ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP
            ]);

            $toForCards = $manualGifuTimeRange['to_card_ds_timestamp'] ?? Carbon::yesterday(Timezone::IST)->endOfDay()->getTimestamp(); // 11:59:59 PM yesterday

            $lastCapturedTimeForCards = $this->repo->payment->fetchLastPaymentCaptureTimestampByMethodAndPeriodForMerchants($input,$beginForCardsFromCache,$toForCards,['card'])->first()->last_capture_timestamp;

            $paymentsForCards = $this->repo->payment->fetchAggregatedPaymentsForMethodBetweenTimePeriodForMerchantIds($input, $beginForCardsFromCache, $lastCapturedTimeForCards, ['card']);

            $this->cardsCutoffTimestamp = $paymentsForCards->count() > 0 ? $lastCapturedTimeForCards + 1 : $beginForCardsFromCache;

            $this->trace->info(TraceCode::GIFU_FILE_DS_PAYMENT_MATRIX,
                [
                    'begin time for cards from cache' => $beginForCardsFromCache,
                    'end time for cards' => $toForCards,
                    'last captured time for cards payments' => $lastCapturedTimeForCards,
                    'next cutoff time' => $this->cardsCutoffTimestamp,
                    'cards payment count' => $paymentsForCards->count()
                ]
            );

            // fetching payments for UPI DS
            $beginForUpiFromCache = $manualGifuTimeRange['from_upi_ds_timestamp'] ?? (new AdminService)->getConfigKey([
                'key' => ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP
            ]);

            // Experiment For Gifu UPI DS Settlement Timestamp
            $experimentName = 'gifu_upi_ds_settlement_timestamp_exp_id';

            if($this->isGifuSplitzExperimentEnabled($orgId, $experimentName) === true)
            {
                $toForUpi = $manualGifuTimeRange['to_upi_ds_timestamp'] ?? Carbon::today(Timezone::IST)->startOfDay()->getTimestamp(); // today's time at 12:00 AM in IST
            }
            else
            {
                $toForUpi = $manualGifuTimeRange['to_upi_ds_timestamp'] ?? Carbon::yesterday(Timezone::IST)->setTime(23, 0, 0)->getTimestamp(); // 11 pm yesterday
            }

            $lastCapturedTimeForUpi = $this->repo->payment->fetchLastPaymentCaptureTimestampByMethodAndPeriodForMerchants($input,$beginForUpiFromCache,$toForUpi,['upi'])->first()->last_capture_timestamp;

            $paymentsForUpi = $this->repo->payment->fetchAggregatedPaymentsForMethodBetweenTimePeriodForMerchantIds($input, $beginForUpiFromCache, $toForUpi, ['upi']);

            $this->upiCutoffTimestamp = $paymentsForUpi->count() > 0 ? $lastCapturedTimeForUpi + 1 : $beginForUpiFromCache;

            $this->trace->info(TraceCode::GIFU_FILE_DS_PAYMENT_MATRIX,
                [
                    'begin time for upi from cache' => $beginForUpiFromCache,
                    'end time for upi' => $toForUpi,
                    'last captured time for upi payments' => $lastCapturedTimeForUpi,
                    'next cutoff time' => $this->upiCutoffTimestamp,
                    'upi payment count' => $paymentsForUpi->count()
                ]
            );

            $dataPayments = $paymentsForCards->concat($paymentsForUpi);
        }

        $this->groupPaymentsByMid($dataPayments,$modData);

        $this->trace->info(TraceCode::SETTLEMENT_FILE_MERCHANTS_TO_PROCESS,
            [
                'Mids with data' => array_keys($modData)
            ]
        );

        $merchantService = (new Merchant\Service());

        foreach ($modData as $mid=>$value)
        {
            try
            {
                $merchant = $merchantService->getMerchantFromMid($mid);

                if ($merchant->isFeatureEnabled(Feature\Constants::CANCEL_SETTLE_TO_BANK) === true)
                {
                    return [];
                }

               $accountNumber = $this->getSettlementAccountNumberForMerchant($merchant, $merchantService);

                if(isset($accountNumber) === false)
                {
                    $failedMids[] = $mid;
                    continue;
                }

                $amount = $this->getAggregatedSettlementAmount($value['settlements'] ?? []);

                $this->trace->info(TraceCode::GIFU_FILE_DS_PAYMENT_MATRIX,
                    [
                        'settlement amount' => $amount,
                        'mid '=>$mid
                    ]
                );

                $amount = $amount + $this->getAggregatedPaymentAmount($value['payments'] ?? []);

                $narration = $this->getNarration($value['settlements'] ?? [],$mid);

                $brCode = $this->getBrCode($accountNumber);
            }
            catch (Exception $exception)
            {
                $failedMids[] = $mid;

                $this->trace->info(
                    TraceCode::SETTLEMENT_FILE_CREATE_ERROR,
                    [
                        'exception'      => $exception->getMessage(),
                        'Failed mid'     => $mid
                    ]
                );

                continue;
            }

            $currency = 1;

            $totalAmount = $totalAmount + $amount;

            $dataAdd = [
                Constants::ACCOUNT_NUMBER       =>  $accountNumber,
                Constants::DEBIT_CREDIT         =>  Constants::CREDIT,
                Constants::AMOUNT               =>  number_format($amount,2,'.',''),
                Constants::NARRATION            =>  substr($narration,0,40),
                Constants::BRCODE               =>  $brCode,
                Constants::CURRENCY             =>  $currency,
                Constants::VALUE_DATE           =>  $date
            ];

            $data[] = $dataAdd;
        }

        $this->totalAmount = $totalAmount;

        $poolAcNo = '';

        try{
            $poolAcNo = $merchantService->getBankAccount(array_key_first($modData),[Type::MERCHANT])['account_number'];
        }
        catch (BadRequestException $exception)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_POOL_ACCOUNT_NOT_FOUND ,
                [
                    'Mid' => array_key_first($modData)
                ]
            );
        }

        $brCodeForPool = $this->getBrCode($poolAcNo);

        $narrationDate = Carbon::parse($date)->isoFormat("DDMMYY");

        $dataDebit = [
            Constants::ACCOUNT_NUMBER           => $poolAcNo,
            Constants::DEBIT_CREDIT             => Constants::DEBIT,
            Constants::AMOUNT                   => number_format($this->totalAmount,2,'.',''),
            Constants::NARRATION                => 'GIB Settlement_'.$narrationDate,
            Constants::BRCODE                   => $brCodeForPool,
            Constants::CURRENCY                 => 1,
            Constants::VALUE_DATE               => $date
        ];

        array_unshift($data,$dataDebit);

        $this->trace->info(
            TraceCode::SETTLEMENT_FILE_CREATE_MERCHANT_FAILURES,
            [
                'Failed mids'    => $failedMids
            ]
        );


        // Experiment For Gifu DS refunds For card and upi.
        $experimentName = 'gifu_card_upi_ds_refunds_exp_id';

        $isGifuRefundsEnabled = $this->isGifuSplitzExperimentEnabled($orgId, $experimentName);

        if($isGifuRefundsEnabled === true)
        {
            // fetching refunds for DS Payments for card,upi

            //TODO setup the initial time.
            $beginTimeForRefunds = $manualGifuTimeRange['from_ds_refund_timestamp'] ?? (new AdminService)->getConfigKey([
                'key' => ConfigKey::DS_REFUNDS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP
            ]);

            $endTimeForRefunds = $manualGifuTimeRange['to_ds_refund_timestamp'] ?? Carbon::today(Timezone::IST)->startOfDay()->getTimestamp(); // 12:00:00 AM today in IST

            $methods = [Payment\Method::CARD,Payment\Method::UPI];

            $this->trace->info(TraceCode::GIFU_FILE_DS_REFUND_MATRIX,
                [
                    'begin time for refund from cache'  => $beginTimeForRefunds,
                    'end time for upi'                  => $endTimeForRefunds,
                ]
            );

            [$dataRefunds, $skippedRefundIds, $lastProcessedTimeForRefunds] = $this->repo->refund->fetchAggregatedRefundsForMethodsBetweenTimePeriodForMerchantIds($input, $beginTimeForRefunds, $endTimeForRefunds, $methods);

            $this->refundCutoffTimestamp = count($dataRefunds) > 0 ? $lastProcessedTimeForRefunds + 1 : $beginTimeForRefunds;

            $this->trace->info(TraceCode::GIFU_FILE_DS_REFUND_MATRIX,
                [
                    'last processed time refunds'       => $lastProcessedTimeForRefunds,
                    'next cutoff time for refunds'      => $this->refundCutoffTimestamp,
                    'refunds count'                     => count($dataRefunds)
                ]
            );

            $this->trace->info(TraceCode::SETTLEMENT_FILE_MERCHANTS_TO_PROCESS,
                [
                    'Mids with refund data'     =>  array_keys($dataRefunds),
                    'Skipped refunds ids'       =>  $skippedRefundIds,
                ]
            );

            foreach($dataRefunds as $mid => $value)
            {
                try
                {
                    $merchant = $merchantService->getMerchantFromMid($mid);

                    if ($merchant->isFeatureEnabled(Feature\Constants::CANCEL_SETTLE_TO_BANK) === true)
                    {
                        return [];
                    }

                    $accountNumber = $this->getSettlementAccountNumberForMerchant($merchant, $merchantService);

                    if(isset($accountNumber) === false)
                    {
                        $failedRefundsMids[] = $mid;
                        continue;
                    }

                    $amount = $this->getAggregatedRefundAmount($value ?? []);

                    $this->trace->info(TraceCode::GIFU_FILE_DS_REFUND_MATRIX,
                        [
                            'refund amount' => $amount,
                            'mid '=>$mid,
                        ]
                    );

                    $refundsNarration = $this->getNarrationForRefunds($mid);

                    $brCode = $this->getBrCode($accountNumber);
                }
                catch (Exception $exception)
                {
                    $failedRefundsMids[] = $mid;

                    $this->trace->info(
                        TraceCode::SETTLEMENT_FILE_CREATE_ERROR,
                        [
                            'exception'             => $exception->getMessage(),
                            'Failed refund mid'     => $mid
                        ]
                    );

                    continue;
                }

                $currency = 1;

                $totalAmountForRefunds = $totalAmountForRefunds + $amount;

                $dataAddRefunds = [
                    Constants::ACCOUNT_NUMBER           =>  $accountNumber,
                    Constants::DEBIT_CREDIT             =>  Constants::DEBIT ,
                    Constants::AMOUNT                   =>  number_format($amount,2,'.',''),
                    Constants::NARRATION                =>  substr($refundsNarration,0,40),
                    Constants::BRCODE                   =>  $brCode,
                    Constants::CURRENCY                 =>  $currency,
                    Constants::VALUE_DATE               =>  $refundDate
                ];

                $data[] = $dataAddRefunds;
            }

            $this->totalRefundsAmount = $totalAmountForRefunds;

            $refundsNarrationDate = Carbon::yesterday('Asia/Kolkata')->format("dmy");

            $dataRefundCredit = [
                Constants::ACCOUNT_NUMBER           => $poolAcNo,
                Constants::DEBIT_CREDIT             => Constants::CREDIT,
                Constants::AMOUNT                   => number_format($this->totalRefundsAmount,2,'.',''),
                Constants::NARRATION                => 'GIB Refund_'.$refundsNarrationDate,
                Constants::BRCODE                   => $brCodeForPool,
                Constants::CURRENCY                 => 1,
                Constants::VALUE_DATE               => $refundDate
            ];

            array_splice($data, 1, 0, [$dataRefundCredit]);

            $this->trace->info(
                TraceCode::SETTLEMENT_FILE_CREATE_MERCHANT_FAILURES,
                [
                    'Failed refunds mids'    => $failedRefundsMids
                ]
            );
        }

        return $data;

    }

    protected function groupSettlementsByMid($data, &$modData)
    {
        foreach ($data as $datum)
        {
            $key = $datum['merchant_id'];
            $modData[$key]['settlements'][] = $datum;
        }
    }

    protected function groupPaymentsByMid($data, &$modData)
    {
        foreach ($data as $datum)
        {
            $key = $datum['merchant_id'];
            $modData[$key]['payments'][] = $datum;
        }
    }

    protected function getBrCode($data)
    {
        if(empty($data) === false)
            return substr($data,0,4);

        return '';
    }

    protected function getAggregatedPaymentAmount($data)
    {
        $totalSum = 0;

        foreach ($data as $datum)
        {
            $totalSum = $totalSum + $datum->total_amount - $datum->total_fee;
        }

        return $totalSum/100;
    }

    protected function getAggregatedSettlementAmount($data)
    {
        $totalSum = 0;

        foreach ($data as $datum)
        {
            $totalSum = $totalSum + $datum->amount;
        }

        return $totalSum/100;
    }

    protected function filterBasedonPOStransaction($dataFetch,$from,$to)
    {
        return array_filter($dataFetch, function ($settlement) use ($from, $to) {
            $settlementId = $settlement[Entity::ID];

            $paymentIds = $this->repo->transaction->getPaymentIdsBySettlementId($settlementId, $from, $to);

            if(empty($paymentIds) === true)
            {
                return true;
            }

            $payments = $this->repo->payment->getPaymentsByIds($paymentIds, $from, $to);

            if(empty($payments) === true)
            {
                return true;
            }
            foreach($payments as $payment)
            {
                if(($payment->gateway !== Gateway::HDFC_EZETAP) or ($payment->receiver_type !== Receiver::POS))
                {
                    return true;
                }
            }
            return false;
        });
    }

    protected function getAggregatedRefundAmount($data): float|int
    {
        $totalAmount = 0;

        foreach ($data as $datum)
        {
            $totalAmount = $totalAmount + $datum[RefundEntity::AMOUNT];
        }

        return $totalAmount/100;
    }

    protected function getNarration($data,$mid): string
    {

        // narration -> mid:setl_id:cards_tid/upi_tid

        $params['status'] = 'activated';
        $params['enabled'] = '1';
        $method = 'card';
        $params['gateway'] = 'hdfc';

        $terminals = $this->repo->terminal->fetch($params,$mid);

        if($terminals->count() === 0)
        {
           $method = 'upi';
           $params['gateway'] = 'upi_mindgate';
           $terminals = $this->repo->terminal->fetch($params,$mid);
        }

        $terminal = $this->filterTerminalBasedOnMethod($terminals,$method);

        $tId = '';

        if(is_null($terminal) === false)
            $tId = $terminal['gateway_terminal_id'];

        $this->trace->info(
            TraceCode::SETTLEMENT_FILE_TERMINAL_FETCH,
            [
                'Terminals Fetch Params' => $params,
                'Terminals Count'        => $terminals->count(),
                'Terminal picked'        => $terminals,
                'Merchant Id'            => $mid,
            ]
        );

        $setlId = !empty($data) ? $data[0]->id : '';

        return $mid . ":" . $setlId . ":" . $tId;
    }

    protected function getNarrationForRefunds($mid): string
    {
        //narration -> REF:705001250:MID:DDMMYY

        $narrationDate = Carbon::yesterday('Asia/Kolkata')->format("dmy");

        $fetchParams = [
            Terminal\Entity::GATEWAY  => Payment\Gateway::HDFC,
            Terminal\Entity::STATUS => Terminal\Status::ACTIVATED,
            Terminal\Entity::ENABLED  => '1',
        ];

        $method = Payment\Method::CARD;

        $terminals = $this->repo->terminal->fetch($fetchParams, $mid);

        $cardTerminal = $this->filterRefundsCardsTerminals($terminals, $method);

        $tId = '';

        if(isset($cardTerminal) === true)
        {
            $tId = $cardTerminal[Terminal\Entity::GATEWAY_TERMINAL_ID] ?? '';
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_FILE_TERMINAL_FETCH_FOR_REFUNDS,
            [
                'Terminals Fetch Params' => $fetchParams,
                'Terminals Count'        => $terminals->count(),
                'Terminal picked'        => $terminals,
                'Gateway Terminal Id'    => $tId,
                'Merchant Id'            => $mid,
            ]
        );

        return "REF:". $tId  . ":" . $mid . ":" . $narrationDate;

    }

    protected function filterRefundsCardsTerminals($terminals, $method)
    {
        if($terminals->count() === 0)
            return null;

        foreach ($terminals as $terminal)
        {
            if($terminal->$method === true and $terminal->emi === false)
            {
                return $terminal;
            }
        }

        return null;
    }

    protected function filterTerminalBasedOnMethod($terminals,$method)
    {
        if($terminals->count() === 0)
            return null;

        foreach ($terminals as $terminal)
        {
            if (
                ($method === Method::UPI && $terminal->upi === true) ||
                (($method === Method::CARD && $terminal->card === true) && ($terminal->emi === false))
            ) {
                return $terminal;
            }
        }

        return null;
    }

    protected function getBucketConfig()
    {

        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($this->type, $this->env);

        return $config[$bucketType];
    }

    public function getCardsCutoffTimestamp()
    {
        return $this->cardsCutoffTimestamp;
    }

    public function getUpiCutoffTimestamp()
    {
        return $this->upiCutoffTimestamp;
    }

    public function getRefundCutoffTimestamp()
    {
        return $this->refundCutoffTimestamp;
    }

    public function getSettlementAccountNumberForMerchant($merchant, $merchantService)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::OLD_CUSTOM_SETTL_FLOW) === true)
        {
            $accountNumber = $merchantService->getBankAccount($merchant->getId(),[Type::ORG_SETTLEMENT])['account_number'];
        }
        else
        {
            $accountNumber = $merchantService->getBankAccount($merchant->getId(),[Type::MERCHANT])['account_number'];
        }

        return $accountNumber;
    }

    public function isGifuSplitzExperimentEnabled($orgID, $experimentName): bool
    {
        try
        {
            $splitzResult = $this->getSplitzResponse($orgID, $experimentName);

            if ((isset($splitzResult) === true) and (strtolower($splitzResult) === 'enable'))
            {
                return true;
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::SETTLEMENT_FILE_CREATE_ERROR,
            );
        }
        return false;
    }

    public function getSplitzResponse(string $orgId, string $experimentName)
    {
        try
        {
            $experimentId = $this->config->get('app.'.$experimentName);

            $response = $this->app['splitzService']->evaluateRequest([
                'id'            => $orgId,
                'experiment_id' => $experimentId,
            ]);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'org_id'            => $orgId,
                'experiment_id'     => $experimentId,
                'response'          => $response
            ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'org_id'   => $orgId,
                'experiment_id' => $this->config->get('app.'.$experimentName) ?? null
            ]);
        }
        return $response['response']['variant']['name'] ?? '';
    }

}
