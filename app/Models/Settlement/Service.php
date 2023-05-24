<?php

namespace RZP\Models\Settlement;

use Cache;
use Config;
use Carbon\Carbon;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment;
use RZP\Base\ConnectionType;
use RZP\Models\SalesforceConverge\Error;
use RZP\Models\Schedule;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Reconciliator\FileProcessor;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Schedule\Type;
use RZP\Jobs\Settlement\Create;
use RZP\Models\Merchant\Balance;
use RZP\Models\Feature\Constants;
use RZP\Models\FundTransfer\Kotak;
use RZP\Jobs\Settlement\LedgerRecon;
use RZP\Models\Report\Types\BasicEntityReport;
use RZP\Models\Report\Types\SettlementReconReport;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Schedule\Task as scheduleTask;
use Symfony\Component\HttpFoundation\File\File;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Batch;
use RZP\Jobs;
use RZP\Models\Merchant\InternationalIntegration\Entity as MIIEntity;



const CAPTURE              = 'capture';

class Service extends Base\Service
{
    use FileHandlerTrait;

    const LEDGER_RECON_STATE_PROCESSING                                = 'processing';
    const LEDGER_RECON_STATE_PROCESSED                                 = 'processed';
    const LEDGER_RECON_TRIGGERED_SYSTEM                                = 'system';
    const LEDGER_RECON_TRIGGERED_MANUAL                                = 'manual';
    const SETTLEMENT_OFFSET_MID_CACHE_KEY                              = 'settlement_migration_offset_mid';
    const SETTLEMENT_BLOCKED_TXN_OFFSET_MID_CACHE_KEY                  = 'settlement_blocked_txn_offset_mid';


    public function createSettlementEntry($input)
    {
        // since in new service all these details are in capital letters thus to accommodate that added these
        $input['channel']      = strtolower($input['channel']);
        $input['balance_type'] = strtolower($input['balance_type']);
        $input['status']       = strtolower($input['status']);

        (new Validator)->validateInput('settlement_service_create', $input);

        return (new Processor)->createSettlementEntry($input);
    }

    public function getMerchantSettlementAmount($input)
    {
        (new Validator)->validateInput('settlement_amount', $input);

        $balanceType = $input['balance_type'] ?? Balance\Type::PRIMARY;

        $balance = $this->merchant->getBalanceByType($balanceType);

        $response = [
            'balance'              => (isset($balance) === true) ? $balance->getBalance() : 0,
            'balance_currency'     => (isset($balance) === true) ? $balance->getCurrency() : 'INR',
            'settlement_amount'    => 0,
            'settlement_currency'  => 'INR',
            'next_settlement_time' => null,
        ];

        $isNewService = (new Bucket\Core())->shouldProcessViaNewService($this->merchant->getId(), $balance);

        if($isNewService === true)
        {
                $requestParams = [
                    'merchant_id' => $this->merchant->getId(),
                    'balance_type' => strtoupper($balanceType),
                ];

                try
                {
                    $res = app('settlements_merchant_dashboard')->getNextSettlementAmount($requestParams, $this->mode);

                    if(isset($res['no_settlement']) == true)
                    {
                        return $response + [
                                'no_settlement' => $res['no_settlement']
                            ];
                    }
                    else
                    {
                        $response['next_settlement_time'] = (int) $res['next_settlement_time'];
                        $response['settlement_amount']    = $res['settlement_amount'];
                        $response['settlement_currency']  = $res['settlement_currency'];
                    }
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        null,
                        TraceCode::SETTLEMENT_AMOUNT_FETCH_FROM_NSS_FAILED,
                        [
                            'merchant_id' => $this->merchant->getId(),
                        ]);

                    return $response;
                }
        }
        else
        {
            //
            // This will give wrong result for wealthy merchant on saturdays
            //
            list ($status, $data) = (new Processor)->isMerchantSettlementAllowed($this->merchant, $balanceType);

            if ($status === false)
            {
                return $response + [
                        'no_settlement' =>  $data
                    ];
            }

            $nextSettlementTime = (new Bucket\Core)->getNextSettlementTime($this->merchant, $balance);

            $settlementDetails = (new Core)->getMerchantSettlementAmount(
                $this->merchant,
                $balance,
                $nextSettlementTime);

            $response = array_merge($response, $settlementDetails);
        }

        //
        // settlement amount should be at least 1rs
        // and settlement amount shouldn't be more than the available balance
        //
        if ($response['settlement_amount'] < 100)
        {
            $response += [
                'no_settlement' => [
                    'caption' => 'Settlement might get skipped',
                    'reason'  => 'Settlement amount is less than 1 rupee',
                    'on_hold' => false,
                ],
            ];
        }
        else if ($response['settlement_amount'] > $balance->getBalance())
        {
            $response += [
                'no_settlement' => [
                    'caption' => 'Settlement might get skipped',
                    'reason'  => 'Settlement amount is more than the available live balance',
                    'on_hold' => false,
                ]
            ];
        }
        else
        {
            $nextSettlementTime = Carbon::createFromTimestamp($response['next_settlement_time'], Timezone::IST);

            $response += [
                'reason_for_delay' => Holidays::constructDetailsMessage($nextSettlementTime),
            ];
        }

