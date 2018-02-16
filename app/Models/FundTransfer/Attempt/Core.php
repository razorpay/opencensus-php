<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Holidays;
use RZP\Models\FundTransfer\Batch\BatchFundTransferTrait;

class Core extends Base\Core
{
    use BatchFundTransferTrait;

    protected $mutex;

    const MUTEX_RESOURCE        = 'FUND_TRANSFER_PROCESSING';
    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Initiate bank transfers
     *
     * @param  array  $input
     * @param  string $channel
     * @return array
     */
    public function initiateFundTransfers(array $input, string $channel): array
    {
        // Temporary. Kotak should ideally be processing at least
        // IMPS payments on holidays as well, but they're currently
        // not doing that, and we're stopping this till they do.
        if (($this->mode !== Constants\Mode::TEST) and
            ($this->env !== 'testing') and
            (Holidays::isWorkingDay(Carbon::today(Constants\Timezone::IST)) === false))
        {
            return Holidays::HOLIDAY_MESSAGE;
        }

        return $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function() use($input, $channel)
            {
                return $this->processBankTransfers($input, $channel);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_FUND_TRANSFER_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * Takes an array of the reconciled rows as an input, each of them having 2 keys
     *   - entity
     *   - fire_webhook
     * Sends a webhook to notify the merchant about the settlement
     *
     * @param array $reconciledRows
     */
    public function notifyMerchantViaWebhook(array $reconciledRows)
    {
        $settlementCore = new Settlement\Core;

        foreach ($reconciledRows as $reconciledRow)
        {
            // Entity could be of class Settlement, Refund etc
            $entity = $reconciledRow['entity'];

            $fireWebhook = $reconciledRow['fire_webhook'];

            if ($fireWebhook === false)
            {
                continue;
            }

            // Allow only the settlement entities
            if ($entity->getEntityName() !== Constants\Entity::SETTLEMENT)
            {
                continue;
            }

            $settlementCore->triggerSettlementWebhook($entity);
        }
    }

    protected function processBankTransfers(array $input, string $channel): array
    {
        return $this->repo->transaction(function() use ($input, $channel)
        {
            (new Validator)->validateInput('initiate_fund_transfer', $input);

            $timestamp = Carbon::now(Constants\Timezone::IST)->getTimestamp();

            $purpose = $input[Entity::PURPOSE];

            $sourceType = $input[Entity::SOURCE_TYPE] ?? null;

            $attempts = $this->repo
                             ->fund_transfer_attempt
                             ->getCreatedAttemptsBeforeTimestamp(
                                $timestamp,
                                $purpose,
                                $sourceType,
                                $channel,
                                ['source']);

            $data[$channel] = $this->processFundTransferAttempts($channel, $attempts);

            return $data;
        });
    }

    protected function processFundTransferAttempts(string $channel, Base\PublicCollection $attempts): array
    {
        $count = $attempts->count();

        $data = ['channel' => $channel, 'count' => $count];

        if ($count === 0)
        {
            $data['message'] = 'No Attempts to process';

            return $data;
        }

        foreach ($attempts as $attempt)
        {
            $txnCount = $this->getTransactionsCount($attempt);

            $this->createOrUpdateBatchFundTransferForEntity($attempt->source, $txnCount);

            $attempt->batchFundTransfer()->associate($this->batchFundTransfer);

            $attempt->setStatus(Status::INITIATED);

            $attempt->source->batchFundTransfer()->associate($this->batchFundTransfer);

            $attempt->source->setStatus(Status::INITIATED);
        }

        $class = "RZP\\Models\\FundTransfer\\" . ucfirst($channel) . "\\NodalAccount";

        $fileEntity = (new $class)->generateFundTransferFile($attempts);

        $url = $fileEntity->getUrl();

        $urls = ['file' => $url];

        $fileDetails = $fileEntity->get();

        $this->updateFileDetailsInBatchFundTransferEntity(
            [
                'urls'          => $urls,
                'txt_file_id'   => $fileDetails['id'],
            ]);

        $this->saveEntitiesToDb($attempts);

        $data['file'] = $fileDetails;

        return $data;
    }

    protected function getTransactionsCount(Entity $attempt): int
    {
        $sourceType = $attempt->getSourceType();

        switch ($sourceType)
        {
            case Type::SETTLEMENT:

                $source = $attempt->source;

                return $source->setlTransactions->count();

            default:
                return 1;
        }
    }

    protected function saveEntitiesToDb(Base\PublicCollection $attempts)
    {
        foreach ($attempts as $attempt)
        {
            $this->repo->saveOrFail($attempt);

            $this->repo->saveOrFail($attempt->source);
        }
    }
}