<?php

namespace Models\Settlement;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Merchant;
use Models\Settlement;
use Models\Transaction;
use Dashboard\Dashboard;
use Trace\TraceCode;

class Settler
{
    protected $setlRepo;

    protected $txnRepo;

    protected $merchantRepo;

    protected $settlements;

    protected $input;

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

        $this->initRepos();
    }

    public function settle($input = array(), $channel = null)
    {
        $this->checkTime();

        $this->input = $input;

        $txns = $this->fetchTransactionsToSettle($input);

        $channels = $this->getArrayedChannels($channel);

        $data = [];

        if (Holidays::isTodayHoliday($this->mode))
        {
            return ['message' => 'Today is a holiday! Happy holidays :)'];
        }

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

    protected function settleForKotak($txns)
    {
        $this->setlRepo->beginTransaction();

        $data['channel'] = 'kotak';

        try
        {
            list($settlements, $txns, $amounts) = $this->process($txns, Channel::KOTAK);

            $urlText = '';

            $data['count'] = $settlements->count();
            $data['transaction_count'] = $txns->count();

            if ($settlements->count() !== 0)
            {
                list($urlText, $urlExcel) = $this->createSettlementFile($settlements, $txns);

                $this->updateDailySettlementAttributes(
                    $urlText,
                    $urlExcel,
                    $settlements->count(),
                    $txns->count());

                $data['settlement_text_file'] = $urlText;
                $data['settlement_excel_file'] = $urlExcel;
            }
            else
            {
                $data['message'] = 'No settlements found!';
            }

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            $this->settlementFailure('kotak', $e);
        }

        $this->successNotification($data, $settlements);

        return $data;
    }

    protected function settleForAtom($txns)
    {
        $this->setlRepo->beginTransaction();

        try
        {
            list($settlements, $txns, $amounts) = $this->process($txns, Channel::ATOM);

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

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

        $this->txnRepo->settled($txnsSettled, self::$settlementTimestamp);

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

        while ($i < $count)
        {
            // Settlement amount
            $setlAmount = $setlGatewayFee = $setlApiFee = $setlFee = 0;
            $setlTxns = new Base\PublicCollection;

            // Get merchant
            $merchantId = $txns[$i]->getMerchantId();
            $merchant = $this->merchantRepo->findOrFail($merchantId);

            while (($i < $count) and
                   ($txns[$i]->getMerchantId() === $merchantId))
            {
                $txn = $txns[$i];

                if ($this->shouldSettle($txn, $channel, $merchant) === false)
                {
                    $i++;
                    continue;
                }

                $setlAmount += $txn->getCredit() - $txn->getDebit();
                $setlGatewayFee += $txn->getGatewayFee();
                $setlApiFee += $txn->getApiFee();
                $setlFee += $txn->getFee();

                $setlTxns->push($txn);
                $i++;
            }

            if ($setlAmount <= 0)
            {
                $setlAmount = 0;
                continue;
            }

            $setl = (new Settlement\Merchant($merchant, $channel))->settle(
                                        $setlTxns, $setlAmount, $setlFee, $setlApiFee, $setlGatewayFee);

            $settlements->push($setl);
            $txnsSettled = $txnsSettled->merge($setlTxns);

            $totalSetlAmount += $setlAmount;
            $totalSetlApiFee += $setlApiFee;
            $totalSetlFee += $setlFee;
            $totalSetlGatewayFee += $setlGatewayFee;
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

        $amounts = array(
            'amount' => $totalSetlAmount,
            'api_fee' => $totalSetlApiFee,
            'gateway_fee' => $totalSetlGatewayFee,
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

    protected function shouldSettle(Transaction\Entity $txn, $channel, $merchant)
    {
        return (($txn->getChannel() === $channel) and
                ($merchant->holdFunds() === false));
    }

    protected function createSettlementFile($settlements, $txns)
    {
        $urls = (new Kotak\NodalAccount)->generateSettlementFile($settlements, $txns);

        $this->trace->info(TraceCode::SETTLEMENT_FILE_GENERATED_KOTAK);

        return $urls;
    }

    protected function collectApiFees($apiFee, $channel)
    {
        if ($channel !== Settlement\Channel::KOTAK)
        {
            throw new Exception\LogicException('Not valid channel: ' . $channel);
        }

        $feeAccount = $this->merchantRepo->findOrFail(Merchant\Account::API_FEE_ACCOUNT);

        list($setl, $adjTxn) = (new Settlement\Merchant($feeAccount, $channel))->collectApiFees($apiFee);

        return [$setl, $adjTxn];
    }

    protected function fetchTransactionsToSettle($input)
    {
        $ts = $this->initSettlementTimestamp($input);

//        $all = $this->isInputValue($input, 'all', '1');

//        if ($all === true)
        {
            //
            // Fetch all txns whose expected settlement
            // time is less than now
            //
            $ts = time();

            $txns = $this->txnRepo->fetchUnsettledTransactions($ts);
        }
        // else
        // {
        //     $txns = $this->txnRepo->fetchTxnsExpectedToSettle($ts);
        // }

        foreach ($txns as $txn)
        {
            if ($txn->isTypePayment())
            {
                $payment = $txn->entity;
            }
            else if ($txn->getType() === Transaction\Type::REFUND)
            {
                $payment = $txn->entity->payment;
            }
        }

        return $txns;
    }

    protected function initRepos()
    {
        $this->setlRepo = new Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->merchantRepo = new Merchant\Repository;
        $this->dailySetlRepo = new Settlement\Daily\Repository;
    }

    protected function initSettlementTimestamp($input)
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
        $force = $this->isInputValue($input, 'force', '1');

        $overwrite = $this->isInputValue($input, 'overwrite', '1');

        $dailySettlement = $this->dailySetlRepo->getSettlementForToday('kotak');

        if ($dailySettlement !== null)
        {
            if ($force === false)
            {
                $data['message'] = 'Settlement already done for today!';

                $dailySettlement = null;

                return $data;
            }
            else
            {
                $this->dailySettlement = $dailySettlement;
            }
        }

        if ($overwrite === false)
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
                'Please settlements before 6 pm everyday');
        }
    }
}