        return $response;
    }

    public function initiateSettlements($input, $channel = null)
    {
        (new Validator)->validateInput('settlement_initiate', $input);

        $balanceType = $input['balance_type'] ?? Balance\Type::PRIMARY;

        $data = (new Settlement\Processor)->process($input, $channel, $balanceType);

        return $data;
    }

    public function processFailedSettlements($input)
    {
        $data = (new Settlement\Processor)->processFailedSettlements($input);

        return $data;
    }

    public function processDailySettlements($input)
    {
        $data = (new Settlement\Processor)->processDailySettlements($input);

        return $data;
    }

    /** Generates settlement file for a given batch_fund_transfer_id
      * Uses settlement entities / fund_transfer_attempt entities to generate
      * file depending on the created_at timestamp of the batch.
      * If the batch was created before the timestamp (i.e. before rolling out
      * attempt base file generation) settlement entities are used.
      * Else corresponding attempt entities are used.
      */
    public function generateSettlementFile($input)
    {
        (new Settlement\Validator)->validateInput('batch_fetch', $input);

        $batchId = $input['batch_fund_transfer_id'];

        $batch = $this->repo->batch_fund_transfer->findOrFailPublic($batchId);

        $versionV2RolloutTimestamp = 1489170600; // Date 1st March 2017 IST

        $currentTimestamp = Carbon::now()->getTimestamp();

        if ($batch->getCreatedAt() < $versionV2RolloutTimestamp)
        {
            $entities = $this->repo->settlement->getSettlementsByBatchFundTransferId($batchId);
        }
        else
        {
            $entities = $this->repo
                             ->fund_transfer_attempt
                             ->getFundTransferAttemptsByBatchIdWithRelations(
                                $batchId,
                                ['source', 'source.merchant', 'source.merchant.bankAccount']);
        }

        $channel = $batch->getChannel();

        $nodalAccountClass = 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\NodalAccount';

        $h2h = (bool) ($input['h2h']);

        $fileCreator = (new $nodalAccountClass)->generateFundTransferFile($entities, $h2h);

        return $fileCreator->get();
    }

    public function fetch($id)
    {
        if ($this->auth->isOptimiserDashboardRequest()) {
            try {
                $fetchInput = $this->createFetchInput($id);

                $settlement = app('settlements_dashboard')->fetch($fetchInput);

                $setl = new Settlement\Entity($settlement);

                $setl->setPublicAttributeForOptimiser($settlement['entity']);

                return $setl->toArrayPublic();

            } catch (\Throwable $e) {
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::GET_SETTLEMENTS_FOR_OPTIMIZER_MERCHANT_DASHBOARD_FAILED,
                    [
                        'id'        => $id,
                        'request'   => 'fetch',
                    ]);
            }

        }

        $setl = $this->repo->settlement->findByPublicIdAndMerchant($id, $this->merchant);

        return $setl->toArrayPublic();
    }

    public function fetchOrgSettlement($settlementId, $sessionMerchamtId)
    {
        try {
            $setl = app('settlements_api')->getOrgSettlement($settlementId, $this->mode);

        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::GET_SETTLEMENT_DETAILS_FOR_PAYMENT_FAILED,
                [
                    'id'        => $settlementId,
                    'request'   => 'fetch',
                    'code'      => $e->getCode(),
                ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_SETTLEMENT_NOT_FOUND);

        }

        if ((isset($setl["org_settlement"]["merchant_id"]) === true) and $setl["org_settlement"]["merchant_id"] === $sessionMerchamtId)
        {
            return $setl;
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
    }

    public function editSettlement($id, $input)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFailPublic($id);

        if ((isset($input['status'])) and
            ($input['status'] === Status::FAILED))
        {
            if ($setl->isStatusCreated() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Settlement status is not created. Status: ' . $setl->getStatus());
            }

            $setl->setStatus(Settlement\Status::FAILED);
            $this->repo->saveOrFail($setl);
        }

        return $setl->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        // Status is not indexed so keeping the time interval as 30days by default in case date filter is not passed from UI
        if ((isset($input['status']) === true) and
            (isset($input['from']) === false) and
            (isset($input['to']) === false))
        {
            $to = Carbon::now(Timezone::IST)->endOfDay()->getTimestamp();

            $from = Carbon::now(Timezone::IST)->subDays(30)->startOfDay()->getTimestamp();

            $input['to'] = $to;

            $input['from'] = $from;
        }

        if ($this->auth->isOptimiserDashboardRequest()) {

            // Currently the value of status is stored in all caps in NSS DB.
            // In case of search by status filter, FE passes the value in lowercase so converting it to uppercase
            // when fetching the data from NSS
            if (isset($input['status']) === true)
            {
                $input['status'] = strtoupper($input['status']);
            }

            try {
                $fetchInput = $this->createFetchMultipleInput($input);

                $settlements = app('settlements_dashboard')->fetchMultiple($fetchInput);

                $settlements = $this->convertToCollection($settlements);

                return $settlements->toArrayPublic();

            } catch (\Throwable $e) {
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::GET_SETTLEMENTS_FOR_OPTIMIZER_MERCHANT_DASHBOARD_FAILED,
                    [
                        '$input' => $input,
                        'request'=> 'fetchMultiple',
                    ]);
            }
        }

        $settlements = $this->repo->settlement->fetch($input, $this->merchant->getKey());

        return $settlements->toArrayPublic();
    }

    public function fetchSettlementTransactions($id)
    {
        $setl = $this->repo->settlement->findByPublicIdAndMerchant($id, $this->merchant);

        return $this->getTransactionsForSettlement($id, $setl);
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    public function sendGifuFile($input, $orgId)
    {
        $from = null;

        $to = null;

        $org = $this->repo->org->findOrFail($orgId);

        if (($org->isFeatureEnabled(Feature\Constants::ORG_POOL_ACCOUNT_SETTLEMENT) === false) and
            ($org->isFeatureEnabled(Feature\Constants::ORG_SETTLE_TO_BANK) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND);
        }

        $bankName = $org->getDisplayName();

        if($this->auth->isAdminAuth() === true){

            (new Validator)->validateInput('admin_generate_gifu_file', $input);
            (new Validator)->validateMerchantIdsBelongToOrg($orgId , $input['merchant_ids']);
            $merchantIds = $input['merchant_ids'];
            $from = $input['from_timestamp'];
            $to = $input['to_timestamp'];
        }
        else
        {
            $merchantIds = $this->repo->merchant->fetchMerchantIdsByOrgId($orgId);
        }

        $class = $this->getGifuFileClass($bankName);

        $fileProcessor = new $class;

        $ufhResponse = $fileProcessor->generate($merchantIds, $from, $to);

        if(count($ufhResponse) !== 0){
            $fileProcessor->sendGifuFile($ufhResponse);
        }

        return $ufhResponse;

    }

    protected function getGifuFileClass($bank): string
    {
        $bankName = explode(' ', $bank)[0];

        return  __NAMESPACE__.'\\Processor\\'.($bankName).'\\GifuFile';

    }

    public function getNiumFile($input)
    {
        $bankName = 'NIUM';
        $parentMerchantID = $input['niumMerchantId'];

        $from = $input['from'] ?? null;
        $to = $input['to'] ?? null;
        $sendFile = $input['sendFile'] ?? false;

        $subMerchants = $this->repo->merchant_access_map->getMappingsFromEntityOwnerId($parentMerchantID);
        $subMerchantIds = $this->getMerchantIdsFromAccessMaps($subMerchants);

        $this->trace->info(
            TraceCode::NIUM_FILE_GENERATION,
            [
                'SubmerchantIds' => $subMerchantIds,
            ]
        );

        $class = $this->getGifuFileClass($bankName);

        $fileProcessor = new $class;
        $ufhResponse = $fileProcessor->generate($subMerchantIds, $from, $to);

        if($sendFile === true && count($ufhResponse) !== 0){
            $fileProcessor->sendGifuFile();
        }
        return $ufhResponse;
    }

    public function onholdClearForImportFlow($input)
    {

        $response = [];
        try {

            $merchantIntegrationInfo = $this->repo->merchant_international_integrations
                ->getByIntegrationKey($input['integration_entity']);

            foreach ($merchantIntegrationInfo as $mii)
            {
                $merchantId = $mii[MIIEntity::MERCHANT_ID];
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                if($merchant->isOpgspImportSettlementEnabled() === true)
                {
                    $data = [
                        'merchant_id' => $merchantId,
                        'action' => Jobs\ImportFlowSettlementProcessor::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT_BULK,
                        // this is required when someone wants to manually trigger the cron
                        // default is 15 days.
                        'prev_days' => $input['prev_days'] ?? null,
                    ];
                    Jobs\ImportFlowSettlementProcessor::dispatch($data)->delay(rand(60, 1000) % 601);
                }
            }

            $response['success'] = true;

        }catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IMPORT_FLOW_BULK_ON_HOLD_CLEAR_FAILED,[
                    '$input' => $input,
                ]
            );
            $response['success'] = false;
        }

        return $response;
    }

    public function sendIciciOpgspImportSettlementFile($input)
    {

        $this->app['rzp.mode'] = 'live';

        $response = [];
        try {

            $merchantIntegrationInfo = $this->repo->merchant_international_integrations
                ->getByIntegrationKey($input['integration_entity']);

            foreach ($merchantIntegrationInfo as $mii)
            {
                $data = [
                    'merchant_id' => $mii[MIIEntity::MERCHANT_ID],
                    'action'      => Jobs\ImportFlowSettlementProcessor::OPGSP_IMPORT_GENERATE_SETTLEMENT_FILE,
                    'send_file'   => $input['send_file'] ?? false,
                    'from'        => $input['from'] ?? null,
                    'to'          => $input['to'] ?? null,
                ];

                Jobs\ImportFlowSettlementProcessor::dispatch($data)->delay(rand(60, 1000) % 601);
            }

            $response['success'] = true;

        }catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::OPGSP_IMPORT_FLOW_GENERATE_FILE_ERROR,[
                    '$input' => $input,
                ]
            );
            $response['success'] = false;
        }

        return $response;
    }

    public function sendIciciOpgspImportInvoices($input)
    {

        $this->app['rzp.mode'] = 'live';

        $response = [];
        try {

            $merchantIntegrationInfo = $this->repo->merchant_international_integrations
                ->getByIntegrationKey($input['integration_entity']);

            foreach ($merchantIntegrationInfo as $mii)
            {
                $data = [
                    'merchant_id' => $mii[MIIEntity::MERCHANT_ID],
                    'action'      => Jobs\ImportFlowSettlementProcessor::OPGSP_IMPORT_SEND_INVOICES,
                    'from'        => $input['from'] ?? null,
                    'to'          => $input['to'] ?? null,
                ];

                Jobs\ImportFlowSettlementProcessor::dispatch($data)->delay(rand(60, 1000) % 601);
            }

            $response['success'] = true;

        }catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IMPORT_FLOW_BULK_ON_HOLD_CLEAR_FAILED,[
                    '$input' => $input,
                ]
            );
            $response['success'] = false;
        }

        return $response;
    }

    /**
     * This method will only process push based settlement reconciliation
     *
     * @param        $input
     * @param string $channel
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function reconcileSettlementsThroughFile($input, string $channel)
    {
        $fileBasedChannels = Channel::getFileBasedChannels();

        if (in_array($channel, $fileBasedChannels, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Channel Does not support File based approach',
                null,
                [
                    'channel' => $channel
                ]);
        }

        $reconNamepsace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\Reconciliation\\Processor';

        return (new $reconNamepsace)->process($input);
    }

    /**
     * This method will only process pull based settlement reconciliation
     *
     * @param        $input
     * @param string $channel
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function settlementReconcileThroughApi($input, string $channel)
    {
        $apiBasedChannels = Channel::getApiBasedChannels();

        if (in_array($channel, $apiBasedChannels, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Channel Does not support API based approach',
                null,
                [
                    'channel' => $channel
                ]);
        }

        (new Validator)->validateInput('status_reconcile_for_api', $input);

        $reconNamespace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel) . '\\Reconciliation\\Processor';

        return (new $reconNamespace)->process($input);
    }

    public function reconcileH2HSettlements($input, string $channel)
    {
        $reconNamepsace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\Reconciliation\\Processor';

        return (new $reconNamepsace)->process($input);
    }

    public function reconcileSettlementsInTestMode(array $input)
    {
        $result = [];

        foreach (Channel::getChannelsWithReconMock() as $channel)
        {
            $class = 'RZP\\Models\\FundTransfer\\' . ucfirst($channel)
                     . '\\Reconciliation\\Mock\\FileGenerator';

            $result[] = (new $class)->reconcileSettlements($input);
        }

        return $result;
    }

    public function generateSettlementReconciliationFile($input, string $channel)
    {
        (new Settlement\Validator)->validateInput('valid_channel', [
            'channel'   => $channel
        ]);

        $reconGeneratorNamespace = '\\RZP\\Models\FundTransfer\\'
                                    . ucfirst($channel)
                                    . '\\Reconciliation\\Mock\\FileGenerator';

        $filename = (new $reconGeneratorNamespace)->generateReconcileFile($input);

        return ['setlReconciliationFile' => $filename];
    }

    public function generateSettlementReturn($input)
    {
        return (new Kotak\Service)->generateSettlementReturn($input);
    }

    public function deleteSetlFile($setlFileType)
    {
        (new Kotak\Service)->deleteSetlFile($setlFileType);
    }

    public function getSettlementCombinedReport($input)
    {
        $report = new BasicEntityReport(E::TRANSACTION);

        return $report->getReport($input);
    }

    public function getSettlementCombinedReconReport($input)
    {
        $report = new SettlementReconReport(E::TRANSACTION);

        return $report->getReport($input);
    }

    public function updateChannelForMultipleSettlements($input)
    {
        $this->trace->info(
            TraceCode::SETTLEMENTS_CHANNEL_BULK_UPDATE_REQUEST,
            $input
        );

        $response = (new Core)->updateChannel($input);

        return $response;
    }

    /**
     * Initiates transfer from one Nodal account to another
     */
    public function postInitiateTransfer(array $input): array
    {
        $response = (new Core)->postInitiateTransfer($input);

        return $response;
    }

    /**
     * Add beneficiary from one Nodal account to another
     */
    public function addBeneficiary(string $channel, array $input): array
    {
        $response = (new Core)->addBeneficiary($channel, $input);

        return $response;
    }

    /**
     * Gets account balance of Nodal Account
     *
     * @param string $channel channel for which the balance has to be fetched
     *
     * @return array
     * [
     *  account_number => account_balance,
     * ]
     */
    public function getAccountBalance(string $channel): array
    {
        $channelAttributeKey = 'balance_' . Entity::CHANNEL;

        (new Validator)->validateInput('canFetchBalance', [
            $channelAttributeKey => $channel
        ]);

        $response = (new Core)->getAccountBalance($channel);

        return $response;
    }

    /**
     * @param array $input
     * @param string $channel
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    public function verifySettlementsThroughApi(array $input, string $channel): array
    {
        $this->trace->info(
            TraceCode::VERIFY_FUND_TRANSFER_INIT,
            [
                'input'     => $input,
                'channel'   => $channel
            ]);

        $apiBasedChannels = Channel::getApiBasedChannels();

        if (in_array($channel, $apiBasedChannels, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Channel Does not support API based approach for verification',
                null,
                [
                    'channel' => $channel
                ]);
        }

         if (empty($input) === true)
         {
           throw new Exception\BadRequestValidationFailureException('Input is empty!');
         }

        (new Validator)->validateInput('settlement_verify', $input);

        $reconNamepsace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel) . '\\Reconciliation\\Processor';

        return (new $reconNamepsace)->verify($input);
    }

    public function notifyH2HErrors($input, string $channel)
    {
        $reconNamepsace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\Reconciliation\\Processor';

        return (new $reconNamepsace)->notifyH2HErrors($input);
    }

    public function processAdhocSettlements($input, $channel = null)
    {
        $data = (new Settlement\Processor)->processAdhocSettlements($input);

        return $data;
    }

    public function getProcessDetails(): array
    {
        $redis = $this->app['redis']->Connection('mutex_redis');

        $countKey            = sprintf(Create::TOTAL_MERCHANT_COUNT, $this->mode);
        $channelWiseCountKey = sprintf(Create::CHANNEL_WISE_COUNT, $this->mode);

        return [
            'pending_merchants'    => Cache::get($countKey),
            'channel_wise_process' => $redis->hGetAll($channelWiseCountKey),
        ];
    }

    public function resetProcessDetails()
    {
        $countKey            = sprintf(Create::TOTAL_MERCHANT_COUNT, $this->mode);
        $channelWiseCountKey = sprintf(Create::CHANNEL_WISE_COUNT, $this->mode);

        Cache::forget($countKey);

        $redis = $this->app['redis']->Connection('mutex_redis');

        $redis->del($channelWiseCountKey);

        return $this->getProcessDetails();
    }

    /**
     * This method is used to get the settlement Holiday List which is used in the settlement UI Dashboard
     *
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     */
    public function getHolidayListForYear(array $input)
    {
        (new Validator)->validateInput('settlement_holiday', $input);

        $timezone = Timezone::IST;
        $countryCode = "IN";
        $merchant = $this->app['basicauth']->getMerchant();
        if (empty($merchant) === false) {
            $countryCode = $merchant->getCountry();
            $timezone = $merchant->getTimeZone();
        }

        $year = Carbon::now($timezone)->year;
        if (isset($input['year']) === true)
        {
            $year = (int) $input['year'];
        }

        if ($timezone == Timezone::IST) {
            return Holidays::getHolidayListForYear($year);
        }

        return app('settlements_merchant_dashboard')->getHolidaysForYearAndCountry($year, $countryCode, $timezone);
    }

    /**
     * This method used for getApi for facebook payout-release, it is written to support the OAuth changes that
     * Facebook is going to use. It simply modifies the response from the initial method to support the merchantId
     * in the response.
     *
     * @param $id
     * @param array $input
     * @return array|mixed
     * @throws Exception\BadRequestException
     */
    public function getSettlementTransactionsWithSettlementId($id, array $input = [])
    {
        $id = Entity::stripSignWithoutValidation($id);

        $setl = $this->repo
                     ->settlement
                     ->findOrFailByPublicIdWithParams($id, $input);

        $settlementMerchantId = $setl->getMerchantId();

        if ($this->merchant->getId() !== $settlementMerchantId)
        {
            // if settlement merchant is not same as context merchant, other valid possibility is that fetch is called by
            // the partner merchant of that submerchant
            $this->checkAuthMerchantAccessToEntity($settlementMerchantId);
        }

        $entity = $this->getTransactionsForSettlement($id, $setl);

        $entity = array_merge($entity, ['merchant_id' => $settlementMerchantId]);

        return $entity;
    }

    public function getSettlementTransactionsSourceDetails($id, $input)
    {
        $id = Entity::stripDefaultSign($id);

        (new Validator())->validateInput('settlement_transaction_source_detail', $input);

        $this->trace->info(
            TraceCode::GET_SOURCE_TRANSACTION_DETAILS,
            [
               'settlement_id' => $id,
               'input'         => $input,
            ]);

        $sourceId = null;

        if(isset($input['source_id']) === true)
        {
            $sourceId = Entity::stripDefaultSign($input['source_id']);
        }

        $sourceType = $input['source_type'];

        $skip = $input['skip'];

        $limit = $input['limit'];

        $startTime = microtime(true);

        $txns = [];

        if ($this->auth->isOptimiserDashboardRequest() === false)
        {
            $txns = $this->repo
                ->transaction
                ->fetchBySettlementIdAndSource($id, $sourceType, $skip, $limit, $sourceId);
        }
        else
        {
            try {
                $fetchInput = $this->createFetchMultipleTxnInput($id, $input, $sourceId);

                $transactions = app('settlements_merchant_dashboard')->getSettlementSourceTransaction($fetchInput);

                $txns = $this->convertToTxnCollection($transactions);

            } catch (\Throwable $e) {
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::GET_SETTLEMENT_TRANSACTIONS_FOR_SOURCE_TYPE_FAILED,
                    [
                        'input' => $input,
                        'id'=> $id,
                    ]);
            }
        }

        $result = [];

        foreach ($txns as $txn)
        {
            $res = [
                'id'              => $txn->source->getPublicId(),
                'amount'          => $txn->getAmount(),
                'fee'             => $txn->getFee(),
                'tax'             => $txn->getTax(),
                'created_at'      => $txn->getCreatedAt(),
                'international'   => false,
            ];

            if ($sourceType === E::REFUND)
            {
                $res['international'] = $txn->source->payment->isInternational();
            }
            else if (method_exists($txn->source, 'isInternational') === true)
            {
                $res['international'] = $txn->source->isInternational();
            }

            if(method_exists($txn->source, 'getStatus') === true)
            {
                $res['status'] = $txn->source->getStatus();
            }

            if ($this->auth->isOptimiserDashboardRequest() === true and
                in_array( $sourceType, ['payment', 'refund']) === true)
            {
                $res['optimizer_provider'] = $txn->source->getOptimiserProvider();

                $res['settled_by'] = $txn->source->getSettledBy();

                $res['amount'] = $txn->source->getAmount();
            }

            $result[] = $res;
        }

        $this->trace->info(
            TraceCode::GET_SOURCE_TRANSACTION_DETAILS,
            [
                'settlement_id' => $id,
                'input'         => $input,
                'time_taken'    => get_diff_in_millisecond($startTime),
            ]);

        return $result;
    }

    /**
     * This method is used to identify weather the request is done on behalf of the submerchant by the partner
     *
     * @param string $entityMerchantId
     * @throws Exception\BadRequestException
     */
    protected function checkAuthMerchantAccessToEntity(string $entityMerchantId)
    {
        if($this->merchant->isPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null, null);
        }

        $partners = (new Merchant\Core())->fetchAffiliatedPartners($entityMerchantId);

        //check if the auth merchant is one of the affiliated partners of the submerchant.
        $applicablePartners = $partners->filter(function(Merchant\Entity $partner)
        {
            return ((($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true)) and ($partner->getId() === $this->merchant->getId()));
        });

        if ( $applicablePartners->isEmpty() === true )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null, null);
        }
    }

    /**
     * This method is used to find all the transactions associated with the particular settlement id
     *
     * @param $id
     * @param $setl
     * @return mixed
     */
    public function getTransactionsForSettlement($id, $setl)
    {
        // Maps the transaction source to the entities to be fetched for it
        $txnToRelationFetchMap = [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT  => [E::ORDER, E::CARD],
            E::REFUND   => [
                E::PAYMENT,
                E::PAYMENT . '.' . E::CARD,
                E::PAYMENT . '.' . E::ORDER,
            ],
            E::ADJUSTMENT   => [
                Adjustment\Entity::ENTITY,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::CARD,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::ORDER,
            ],
            E::SETTLEMENT,
        ];

        $start = microtime(true);

        $txns = $this->repo->transaction->fetchBySettlement($setl, $txnToRelationFetchMap);

        $timeTaken = get_diff_in_millisecond($start);

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTION_FETCH,
            [
                'merchantId'    => $this->merchant->getId(),
                'settlement_id' => $id,
                'txn_count'     => $txns->count(),
                'time_taken'    => $timeTaken
            ]);

        return $txns->toArrayPublic();
    }

    public function replayTransactions(array $input): array
    {
        if (isset($input['initial_ramp']) === true)
        {
            $input['initial_ramp'] = ($input['initial_ramp'] === '1');
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTION_REPLAY_REQUEST,
            $input
        );

        (new Validator)->validateInput('settlement_transactions_replay', $input);

        $this->core()->enqueueForReplay($input);

        return [
            'replayed_mids' => $input['merchant_ids'],
        ];
    }

    /**
     * This function used to update the settlement details back from the settlement service
     *
     * @param array $input
     * @return array|null[]
     */
    public function postSettlementCreateStatusUpdate(array $input)
    {
        $input['status'] = strtolower($input['status']);

        (new Validator)->validateInput('settlement_status_update', $input);

        return (new Processor)->settlementStatusUpdate($input);
    }

    public function getIrctcSettlementFile(string $date, $input)
    {
        $sftp = new SFTP('sftp.razorpay.com');

        $privateKey = new RSA();

        $secretKey = Config::get('applications.rzp_sftp.rzp_sftp_secret_key');

        $secretKey = trim(str_replace('\n', "\n", $secretKey));

        $privateKey->loadKey($secretKey, RSA::PRIVATE_FORMAT_PKCS1);

        $privateKey->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        $fileBasePath = Config::get('applications.rzp_sftp.rzp_sftp_file_path');

        $username = Config::get('applications.rzp_sftp.rzp_sftp_sftp_username');

        if (!$sftp->login($username, $privateKey)) {
            throw new Exception\ServerErrorException(
                'sftp connection failed', ErrorCode::SERVER_ERROR_SFTP_CONNECTION_FAILED);
        }

        $filename = $fileBasePath."RZPX_Settlement_".$input['merchant_id']."_".$date.".csv";

        return ['data' => $sftp->get($filename)];
    }

    public function settlementTransactionsVerify(array $input)
    {
        (new Validator)->validateInput('settlement_transactions_verify', $input);

        $txnIds = $input['transaction_ids'];

        $txns = $this->repo->transaction->verifySettlementTransactions($txnIds);

        $verifyFailedTxnIds = [];

        foreach ($txnIds as $txnId)
        {
            if (isset($txns[$txnId]) === false)
            {
                $verifyFailedTxnIds[] = $txnId;
            }
            else
            {
                if(isset($txns[$txnId]['balance_type']) === false)
                {
                    $txns[$txnId]['balance_type'] = Balance\Type::PRIMARY;
                }
            }
        }

        $verifiedTxnCount = sizeof($txns);

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTIONS_VERIFY,
            [
                'request_txn_count' => sizeof($input['transaction_ids']),
                'verified_txn_count'  => $verifiedTxnCount,
                'unverified_txns_ids'   => $verifyFailedTxnIds,
            ]);

        if ($verifiedTxnCount === 0)
        {
            return null;
        }

        return $txns;
    }

    public function getSettlementSourceDetails($input)
    {
        // Maps the transaction source to the entities to be fetched for it
        $txnToRelationFetchMap = [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT => [
                E::PAYMENT  => [
                    E::ORDER,
                    'paymentMeta'
                ],
            ],
            E::REFUND => [
                E::REFUND   => [],
            ],
            E::ADJUSTMENT => [
                E::ADJUSTMENT   => [
                    Adjustment\Entity::ENTITY,
                    Adjustment\Entity::ENTITY . '.' . E::PAYMENT,
                    Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::ORDER,
                    Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . 'paymentMeta',
                ],
            ],
            E::DISPUTE => [
                E::ADJUSTMENT   => [
                    Adjustment\Entity::ENTITY,
                    Adjustment\Entity::ENTITY . '.' . E::PAYMENT,
                    Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::ORDER,
                    Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . 'paymentMeta',
                ],
            ],
            E::REVERSAL => [
                E::REVERSAL => [],
            ]
        ];

        $start = microtime(true);

        $transactions = $this->repo->transaction->findMany($input['ids']);

        $source = $input['source_type'];

        $txns = $this->repo->transaction
                           ->fetchAssociatedRelationsWithLoadedEntities(
                               $transactions,'source', $txnToRelationFetchMap[$source]);

        $timeTaken = get_diff_in_millisecond($start);

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTION_FETCH,
            [
                'txn_count'     => $txns->count(),
                'time_taken'    => $timeTaken
            ]);

        $result = [];

        foreach ($txns as $txn)
        {
            $response = [
                'id'          => $txn->getId(),
                'amount'      => $txn->getAmount(),
                'fees'        => $txn->getFee(),
                'tax'         => $txn->getTax(),
                'currency'    => $txn->getCurrency(),
                'source_type' => $source,
                'source_id'   => $txn->source->getId(),
            ];

            switch ($source)
            {
                case E::PAYMENT:

                    $paymentMeta = (new Payment\Service)->getPaymentMetaByPaymentIdAction($txn->source->id, CAPTURE);

                    $googleRequestId = null;

                    if ($paymentMeta !== null)
                    {
                        $googleRequestId = $txn->source->paymentMeta->getReferenceId();
                    }

                    $response['external_id'] = $googleRequestId;

                    $result[] = $response;

                    break;

                case E::REFUND:

                    $response['external_id'] = $txn->source->getReceipt();

                    $result[] = $response;

                    break;

                case E::REVERSAL:

                    $response['external_id'] =  $txn->source->entity->getReceipt();

                    $result[] = $response;

                    break;

                case E::ADJUSTMENT:

                    $paymentMeta = null;

                    if($txn->source->entity != null && $txn->source->entity->payment != null){
                        $paymentMeta = $txn->source->entity->payment->paymentMeta;
                    }

                    $googleRequestId = null;

                    if ($paymentMeta != null)
                    {
                        $googleRequestId = $paymentMeta->getReferenceId();
                    }

                    $response['external_id'] = $googleRequestId;

                    $result[] = $response;

                    break;

                case E::DISPUTE:

                    $paymentMeta = null;

                    if($txn->source->entity != null && $txn->source->entity->payment != null){
                        $paymentMeta = $txn->source->entity->payment->paymentMeta;
                    }

                    $googleRequestId = null;

                    if ($paymentMeta != null)
                    {
                        $googleRequestId = $paymentMeta->getReferenceId();
                    }

                    if($txn->source->entity != null) {
                        $response['source_id'] = $txn->source->entity->getId();
                    }

                    $response['external_id'] = $googleRequestId;

                    $result[] = $response;

                    break;
            }
        }

        return $result;
    }

    //********************* All proxy routes are Listed Here *********************//

    public function serviceFetch(array $input) : array
    {
        return app('settlements_dashboard')->fetch($input);
    }

    public function serviceFetchMultiple(array $input) : array
    {
        return app('settlements_dashboard')->fetchMultiple($input);
    }

    public function merchantConfigGet(array $input) : array
    {
        return app('settlements_dashboard')->merchantConfigGet($input);
    }

    public function settlementTimelineModalGet(array $input) : array
    {
        return app('settlements_merchant_dashboard')->settlementTimelineModalGet($input);
    }

    public function settlementTimeline(array $input) : array
    {
        $merchant = $this->merchant;
        $merchantId = $merchant->getId();

        $isNewService = $this->repo->feature->getMerchantIdsHavingFeature(Constants::NEW_SETTLEMENT_SERVICE, array($merchantId));

        // check if the merchant is in new service
        if(empty($isNewService) === true){
            return ['status' => false];
        }

        // Maps the transaction source to the entities to be fetched for it
        $txnToRelationFetchMap = [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT => [
                E::PAYMENT  => [],
            ],
            E::REFUND => [
                E::REFUND   => [],
            ],
        ];

        $transactionId = $input['transaction_id'];
        $transaction = $this->repo->transaction->findById($transactionId);
        $source = $input['source_type'];

        $txn = $this->repo->transaction
            ->fetchAssociatedRelationsWithLoadedEntities(
                $transaction,'source', $txnToRelationFetchMap[$source]);

        $this->trace->info(
            TraceCode::TRANSACTION_ENTITY_FETCH,
            [
                'txn'     => $txn,
            ]);

        $startedAt = 0;

        switch($source) {

            case E::PAYMENT:
                $payment = $txn[0]->source;
                $capturedAt = $payment['captured_at'];
                if($capturedAt){
                    $startedAt = $capturedAt;
                }
                break;

            case E::REFUND:
                $refund = $txn[0]->source;
                $processedAt = $refund['processed_at'];
                if($processedAt){
                    $startedAt = $processedAt;
                }
                break;
        }

        $settlementTimeLineModalInput = [
            'merchant_id' => $merchantId,
            'transaction_id' => $transactionId,
            'created_at' => $startedAt
        ];

        $this->trace->info(TraceCode::SETTLEMENT_TIMELINE_REQ, [
            'settlement_timeline_req' => $settlementTimeLineModalInput,
        ]);

        $settlementTimeLineModal = ['status' => false];

        try
        {
            if($startedAt) {
                $settlementTimeLineModal = $this->settlementTimelineModalGet($settlementTimeLineModalInput);
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode:: SETTLEMENT_TIMELINE_MODAL_FAILED,
                [
                    'merchant_id'       => $merchantId,
                    'transaction_id'    => $transactionId,
                    'created_at'       => $startedAt,
                ]
            );
        }

        return $settlementTimeLineModal;
    }


    // RSR-1970 merchant config from merchant dashboard
    public function merchantDashboardConfigGet(array $input) : array
    {
        $merchant = $this->merchant;

        $mid = $merchant->getId();

        $isNewService = $this->repo->feature->getMerchantIdsHavingFeature(Constants::NEW_SETTLEMENT_SERVICE, array($mid));

        $this->trace->info(
            TraceCode::SETTLEMENT_MERCHANT_DASHBOARD_FETCH_REQUEST,
            [
                'merchant_id'    => $mid,
                'is_new_service' => $isNewService
            ]);

        if (empty($isNewService) === true)
        {
            $schedulesFetched = $this->repo->schedule_task->fetchByMerchant($merchant, 'settlement');

            $scheduleTasks = [];

            $schedulesFetched->each(function ($scheduleTask) use (& $scheduleTasks)
            {
                array_push($scheduleTasks, [
                    ScheduleTask\Entity::METHOD                     => $scheduleTask->getAttribute(ScheduleTask\Entity::METHOD),
                    Schedule\Entity::DELAY                          => $scheduleTask->schedule->getAttribute(Schedule\Entity::DELAY),
                    ScheduleTask\Entity::INTERNATIONAL              => $scheduleTask->getAttribute(ScheduleTask\Entity::INTERNATIONAL),
                ]);

                return true;
            });

            $methodOfPayments = [
                null,
                Payment\Method::EMANDATE,
                Payment\Method::EMI,
                Payment\Method::CARD,
                Payment\Method::UPI,
                Payment\Method::BANK_TRANSFER,
                Payment\Method::WALLET,
                Payment\Method::NETBANKING,
            ];

            $newSettlementSchedules = array();
            $response = array();

            $response['config']['features'] = null;

            foreach ($scheduleTasks as $schedule)
            {

                $scheduleMethod = $schedule[scheduleTask\Entity::METHOD];

                //Setting schedule name, eg: name = 'T+7 Working days', where delay=7
                $ScheduleName = 'T+' . $schedule[Schedule\Entity::DELAY] . ' Working days';

                if(in_array($scheduleMethod, $methodOfPayments) === true)
                {
                    if($schedule[scheduleTask\Entity::INTERNATIONAL] === 0)
                    {
                        if($scheduleMethod === null)
                        {
                            $method = SettlementServiceMigration::PREFIX_DOMESTIC . SettlementServiceMigration::DEFAULT_CONST;
                        }
                        else
                        {
                            $method = SettlementServiceMigration::PREFIX_DOMESTIC . $scheduleMethod ;
                        }

                        $newSettlementSchedules['payment'][$method] = $ScheduleName;
                    }
                    else
                    {
                        if($scheduleMethod === null)
                        {
                            $method =  SettlementServiceMigration::PREFIX_INTERNATIONAL. SettlementServiceMigration::DEFAULT_CONST;
                        }
                        else
                        {
                            $method = SettlementServiceMigration::PREFIX_INTERNATIONAL . $scheduleMethod ;
                        }

                        $newSettlementSchedules['payment'][$method] = $ScheduleName;
                    }

                }
                else
                {
                    $newSettlementSchedules[$scheduleMethod][SettlementServiceMigration::DEFAULT_CONST] = $ScheduleName;
                }
            }

            foreach ($newSettlementSchedules as $type => $methods)
            {
                foreach ($methods as $method => $scheduleName)
                {
                    $response['config']['schedules'][$type][$method] = $scheduleName;
                }
            }
            return $response;
        }

        // if merchant is on NSS, Fetch data from there itself
        $input['merchant_id']=$mid;
        return app('settlements_merchant_dashboard')->merchantDashboardConfigGet($input);
    }

    public function merchantConfigCreate(array $input) : array
    {
        return app('settlements_dashboard')->merchantConfigCreate($input);
    }

    public function merchantConfigUpdate(array $input) : array
    {
        return app('settlements_dashboard')->merchantConfigUpdate($input);
    }

    public function orgConfigGet(array $input) : array
    {

        (new Validator)->validateInput('org_config_get', $input);

        $input['org_id'] = Entity::stripDefaultSign($input['org_id']);

        try
        {
            $resp = app('settlements_dashboard')->orgConfigGet($input);
        }
        catch(\Throwable $e)
        {
            if ($e->getMessage() == 'record_not_found: record not found')
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR,null, null,
                    $e->getMessage());
            }

            throw $e;
        }

        return $resp;
    }

    public function orgConfigCreateOrUpdate(array $input) : array
    {
        (new Validator)->validateInput('org_config_create', $input);

        $input['org_id'] = Entity::stripDefaultSign($input['org_id']);

        return app('settlements_dashboard')->orgConfigCreateOrUpdate($input);
    }

    public function merchantConfigBulkUpdate(array $input) : array
    {
        return app('settlements_dashboard')->merchantConfigBulkUpdate($input);
    }

    public function merchantConfigGetScheduleableEntities(): array
    {
        return app('settlements_dashboard')->merchantConfigGetScheduleableEntities();
    }

    public function bankAccountCreate(array $input) : array
    {
        return app('settlements_dashboard')->bankAccountCreate($input);
    }

    public function bankAccountUpdate(array $input) : array
    {
        return app('settlements_dashboard')->bankAccountUpdate($input);
    }

    public function bankAccountGet(array $input) : array
    {
        return app('settlements_dashboard')->bankAccountGet($input);
    }

    public function bankAccountDelete(array $input) : array
    {
        return app('settlements_dashboard')->bankAccountDelete($input);
    }

    public function scheduleCreate(array $input) : array
    {
        return app('settlements_dashboard')->scheduleCreate($input);
    }

    public function scheduleGet(array $input) : array
    {
        return app('settlements_dashboard')->scheduleGet($input);
    }

    public function scheduleRename(array $input) : array
    {
        return app('settlements_dashboard')->scheduleRename($input);
    }

    public function scheduleGetIds(array $input) : array
    {
        return app('settlements_dashboard')->scheduleGetIds($input);
    }

    public function executionReminder(array $input) : array
    {
        return app('settlements_reminder')->executionReminder($input);
    }

    public function executionRegister(array $input) : array
    {
        return app('settlements_dashboard')->executionRegister($input);
    }

    public function setDCSObject(array $input) : array
    {
        return app('settlements_dashboard')->setDCSObject($input);
    }

    public function executionTriggerMultiple(array $input) : array
    {
        return app('settlements_dashboard')->executionTriggerMultiple($input);
    }

    public function triggerReport(array $input) : array
    {
        return app('settlements_dashboard')->triggerReport($input);
    }

    public function transferStatusUpdate(array $input) : array
    {
        return app('settlements_dashboard')->transferStatusUpdate($input);
    }

    public function migrateToPayout(array $input) : array
    {
        return app('settlements_dashboard')->migrateToPayout($input);
    }

    public function executionResume(array $input) : array
    {
        return app('settlements_dashboard')->executionResume($input);
    }

    /**
     * RSR-2204 - bulk register reminder service
     * for created execution in CREATED
     * @param array $input
     * @return array
     */
    public function bulkRegisterReminder(array $input) : array
    {
        return app('settlements_dashboard')->bulkRegisterReminder($input);
    }

    public function bulkRegisterEntitySchedulerReminder(array $input) : array
    {
        return app('settlements_dashboard')->bulkRegisterEntitySchedulerReminder($input);
    }

    public function entitySchedulerTriggerMultiple(array $input) : array
    {
        return app('settlements_dashboard')->entitySchedulerTriggerMultiple($input);
    }

    public function transactionHold(array $input) : array
    {
        return app('settlements_dashboard')->transactionHold($input);
    }

    public function transactionRelease(array $input) : array
    {
        return app('settlements_dashboard')->transactionRelease($input);
    }

    public function channelStatusUpdate(array $input) : array
    {
        return app('settlements_dashboard')->channelStatusUpdate($input);
    }

    public function settlementRetry(array $input) : array
    {
        return app('settlements_dashboard')->settlementRetry($input);
    }

    public function getChannelState() : array
    {
        return app('settlements_dashboard')->getChannelState();
    }

    public function getSettlementServiceEntityFile($input) : array
    {
        return app('settlements_dashboard')->getSettlementServiceEntityFile($input);
    }

    public function checkForEntityAlerts($input) : array
    {
        return app('settlements_api')->checkForEntityAlerts($input);
    }

    public function initiateInterNodalTransfer($input) : array
    {
        return app('settlements_api')->initiateInterNodalTransfer($input);
    }

    public function migrateConfigurations(array $input)
    {
        if(isset($input['migrate_bank_account']) === true)
        {
            $input['migrate_bank_account'] = ($input['migrate_bank_account'] === '1');
        }

        if(isset($input['migrate_merchant_config']) === true)
        {
            $input['migrate_merchant_config'] = ($input['migrate_merchant_config'] === '1');
        }

        (new Validator)->validateInput('settlements_service_migration', $input);

        return (new Core)->migrateConfigurations($input);
    }

    public function migrateBlockedTransactions(array $input)
    {
        (new Validator)->validateInput('settlements_service_blocked_migration', $input);

        $this->trace->info(
            TraceCode:: SETTLEMENT_SERVICE_BLOCK_TXN_MIGRATE_BEGIN,
            [
                'request data'   =>$input
            ]);

        $redis = $this->app['redis']->Connection('mutex_redis');
        $cached_id_exists= $redis->exists(self::SETTLEMENT_BLOCKED_TXN_OFFSET_MID_CACHE_KEY);
        $cached_merchant_id=null;

        if($cached_id_exists === true)
        {
            $cached_merchant_id = $redis->get(self::SETTLEMENT_BLOCKED_TXN_OFFSET_MID_CACHE_KEY);
        }

        $limit=$input['limit'];
        $offsetID = (isset($input['offset_id']) === false)? $cached_merchant_id : $input['offset_id'];

        if (isset($input['merchant_ids']) === true){
            $inputMerchantIDs = $input['merchant_ids'];
            $merchantsOnNSS = $this->repo->feature->getMerchantIdsHavingFeature(Constants::NEW_SETTLEMENT_SERVICE, $inputMerchantIDs);
            $merchantsTxnToMigrate = $merchantsOnNSS;
        }
        else{
            $allMerchants = $this->repo->merchant->fetchAllMids($offsetID, $limit);
            $merchantWithBlock = $this->repo->feature->getMerchantIdsHavingFeature(Constants::BLOCK_SETTLEMENTS, $allMerchants);
            $merchantsOnNSSWithBlock = $this->repo->feature->getMerchantIdsHavingFeature(Constants::NEW_SETTLEMENT_SERVICE, $merchantWithBlock);
            $merchantsTxnToMigrate = $merchantsOnNSSWithBlock;

            //Finding the last entry for next update. In case reaches end of table, shouldn't update cache value
            $newOffsetMid = (empty($allMerchants) === false) ? max($allMerchants) : "";

            if(empty($allMerchants) === true)
            {
                $this->trace->info(
                    TraceCode::BLOCKED_TXN_MIGRATE_CRON_FAILURE_NO_MID_TO_MIGRATE,
                    [
                        'message'  =>'no merchants IDs found to migrate anymore. Reached EOT probably',
                    ]);
            }

            if (empty($merchantsTxnToMigrate) === true)
            {
                $this->trace->info(
                    TraceCode::BLOCKED_TXN_MIGRATE_CRON_FAILURE_NO_MID_TO_MIGRATE,
                    [
                        'message'                             =>'no merchants IDs found to migrate in this range',
                        'offset_id'                           =>$offsetID,
                        'limit'                               =>$limit,
                        'all_possible_merchants'              =>$allMerchants
                    ]);

                if ((isset($input['offset_id'])===false) && (!empty($allMerchants)))
                {
                    $redis->set(self::SETTLEMENT_BLOCKED_TXN_OFFSET_MID_CACHE_KEY,$newOffsetMid);
                }

                return [];
            }
        }

        $input_constructed=[
            'merchant_ids'            =>$merchantsTxnToMigrate,
            'via'                     =>'payout',
            'to'                      =>$input['to'],
            'from'                    =>$input['from']
        ];

        $output = (new Core)->migrateBlockedTransactions($input_constructed);
        //only update cache when no issues occurred in migration, and migration was a success.Eg:In cases of timeouts, cache shouldn't be updated
        //dont update if end of table reached
        if ((isset($input['offset_id'])===false)      &&
            (!empty($allMerchants)) &&
            (isset($input['merchant_ids'])===false))
        {
            $redis->set(self::SETTLEMENT_BLOCKED_TXN_OFFSET_MID_CACHE_KEY, $newOffsetMid);
        }

        return $output;
    }

    public function cronRunMigrations(array $input)
    {
        (new Validator)->validateInput('settlement_bulk_migrations', $input);

        $this->trace->info(
            TraceCode:: SETTLEMENTS_CRON_MIGRATIONS_BEGIN,
            [
                'message'        =>'begin cron migrations' ,
                'request data'   =>$input
            ]);

        $redis = $this->app['redis']->Connection('mutex_redis');

        $cached_id_exists= $redis->exists(self::SETTLEMENT_OFFSET_MID_CACHE_KEY);

        $cached_merchant_id=null;

        if($cached_id_exists)
        {
            $cached_merchant_id = $redis->get(self::SETTLEMENT_OFFSET_MID_CACHE_KEY);
        }

        $limit=$input['limit'];

        $offsetID = (isset($input['offset_id']) === false)? $cached_merchant_id : $input['offset_id'];

        $allMerchants=$this->repo->merchant->fetchAllMids($offsetID, $limit);

        $merchantsToMigrate=$this->repo->feature->getMerchantIdsHavingFeature(Constants::NEW_SETTLEMENT_SERVICE, $allMerchants);

        $merchantsToMigrate= array_diff($allMerchants, $merchantsToMigrate);

        //Finding the last entry for next update. In case reaches end of table, shouldn't update cache value
        $newOffsetMid = (empty($allMerchants) === false) ? max($allMerchants) : "";

        if(empty($allMerchants) === true)
        {
            $this->trace->info(
                TraceCode::MERCHANT_MIGRATIONS_CRON_FAILURE_NO_MIDS_TO_MIGRATE,
                [
                    'message'  =>'no merchants IDs found to migrate anymore. Reached EOT probably',
                ]);
        }

        if (empty($merchantsToMigrate) === true)
        {
            $this->trace->info(
                TraceCode::MERCHANT_MIGRATIONS_CRON_FAILURE_NO_MIDS_TO_MIGRATE,
                [
                    'message'                             =>'no merchants IDs found to migrate in this range',
                    'offset_id'                           =>$offsetID,
                    'limit'                               =>$limit,
                    'all_possible_merchants'              =>$allMerchants,
                    'final_merchants_to_migrate'          =>$merchantsToMigrate,
                ]);

            if ((isset($input['offset_id'])===false) && (!empty($allMerchants)))
            {
                 $redis->set(self::SETTLEMENT_OFFSET_MID_CACHE_KEY,$newOffsetMid);
            }

            return [];
        }

        $input_constructed=[
            'merchant_ids'            =>$merchantsToMigrate,
            'migrate_bank_account'    =>'1',
            'migrate_merchant_config' =>'1',
            'via'                     =>'payout'
        ];

        $this->trace->info(
            TraceCode::MERCHANT_MIGRATIONS_CRON_MIGRATE_MIDs,
            [
                'message'                    =>'actual migration starts',
                'time'                       =>time(),
                'all_possible_merchants'     =>$allMerchants,
                'final_merchants_to_migrate' =>$merchantsToMigrate,
            ]);

        $output= $this->migrateConfigurations($input_constructed);

        //only update cache when no issues occurred in migration, and migration was a success.Eg:In cases of timeouts, cache shouldn't be updated
        //dont update if end of table reached
        if ((isset($output['total'])=== true)         &&
            (isset($output['failed_count'])===true)   &&
            (isset($input['offset_id'])===false)      &&
            (!empty($allMerchants)))
        {
            $redis->set(self::SETTLEMENT_OFFSET_MID_CACHE_KEY,$newOffsetMid);
        }

        return $output;
    }


    public function replaySettlementsStatusUpdate(array $input)
    {
        (new Validator)->validateInput('settlements_status_replay', $input);

        return app('settlements_dashboard')->replaySettlementsStatusUpdate($input);
    }

    public function getSettlementSmsNotificationStatus($merchant = null)
    {
        $notificationMerchant = $this->merchant;

        if(isset($merchant) === true)
        {
            $notificationMerchant = $merchant;
        }

        $merchantId = $notificationMerchant->getId();

        $result = $this->repo->feature->getMerchantIdsHavingFeature(
            Constants::SETTLEMENTS_SMS_STOP,
            [
                $merchantId
            ]);

        if (empty($result) === false)
        {
            return ['enabled' => false];
        }

        return ['enabled' => true];
    }

    public function toggleSettlementSmsNotification(array $input)
    {
        (new Validator)->validateInput('settlement_sms_notification', $input);

        $status = $this->getSettlementSmsNotificationStatus();

        $merchantId = $this->merchant->getId();

        if($input['enable'] === true)
        {
            if($status['enabled'] === true)
            {
                return ['enabled' => true];
            }

            try
            {
                (new Merchant\Service)->addOrRemoveMerchantFeatures([
                    'features' => [
                        Constants::SETTLEMENTS_SMS_STOP => 0,
                    ],
                    Feature\Entity::SHOULD_SYNC => true,
                ]);

                $this->sendSelfServeSuccessAnalyticsEventToSegmentForEnablingSms();
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::SETTLEMENT_SMS_NOTIFY_TOGGLE_FAILED,
                    [
                        'merchant_id'       => $merchantId,
                        'current_status'    => $status['enabled'],
                        'requested_status'  => $input['enable'],
                        ]);

                return ['enabled' => false];
            }

            return ['enabled' => true];
        }


        if($status['enabled'] === false)
        {
            return ['enabled' => false];
        }

        try
        {
            (new Merchant\Service)->addOrRemoveMerchantFeatures([
                'features' => [
                    Constants::SETTLEMENTS_SMS_STOP => 1,
                ],
                Feature\Entity::SHOULD_SYNC => true,
            ]);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::SETTLEMENT_SMS_NOTIFY_TOGGLE_FAILED,
                [
                    'merchant_id'       => $merchantId,
                    'current_status'    => $status['enabled'],
                    'requested_status'  => $input['enable'],
                ]);

            return ['enabled' => true];
        }

        return ['enabled' => false];
    }

    public function settlementsLedgerInconsistencyDebug($input)
    {

        $from = Carbon::now(Timezone::IST)->subMonth()->getTimestamp();

        $to = Carbon::now(Timezone::IST)->getTimestamp();

        // increasing allowed system limit
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(3600);

        $startTime = microtime(true);

        $this->trace->info(
            TraceCode::SETTLEMENT_DEBUGGING_FRAMEWORK_REQUEST,
            [
                'input' => $input,
            ]);

        if ((isset($input['from']) === true) and (isset($input['to']) === true))
        {
            $from = $input['from'];

            $to = $input ['to'];
        }

        $merchantIds = [];

        if (empty($input['merchant_ids']) === false)
        {
            $merchantIds = $input['merchant_ids'];
        }

        $input['from'] = $from;
        $input['to'] = $to;

        $response = app('settlements_api')->ledgerReconCronTrigger($input, $this->mode);

        $this->trace->info(
            TraceCode::SETTLEMENT_DEBUGGING_FRAMEWORK_PUSH_COMPLETE,
            [
                'count'      => count($merchantIds),
                'response'   => $response,
                'input'      => $input,
                'time_taken' => get_diff_in_millisecond($startTime),
            ]);

        return [
            'enqueued_mid_count' => count($merchantIds),
            'time_taken'         => get_diff_in_millisecond($startTime)
        ];
    }

    protected function addLedgerCronExecution($triggeredBy, $merchantCount = null) : string
    {
        $cronExecutionInput = [
            'status'    => self::LEDGER_RECON_STATE_PROCESSING,
            'triggered_by'  => $triggeredBy,
        ];

        if(isset($merchantCount) === true)
        {
            $cronExecutionInput['merchant_count'] = $merchantCount;
        }

        $response = app('settlements_api')->ledgeCronExecutionAdd($cronExecutionInput, $this->mode);

        $this->trace->info(
            TraceCode::LEDGER_CRON_EXECUTION_ADD,
            [
                'input' =>    $cronExecutionInput,
                'response'  => $response,
            ]);

        return $response['id'];
    }

    protected function dispatchForLedgerDiscrepancyCheck(array $merchantIds, string $cronId, bool $setBaseLineZero = false)
    {
        foreach ($merchantIds as $merchantId)
        {
            LedgerRecon::dispatch($this->mode, $merchantId, $cronId, null, $setBaseLineZero);
        }
    }

    protected function fetchAndDispatchLedgerCronActiveMTUs($cronId) : array
    {
        $merchantIds = [];

        $skip = 0;
        $limit = 100;

        $input = [
            'entity_name'   => 'ledger_recon_mtu',
            'filter'        => [
                'active_discrepancy' => true,
            ],
        ];

        do
        {
            $input['pagination']['limit'] = $limit;

            $input['pagination']['skip'] = $skip;

            $result = app('settlements_dashboard')->fetchMultiple($input);

            $ledgerReconMTUs = $result['entities']['ledger_recon_mtus'];

            $count = count($ledgerReconMTUs);

            $skip += $count;

            foreach ($ledgerReconMTUs as $ledgerReconMTU)
            {
                $merchantId = $ledgerReconMTU['merchant_id'];

                $merchantIds[] = $merchantId;

                LedgerRecon::dispatch($this->mode, $merchantId, $cronId, (int) $ledgerReconMTU['baseline_discrepancy']);
            }

        } while($limit === $count);

        $this->ledgerCronExecutionUpdate($cronId, null, count($merchantIds));

        return $merchantIds;
    }

    protected function ledgerCronExecutionUpdate($cronId, $status = null, $merchantCount = null)
    {
        if((empty($status) === true) and (empty($merchantCount) === true))
        {
            return;
        }

        $cronExecutionUpdateInput = [
            "cron_id"   => $cronId
        ];

        if(empty($status) === false)
        {
            $cronExecutionUpdateInput['status'] = $status;
        }

        if(empty($merchantCount) === false)
        {
            $cronExecutionUpdateInput['merchant_count'] = $merchantCount;
        }

        app('settlements_api')->ledgeCronExecutionUpdate($cronExecutionUpdateInput);
    }

    public function updateBeneName($input)
    {
        return app('settlements_dashboard')->updateBeneName($input);
    }

    /**
     * execute optimizer settlements
     * @param array $input
     * @return array
     */
    public function optimizerExternalSettlementsExecute(array $input) : array
    {
        return app('settlements_api')->optimizerExternalSettlementsExecute($input);
    }

    public function settlementsInitiate($input)
    {
        return app('settlements_dashboard')->settlementsInitiate($input);
    }

    /**
     * manual api to execute optimiser settlement cron
     * @param $input
     * @return array
     */
    public function optimizerExternalSettlementsManualExecute($input) : array
    {
        return app('settlements_api')->optimizerExternalSettlementsManualExecute($input);
    }

    public function createFetchInput($id)
    {
        $id = $this->repo->settlement->verifyIdAndStripSign($id);

        return [
            'id' => $id,
            'entity_name' => 'settlement',
        ];
    }

    public function createFetchMultipleInput($input)
    {
        $fetchInput = [
            'entity_name' => 'settlement',
            'filter' => [
                'merchant_id' => $this->merchant->getId(),
            ],
        ];

        if(isset( $input['count']) )
        {
            $fetchInput['pagination']['limit'] = $input['count'];
        }

        if(isset( $input['skip']) )
        {
            $fetchInput['pagination']['skip'] = $input['skip'];
        }

        foreach ($input as $key => $val)
        {
            if ($key == 'count' || $key == 'skip') {
                continue;
            }
            if ($key == 'terminal_id') {
                $key = 'provider';
            }

            $fetchInput['filter'][$key] = $val;
        }

        return $fetchInput;
    }

    public function convertToCollection($settlements)
    {
        $collectionEntity = [];

        foreach($settlements['entities']['settlements'] as $settlementsEntity)
        {
            $setl = new Settlement\Entity($settlementsEntity);

            $setl->setPublicAttributeForOptimiser($settlementsEntity);

            array_push($collectionEntity, $setl);
        }

        $collection = collect($collectionEntity);

        return new PublicCollection($collection);
    }

    public function createFetchMultipleTxnInput($id, $input, $sourceId)
    {
        $fetchInput = [
            'settlementId' => $id,
            'merchantId' => $this->merchant->getId(),
            'sourceType' => $input['source_type'],
            'sourceId'   => $sourceId,
        ];

        if(isset( $input['limit']) )
        {
            $fetchInput['pagination']['limit'] = $input['limit'];
        }

        if(isset( $input['skip']) )
        {
            $fetchInput['pagination']['skip'] = $input['skip'];
        }

        return $fetchInput;
    }

    public function convertToTxnCollection($txns)
    {
        $collectionEntity = [];

        foreach($txns['entities']['transactions'] as $txn)
        {
            $entity = $this->setPublicTxnAttributes($txn);

            array_push($collectionEntity, $entity);
        }

        $collection = collect($collectionEntity);

        return new PublicCollection($collection);
    }

    public function setPublicTxnAttributes($txn)
    {
        $entity = new Transaction\Entity($txn);

        if (isset($txn['Id']))
            $entity->setId($txn['Id']);

        if (isset($txn['Fee']))
            $entity->setFee($txn['Fee']);

        if (isset($txn['Tax']))
            $entity->setTax($txn['Tax']);

        if (isset($txn['Credit']) && $txn['Credit'] > 0 )
        {
            $entity->setCredit($txn['Credit']);
            $entity->setAmount($txn['Credit']);
        }

        if (isset($txn['Debit']) && $txn['Debit'] > 0)
        {
            $entity->setDebit($txn['Debit']);
            $entity->setAmount($txn['Debit']);
        }

        if (isset($txn['SourceId']))
            $entity->setEntityId($txn['SourceId']);

        if (isset($txn['SourceType']))
            $entity->setType($txn['SourceType']);

        if (isset($txn['CreatedAt']))
        {
            $entity->setCreatedAt($txn['CreatedAt']);
        }
        else
        {
            $entity->setCreatedAt(time());
        }

        return $entity;
    }

    public function settlementsAmountCheck($input)
    {
        $this->trace->info(
            TraceCode::SETTLEMENTS_AMOUNT_CHECK_REQUEST,
            $input
        );

        if($this->auth->isOptimiserDashboardRequest() === false)
        {
            return [
                'settlementAmount'          => 0,
                'totalTransactionAmount'    => 0,
            ];
        }

        $id = $this->repo->settlement->verifyIdAndStripSign($input['settlement_id']);

        $fetchInput = [
            'id' => $id,
            'entity_name' => 'settlement',
        ];

        $settlement = app('settlements_dashboard')->fetch($fetchInput);

        $details = json_decode($settlement['entity']['details'], true);

        $settlementAmount = $settlement['entity']['amount']/100;

        $totalTransactionAmount = $settlementAmount;

        foreach ($details as  $component => $detail )
        {
            if ($component === 'external')
            {
                $totalTransactionAmount = $totalTransactionAmount - $detail['amount']/100;
            }
        }

        return [
            'settlementAmount'          => $settlementAmount,
            'totalTransactionAmount'    => $totalTransactionAmount
        ];
    }

    private function getMerchantIdsFromAccessMaps($input)
    {
        $data = [];
        foreach($input as $accessMap)
        {
            array_push($data, $accessMap->getMerchantId());
        }
        return $data;
    }

    private function sendSelfServeSuccessAnalyticsEventToSegmentForEnablingSms()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'SMS Notifications Enabled';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    public function readCustomSettlementsFile(array $input)
    {
        $this->trace->info(
            TraceCode::LAMBDA_REQUEST,
            [
                'input'      => $input,
            ]
        );

        $req = [
            'bucket_name' => $input['bucket'],
            'file_name'   => $input['key'],
        ];

       return app('settlements_api')->forwardCustomSettlementFileReadRequest($req, $this->mode);
    }

    public function processPosFile(array $input, $batchType): array
    {
        $this->trace->info(
            TraceCode::LAMBDA_REQUEST,
            [
                'input'      => $input,
                'batch_type' => $batchType,
            ]
        );

        Batch\Type::validateType($batchType);

        $fileDetails = $this->getFileDetails($input, $batchType);

        $batchCore = new Batch\Core;

        if (isset($fileDetails['file_path']) === true)
        {
            $file = new File($fileDetails['file_path']);

            $params = [
                Batch\Entity::TYPE          => $batchType,
                Batch\Entity::FILE          => $file,
            ];

            $sharedMerchant = $this->repo
                ->merchant
                ->findOrFailPublic(Account::SHARED_ACCOUNT);

            $batch = $batchCore->create($params, $sharedMerchant);

            return $batch->toArrayPublic();
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid input, cannot process batch');
        }
    }

    protected function getFileDetails(array & $input)
    {
        $fileProcessor = new FileProcessor;

        try {
            if (isset($input['key']) === true)
            {
                $key = urldecode($input['key']);

                // Adding this to migrate the lambdas to indian region bucket
                // with old lambda bucket and region was not being passed
                // thus added this step to pass the bucket and region along with the new lambda
                // keeping the following config in order to support both the lmbdas old and new
                // to ease the migration process

                $bucketConfig = 'h2h_bucket';
                $bucketRegion = null;

                if(empty($input['bucket']) === false)
                {
                    //Chakra sends actual bucket name, API uses bucket config name
                    //Get the bucket config name from AWS config
                    $config =  \Config::get('aws');

                    foreach ($config as $bucket => $bucketName)
                    {
                        if ($bucketName === $input['bucket'])
                        {
                            $bucketConfig = $bucket;
                            break;
                        }
                    }

                }

                if (empty($input['region']) === false)
                {
                    $bucketRegion = $input['region'];
                }

                $filePath = $this->getH2HFileFromAws($key, true, $bucketConfig, $bucketRegion);

                $file = new File($filePath);

                $filesDetails = $fileProcessor->getFileDetails($file, FileProcessor::STORAGE);

                $this->trace->info(TraceCode::LAMBDA_FILE_DETAILS, ["filePath" => $filePath, 'fileDetails' => $filesDetails]);
            }
            else if (isset($input['file']) === true)
            {
                $filesDetails = $fileProcessor->getFileDetails($input['file'], FileProcessor::STORAGE);
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'invalid input, either bucket key or uploaded file is required');
            }
        }
        catch (\Exception $e)
        {
            throw $e;
        }

        return $filesDetails;
    }

    public function createPosSettlement(array $input): array
    {
        $externalTxns = [];

        $merchantId = false;

        try{
            foreach ($input as $key => $row) {

                if ($merchantId === false)
                {
                    $terminal = $this->repo->terminal->findByGatewayMerchantId($row['merchant_id'], 'hdfc_ezetap');

                    if (isset($terminal) === false){
                        return ['failure_reason' => 'Merchant not onboarded'];
                    }

                    $merchantId = $terminal['merchant_id'];
                }

                $row['source_type'] = 'POS';

                $externalTxns[] = [
                    'merchant_id' => $merchantId,
                    'source_id' => $row['source_id'],
                    'gateway_transaction_id' => $row['source_id'],
                    'total_amount' => ((float) $row['total_amount']) * 100,
                    'utr' => $row['utr'],
                    'gateway_settled_at' => strtotime($row['gateway_settled_at']),
                    'meta' => $row
                ];
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::BATCH_FILE_PROCESSING_ERROR,
                [
                    'input' => $input,
                ]);

            return $input;
        }

        $payload['pos_transactions'] = $externalTxns;

        $response = app('settlements_api')->posTransactionsAdd($payload);

        return $input;

    }

    /**
     * manual api to insert external transaction records for single recon
     * @param $input
     * @return array
     */
    public function insertExternalTransactionRecord($input) : array
    {
        return app('settlements_api')->insertExternalTransactionRecord($input);
    }

    /**
     * manual api to update transaction count in optimiser execution table for single recon
     * @param $input
     * @return array
     */
    public function updateTransactionCountOfExecution($input) : array
    {
        return app('settlements_api')->updateTransactionCountOfExecution($input);
    }

    /**
     * manual api to update status in optimiser execution table for single recon
     * @param $input
     * @return array
     */
    public function updateStatusofOptimiserExecution($input) : array
    {
        return app('settlements_api')->updateStatusofOptimiserExecution($input);
    }
}
