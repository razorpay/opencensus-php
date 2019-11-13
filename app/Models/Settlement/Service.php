<?php

namespace RZP\Models\Settlement;

use Cache;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\Adjustment;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Jobs\Settlement\Create;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Report\Types\BasicEntityReport;
use RZP\Models\Report\Types\SettlementReconReport;

class Service extends Base\Service
{

    public function getMerchantSettlementAmount($input)
    {
        // todo: response structure has to be finalized
        (new Validator)->validateInput('settlement_amount', $input);

        $balanceType = $input['balance_type'] ?? Balance\Type::PRIMARY;

        $balance = $this->merchant->getBalanceByType($balanceType);

        $response = [
            'balance'           => $balance->getBalance(),
            'settlement_amount' => 0,
        ];

        //
        // This will give wrong result for wealthy merchant on saturdays
        //
        list ($status, $data) = (new Processor)->isMerchantSettlementAllowed($this->merchant);

        if ($status === false)
        {
            return $response +[
                'no_settlement' =>  $data
            ];
        }

        $nextSettlementTime = (new Bucket\Core)->getNextSettlementTime($this->merchant, $balance);

        $settlementDetails = (new Core)->getMerchantSettlementAmount(
            $this->merchant,
            $balance,
            $nextSettlementTime);

        $response = array_merge($response, $settlementDetails);

        //
        // settlement amount should be atleast 1rs
        // and settlement amount shouldn't be more than the available balance
        //
        if ($response['settlement_amount'] < 100)
        {
            $response += [
                'no_settlement' => [
                    'caption' => 'Settlement might get skipped',
                    'reason'  => 'Settlement amount is less than 1 rupee'
                ],
            ];
        }
        else if ($response['settlement_amount'] > $balance->getBalance())
        {
            $response += [
                'no_settlement' => [
                    'caption' => 'Settlement might get skipped',
                    'reason'  => 'Settlement amount is more than the available live balance',
                ]
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
        $setl = $this->repo->settlement->findByPublicIdAndMerchant($id, $this->merchant);

        return $setl->toArrayPublic();
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
        $settlements = $this->repo->settlement->fetch($input, $this->merchant->getKey());

        return $settlements->toArrayPublic();
    }

    public function fetchSettlementTransactions($id)
    {
        $setl = $this->repo->settlement->findByPublicIdAndMerchant($id, $this->merchant);

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

    public function nextSettlementAmount()
    {
        $data = (new Settlement\Processor)->settlementAmount();

        return $data;
    }

    public function getProcessDetails(): array
    {
        $redis = $this->app['redis']->connection();

        $countKey            = sprintf(Create::TOTAL_MERCHANT_COUNT, $this->mode);
        $channelWiseCountKey = sprintf(Create::CHANNEL_WISE_COUNT, $this->mode);

        return [
            'pending_merchants'    => Cache::get($countKey),
            'channel_wise_process' => $redis->HGETALL($channelWiseCountKey),
        ];
    }

    public function resetProcessDetails()
    {
        $countKey            = sprintf(Create::TOTAL_MERCHANT_COUNT, $this->mode);
        $channelWiseCountKey = sprintf(Create::CHANNEL_WISE_COUNT, $this->mode);

        Cache::forget($countKey);

        $redis = $this->app['redis']->connection();

        $redis->del($channelWiseCountKey);

        return $this->getProcessDetails();
    }
}
