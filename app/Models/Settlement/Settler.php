<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Exception;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Dashboard\Dashboard;

use RZP\Trace\TraceCode;


class Settler
{
    protected $settlements;

    protected $input;

    protected $mutex;

    const HOLIDAY_MESSAGE       = ['message' => 'Today is a holiday! Happy holidays :)'];

    const MUTEX_RESOURCE        = 'SETTLMENT_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 900;

    /**
     * Used for testing purposes. Default should
     * be null.
     * @var integer
     */
    public static $settlementTimestamp = null;

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
        $this->preSettlementProcessing();

        $this->input = $input;

        if ($this->checkForHolidays())
        {
            return self::HOLIDAY_MESSAGE;
        }

        $txns = $this->fetchMerchantTransactionsToSettle($input, $merchant);

        $data = $this->mutex->acquireAndRelease(self::MUTEX_RESOURCE, function() use($input, $channel, $txns)
        {
            return $this->processSettlements($input, $channel, $txns);
        }, self::MUTEX_LOCK_TIMEOUT, ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    public function settle($input = array(), $channel = null)
    {
        $this->preSettlementProcessing();

        $this->input = $input;

        if ($this->checkForHolidays())
        {
            return self::HOLIDAY_MESSAGE;
        }

        $txns = $this->fetchTransactionsToSettle($input);

        $data = $this->mutex->acquireAndRelease(self::MUTEX_RESOURCE, function() use($input, $channel, $txns)
        {
            return $this->processSettlements($input, $channel, $txns);
        }, self::MUTEX_LOCK_TIMEOUT, ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    protected function preSettlementProcessing()
    {
        $this->increaseAllowedSystemLimits();

        $this->checkTime();
    }

    protected function processSettlements($input, $channel, $txns)
    {
        $channels = $this->getArrayedChannels($channel);

        $data = [];

        foreach ($channels as $channel)
        {
            $res = $this->getOrCreateDailySettlementForToday($input, $channel);

            if ($res !== null)
            {
                $data[$channel] = $res;
                continue;
            }

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
        $this->repo->beginTransaction();

        $data['channel'] = 'kotak';

        try
        {
            list($settlements, $txns, $amounts) = $this->process($txns, Channel::KOTAK);

            $urlText = '';

            $data['count'] = $settlements->count();
            $data['transaction_count'] = $txns->count();

            if ($settlements->count() !== 0)
            {
                $this->updateDailySettlementAttributes(
                    null,
                    null,
                    $settlements->count(),
                    $txns->count());
            }
            else
            {
                $data['message'] = 'No settlements found!';
            }

            $this->repo->commit();
        }
        catch (\Exception $e)
        {
            $this->repo->rollback();

            $this->settlementFailure('kotak', $e);
        }

        if ($settlements->count() !== 0)
        {
            list($urlText, $urlExcel) = $this->createSettlementFile($settlements, $txns);

            $data['settlement_text_file'] = $urlText;

            $data['settlement_excel_file'] = $urlExcel;
        }

        $this->successNotification($data, $settlements);

        return $data;
    }

    protected function settleForAtom($txns)
    {
        $this->repo->beginTransaction();

        try
        {
            list($settlements, $txns, $amounts) = $this->process($txns, Channel::ATOM);

            $this->repo->commit();
        }
        catch (\Exception $e)
        {
            $this->repo->rollback();

            $this->settlementFailure('atom', $e);
        }

        $this->trace->info(TraceCode::SETTLEMENT_ATOM_INITIATED_RECONCILED);

        $data['count'] = $settlements->count();
        $data['transaction_count'] = $txns->count();
        $data['channel'] = 'atom';

        $this->successNotification($data, $settlements);

        return $data;
    }

    protected function settlementFailure($channel, $e)
    {
        $e = new SettlementFailureException($channel, null, $e);

        $this->failureNotification($e);

        $this->trace->critical(TraceCode::SETTLEMENT_INITIATE_FAILED);

        throw $e;
    }

    protected function successNotification($data, $settlements)
    {
        $this->trace->info(TraceCode::SETTLEMENT_INITIATED, $data);

        (new SlackNotification)->success('setl_initiate', $data);

        Dashboard::send('settlement', $settlements);
    }

    protected function failureNotification($exception)
    {
        (new SlackNotification)->failure('setl_initiate', $exception);
    }

    protected function process($txns, $channel)
    {
        list($settlements, $txnsSettled, $amounts) = $this->createSettlements($txns, $channel);

        $this->repo->transaction->settled($txnsSettled, self::$settlementTimestamp);

        return array($settlements, $txnsSettled, $amounts);
    }

    protected function createSettlements($txns, $channel)
    {
        $this->dailySettlement->channel = $channel;

        $settlements = new Base\PublicCollection;
        $txnsSettled = new Base\PublicCollection;

        $i = 0;
        $count = $txns->count();
        $totalSetlAmount = 0;
        $totalSetlGatewayFee = 0;
        $totalSetlApiFee = 0;
        $totalSetlFee = 0;
        $totalServiceTax = 0;

        while ($i < $count)
        {
            // Settlement amount
            $setlAmount = $setlGatewayFee = $setlApiFee = 0;
            $setlFee = $serviceTax = 0;

            $setlTxns = new Base\PublicCollection;

            // Get merchant
            $merchantId = $txns[$i]->getMerchantId();
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            while (($i < $count) and
                   ($txns[$i]->getMerchantId() === $merchantId))
            {
                $txn = $txns[$i];

                if ($this->shouldSettle($txn, $channel, $merchant) === false)
                {
                    $i++;
                    continue;
                }

                if (($txn->getBalance() === 0) and
                    ($txn->isTypeRefund()))
                {
                    $payment = $txn->source->payment;

                    if ($payment->hasBeenCaptured() === false)
                    {
                        $this->trace->info(
                            TraceCode::TRANSACTION_REFUND_TRACE,
                            ['id' => $txn->getId()]);

                        $txn[Transaction\Entity::SETTLED_AT] = null;
                        $txn->saveOrFail();
                        $i++;
                        continue;
                    }
                }

                $setlAmount += $txn->getCredit() - $txn->getDebit();
                $setlGatewayFee += $txn->getGatewayFee();
                $setlApiFee += $txn->getApiFee();
                $setlFee += $txn->getFee();
                $serviceTax += $txn->getServiceTax();

                $setlTxns->push($txn);
                $i++;
            }

            //settle only if settlement amount is more than INR 1
            if ($setlAmount <= 100)
            {
                $setlAmount = 0;
                continue;
            }

            $setl = (new Settlement\Merchant($merchant, $channel, $this->repo))->settle(
                                        $setlTxns,
                                        $setlAmount,
                                        $setlFee,
                                        $setlApiFee,
                                        $setlGatewayFee,
                                        $serviceTax);

            $settlements->push($setl);
            $txnsSettled = $txnsSettled->merge($setlTxns);

            $totalSetlAmount += $setlAmount;
            $totalSetlApiFee += $setlApiFee;
            $totalSetlFee += $setlFee;
            $totalSetlGatewayFee += $setlGatewayFee;
            $totalServiceTax += $serviceTax;
        }

        if (($totalSetlApiFee !== 0) and
            ($channel === Settlement\Channel::KOTAK))
        {
            list($setl, $adjTxn) = $this->collectApiFees($totalSetlApiFee, $channel);

            $settlements->push($setl);
            $txns->push($adjTxn);
            $txnsSettled->push($adjTxn);

            $totalSetlAmount += $totalSetlApiFee;
        }

        $this->dailySettlement->amount = $totalSetlAmount;
        $this->dailySettlement->api_fee = $totalSetlApiFee;
        $this->dailySettlement->gateway_fee = $totalSetlGatewayFee;
        $this->dailySettlement->fees = $totalSetlFee;
        $this->dailySettlement->service_tax = $totalServiceTax;

        $amounts = array(
            'amount'        => $totalSetlAmount,
            'fees'          => $totalSetlApiFee,
            'service_tax'   => $totalServiceTax,
            'api_fee'       => $totalSetlApiFee,
            'gateway_fee'   => $totalSetlGatewayFee,
        );

        return [$settlements, $txnsSettled, $amounts];
    }

    protected function updateDailySettlementAttributes($urlText, $urlExcel, $setlCount, $txnCount)
    {
        $urls = array();
        $urls['kotak_settlement_txt'] = $urlText;
        $urls['kotak_settlement_excel'] = $urlExcel;

        $dailySettlement = $this->dailySettlement;
        $dailySettlement->setUrls($urls);
        $dailySettlement->initiated_at = time();
        $dailySettlement->settlement_count = $setlCount;
        $dailySettlement->transaction_count = $txnCount;

        $dailySettlement->saveOrFail();
    }

    /**
     * Settlement is done only if funds are not on hold and bank account change
     * is not recent as we need some time till beneficiary is updated in kotak
     */
    protected function shouldSettle(Transaction\Entity $txn, $channel, $merchant)
    {
        $today = Carbon::today('Asia/Kolkata');

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        $shouldSettle = (($txn->getChannel() === $channel) and
                         ($merchant->holdFunds() === false));


        assert ($merchant->bankAccount !== null);

        // If merchant has a hourly schedule entity assigned to him, his settlements
        // will be handled by the new Settler defined in Settlement\Processor
        if ($merchant->hasSchedule() === true)
        {
            return false;
        }

        if (($this->mode !== Mode::TEST) and
            ($merchant->bankAccount->getCreatedAt() > $lastWorkingDay->timestamp))
        {
            $shouldSettle = false;
        }

        return $shouldSettle;
    }

    protected function createSettlementFile($settlements)
    {
        $urls = (new Kotak\NodalAccount)->generateSettlementFile($settlements);

        $this->trace->info(TraceCode::SETTLEMENT_FILE_GENERATED_KOTAK);

        return $urls;
    }

    protected function collectApiFees($apiFee, $channel)
    {
        if ($channel !== Settlement\Channel::KOTAK)
        {
            throw new Exception\LogicException('Not valid channel: ' . $channel);
        }

        $feeAccount = $this->repo->merchant->findOrFail(Merchant\Account::API_FEE_ACCOUNT);

        list($setl, $adjTxn) = (new Settlement\Merchant($feeAccount, $channel))->collectApiFees($apiFee);

        return [$setl, $adjTxn];
    }

    protected function fetchTransactionsToSettle($input)
    {
        $ts = $this->initSettlementTimestamp();

        $ts = time();

        if (($this->mode === Mode::TEST) and
            (empty($input['testSettleTimeStamp']) === false))
        {
            $ts = $input['testSettleTimeStamp'];
        }

        $txns = $this->repo->transaction->fetchUnsettledTransactions($ts);

        return $txns;
    }

    protected function fetchMerchantTransactionsToSettle($input, $merchant)
    {
        $ts = $this->initSettlementTimestamp();

        $txns = $this->repo->transaction->fetchUnsettledTransactionsForMerchant($ts, $merchant);

        return $txns;
    }

    protected function initSettlementTimestamp()
    {
        if (self::$settlementTimestamp === null)
        {
            // Get the timestamp today at 12 am
            $timestamp = Carbon::today('Asia/Kolkata')->timestamp;

            self::$settlementTimestamp = $timestamp;
        }

        return self::$settlementTimestamp;
    }

    protected function traceSetlInitiating($channel)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y H:i:s');

        $this->trace->info(
            TraceCode::SETTLEMENT_INITIATING,
            [
                'channel' => $channel,
                'timestamp' => self::$settlementTimestamp,
                'time' => $time,
            ]);
    }

    protected function getOrCreateDailySettlementForToday(array $input, $channel)
    {
        $overwrite = $this->isInputValue($input, 'overwrite', '1');

        if ($overwrite === true)
        {
            $this->dailySettlement = $this->repo->daily_settlement->getSettlementForToday('kotak');
        }
        else
        {
            $this->dailySettlement = Settlement\Daily\Entity::newForToday();
        }
    }

    protected function isInputValue(array $input, $key, $value)
    {
        if ((isset($input[$key])) and
            ($input[$key] === $value))
        {
            return true;
        }

        return false;
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

        $boundary = $sixPm - (5*60); // Subtract 5 mintues

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

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(300);
    }
}
