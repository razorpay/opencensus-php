<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Settlement\Batch\Entity as BatchSettlement;
use RZP\Trace\TraceCode;

class Settler
{
    use CommonTrait;

    protected $settlements;

    protected $batchSettlement;

    protected $input;

    protected $mutex;

    const HOLIDAY_MESSAGE       = ['message' => 'Today is a holiday! Happy holidays :)'];

    const MUTEX_RESOURCE        = 'SETTLEMENT_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 900;

    /**
     * Used for testing purposes. Default should
     * be null.
     * @var integer
     */
    // public static $settlementTimestamp = null;

    protected $setlTime = null;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];
        $this->env = $app['env'];
        $this->trace = $app['trace'];
        $this->repo = $app['repo'];
        $this->mutex = $app['api.mutex'];
    }

    public function settleForParticularMerchant($input, $merchant, $channel = null)
    {
        if ($this->checkForHolidays())
        {
            return self::HOLIDAY_MESSAGE;
        }

        $this->preSettlementProcessing();

        $this->input = $input;

        $txns = $this->fetchMerchantTransactionsToSettle($merchant);

        $data = $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function() use($channel, $txns)
            {
                return $this->processSettlements($channel, $txns);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    public function settle($input = array(), $channel = null)
    {
        if ($this->checkForHolidays())
        {
            return self::HOLIDAY_MESSAGE;
        }

        $this->preSettlementProcessing();

        $this->input = $input;

        $txns = $this->fetchTransactionsToSettle($input);

        $data = $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function() use($channel, $txns)
            {
                return $this->processSettlements($channel, $txns);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    protected function preSettlementProcessing()
    {
        $this->checkTime();

        $this->increaseAllowedSystemLimits();

        $this->setlTime ?: Carbon::now('Asia/Kolkata')->timestamp;
    }

    protected function processSettlements($channel, $txns)
    {
        $channels = $this->getArrayedChannels($channel);

        $data = [];

        foreach ($channels as $channel)
        {
            $this->traceSetlInitiating($channel);

            $settleForChannelVar = 'settleFor' . ucfirst($channel);

            $data[$channel] = $this->$settleForChannelVar($txns);
        }

        return $data;
    }

    protected function checkForHolidays()
    {
        // Settlement files to not be generated on Public Holidays
        // No public holiday in test mode
        $today = Carbon::today('Asia/Kolkata');

        if (($this->mode === Mode::LIVE) and
            (Holidays::isSpecifiedBankHoliday($today)))
        {
            return true;
        }

        // And on second saturdays due to bank leaves.
        // Marks as holiday in test mode as well
        if (($this->mode === Mode::LIVE) and
            ($today->dayOfWeek === Carbon::SATURDAY) and
            (Holidays::isWorkingSaturday($today) === false))
        {
            return true;
        }

        return false;
    }

    protected function settleForKotak($txns)
    {
        $data['channel'] = Channel::KOTAK;

        try
        {
            list($settlements, $txnsCount, $setlAttempts) = $this->process($txns, Channel::KOTAK);

            $data['count'] = $settlements->count();
            $data['transaction_count'] = $txnsCount;

            if ($settlements->count() === 0)
            {
                $data['message'] = 'No settlements found!';
            }
        }
        catch (\Exception $e)
        {
            $this->settlementFailure('kotak', $e, TraceCode::SETTLEMENT_INITIATE_FAILED);
        }

        if ($settlements->count() !== 0)
        {
            list($urlText, $urlExcel) = $this->createSettlementFile($setlAttempts);

            $data['settlement_text_file'] = $urlText;

            $data['settlement_excel_file'] = $urlExcel;

            $this->updateBatchSettlementEntityUrls($urlText, $urlExcel);
        }

        $this->successNotification($data, $settlements, TraceCode::SETTLEMENT_INITIATED);

        return $data;
    }

    protected function settleForAtom($txns)
    {
        $this->repo->beginTransaction();

        try
        {
            list($settlements, $txnsCount, $setlAttempts) = $this->process($txns, Channel::ATOM);

            $this->repo->commit();
        }
        catch (\Exception $e)
        {
            $this->repo->rollback();

            $this->settlementFailure('atom', $e, TraceCode::SETTLEMENT_INITIATE_FAILED);
        }

        $this->trace->info(TraceCode::SETTLEMENT_ATOM_INITIATED_RECONCILED);

        $data['count'] = $settlements->count();
        $data['transaction_count'] = $txnsCount;
        $data['channel'] = 'atom';

        $this->successNotification($data, $settlements, TraceCode::SETTLEMENT_INITIATED);

        return $data;
    }

    protected function process($txns, $channel): array
    {
        $txns = $this->filterTransactionsForSettlement($txns, $channel);

        list($settlements, $txnsSettledCount, $setlAttempts) =
            $this->createSettlementsFromTxns($txns, $channel);

        return [$settlements, $txnsSettledCount, $setlAttempts];
    }

    protected function createSettlementFile($setlAttempts)
    {
        $urls = (new Kotak\NodalAccount)->generateSettlementFile($setlAttempts);

        $this->trace->info(TraceCode::SETTLEMENT_FILE_GENERATED_KOTAK);

        return $urls;
    }

    protected function fetchTransactionsToSettle()
    {
        $ts = time();

        if (($this->mode === Mode::TEST) and
            (empty($this->input['testSettleTimeStamp']) === false))
        {
            $ts = $this->input['testSettleTimeStamp'];
        }

        $txns = $this->repo->transaction->fetchUnsettledTransactions($ts);

        return $txns;
    }

    protected function fetchMerchantTransactionsToSettle($merchant)
    {
        $txns = $this->repo
                     ->transaction
                     ->fetchUnsettledTransactionsForMerchant($this->setlTime, $merchant);

        return $txns;
    }

    protected function traceSetlInitiating($channel)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y H:i:s');

        $this->trace->info(
            TraceCode::SETTLEMENT_INITIATING,
            [
                'channel' => $channel,
                'timestamp' => $this->setlTime,
                'time' => $time,
            ]);
    }

    protected function createBatchSettlementEntity($setl, $txnsCount)
    {
        $batchSettlement = new BatchSettlement;

        $input = [
            BatchSettlement::CHANNEL           => $setl->getChannel(),
            BatchSettlement::AMOUNT            => $setl->getAmount(),
            BatchSettlement::FEES              => $setl->getFees(),
            BatchSettlement::SERVICE_TAX       => $setl->getServiceTax(),
            BatchSettlement::SETTLEMENT_COUNT  => 1,
            BatchSettlement::TRANSACTION_COUNT => $txnsCount,
            BatchSettlement::INITIATED_AT      => time(),
            BatchSettlement::API_FEE           => 0,
            BatchSettlement::GATEWAY_FEE       => 0,
            BatchSettlement::URLS              => null,
        ];

        $batchSettlement->build($input);

        return $batchSettlement;
    }

    protected function createOrUpdateBatchSettlementForSettlement($setl, $txnsCount)
    {
        if ($this->batchSettlement === null)
        {
            $this->batchSettlement = $this->createBatchSettlementEntity($setl, $txnsCount);
        }
        else
        {
            $this->batchSettlement->incrementAmount($setl->getAmount());
            $this->batchSettlement->incrementFees($setl->getFees());
            $this->batchSettlement->incrementServiceTax($setl->getServiceTax());
            $this->batchSettlement->incrementSettlementCount();
            $this->batchSettlement->incrementTransactionCount($txnsCount);
        }

        $this->repo->saveOrFail($this->batchSettlement);
    }

    protected function updateBatchSettlementEntityUrls($urlText, $urlExcel)
    {
        $batchSettlement = $this->batchSettlement;

        $urls = [
            'kotak_settlement_txt'   => $urlText,
            'kotak_settlement_excel' => $urlExcel
        ];

        $batchSettlement->setUrls($urls);

        $this->repo->saveOrFail($batchSettlement);
    }

    protected function getArrayedChannels($channel = null)
    {
        if ($channel === null)
        {
            $channels = Channel::getChannels();
        }
        else
        {
            $channels = [$channel];
        }

        return $channels;
    }

    /**
     * Settlement should happen before 6 pm otherwise not
     */
    protected function checkTime()
    {
        $mode = $this->mode;
        $env = $this->env;

        $sixPm = Carbon::today('Asia/Kolkata')->hour(18)->timestamp;

        $boundary = $sixPm - (5 * 60); // Subtract 5 mintues

        $now = time();

        $crossed = false;

        if ($now > $boundary)
        {
            $crossed = true;
        }

        if (($env === 'production') and
            ($mode === 'live') and
            ($crossed === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please do settlements before 6 pm everyday');
        }
    }
}
