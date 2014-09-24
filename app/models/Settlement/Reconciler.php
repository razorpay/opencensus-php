<?php

namespace Models\Settlement;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Card;
use Models\Gateway;
use Models\Transaction;
use Models\Merchant;
use Models\Pricing;
use Models\Payment;

class Reconciler
{
    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    /**
     * It's set to tomorrow's timestamp if default is null
     * The default value can be changed during testing
     * @var int
     */
    public static $settledAt = null;

    protected $txn;
    protected $merchant;
    protected $payment;
    protected $card;

    public function __construct()
    {
        $this->reconciledAt = time();

        $this->feeCalculator = new Pricing\Fee;

        $this->queue = \Queue::getFacadeRoot();

        if (self::$settledAt === null)
        {
            $timestamp = Carbon::tomorrow('Asia/Kolkata')->timestamp;
            self::$settledAt = $timestamp;
        }

        $this->initRepos();
    }

    public function reconcile($mprData, $gateway)
    {
        $this->txnRepo->beginTransaction();

        try
        {
            $txns = $this->process($mprData, $gateway);

            $this->txnRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->txnRepo->rollback();

            (new SlackNotification)->queueOperationFailure('mpr_reconciliation', $e);

            throw $e;
        }

        $count = $txns->count();

        (new SlackNotification)->queueOperationSuccess('mpr_reconciliation', $count);

        return $txns;
    }

    protected function process($mprData, $gateway)
    {
        $txns = new Base\PublicCollection;

        foreach ($mprData as $row)
        {
            $txn = $this->reconcileMprRecord($row, $gateway);

            $txns->push($txn);
        }

        return $txns;
    }

    protected function reconcileMprRecord($mprRecord, $gateway)
    {
        $paymentId = Gateway::call('getPaymentId', $mprRecord, 'test');

        $txn = $this->newTransactionRecord();

        $entitiesArray = $this->loadEntities($paymentId);

        $params = array(
            'input' => $mprRecord,
            'transactionId' => $txn->getKey(),
            'entities' => $entitiesArray);

        $data = Gateway::call('reconcile', $params, 'test');

        $txn = $this->reconcileRecord($data);

        return $txn;
    }

    protected function reconcileRecord($data)
    {
        $txn = $this->txn;

        $entities = $this->entities;

        list($fee, $pricingRuleId) = $this->calculateMerchantFees();

        $this->updateCardNetworkAndCountry(
            $this->card,
            $data['card']['network'],
            $data['card']['country']);

        $amount = $this->payment->getAmount();
        $credit = $amount - $fee;

        $gatewayFee = $data['transaction']['gateway_fee'];
        $apiFee = $fee - $gatewayFee;

        $txnData = array(
            Transaction\Entity::AMOUNT => $amount,
            Transaction\Entity::GATEWAY_FEE => $data['transaction']['gateway_fee'],
            Transaction\Entity::MERCHANT_ID => $this->merchant->getKey(),
            Transaction\Entity::ENTITY_ID => $this->payment->getKey(),
            Transaction\Entity::ENTITY_TYPE => 'payment',
            Transaction\Entity::FEE => $fee,
            Transaction\Entity::CREDIT => $credit,
            Transaction\Entity::DEBIT => 0,
            Transaction\Entity::CURRENCY => 'INR',
            Transaction\Entity::PRICING_RULE_ID => $pricingRuleId,
            Transaction\Entity::API_FEE => $apiFee,
            Transaction\Entity::SETTLED_AT => self::$settledAt);

        $this->txn->fill($txnData);

        if ($this->checkPreviousEntries($txn, $this->txnRepo) === false)
        {
            $this->updateBalances($txn);

            $this->txnRepo->save($txn);
        }

        return $txn;
    }

    protected function updateCardNetworkAndCountry($card, $network, $country)
    {
        $card->setCountry($country);

        if (($network !== null) and
            ($network !== ''))
        {
            $card->setNetwork($network);
        }

        (new Card\Repository)->saveOrFail($card);
    }

    protected function calculateMerchantFees()
    {
        return $this->feeCalculator->calculateMerchantFees(
                    $this->merchant,
                    $this->card,
                    $this->payment->getAmount());
    }

    protected function updateBalances()
    {
        $merchantRepo = new Merchant\Repository();

        $nodalBalance = $merchantRepo->getEscrowBalanceLockForUpdate();
        $merchantBalance = $merchantRepo->getBalanceLockForUpdate($this->merchant->getKey());

        $merchantBalance->addAmount($this->txn['credit']);
        $merchantBalance->subAmount($this->txn['debit']);
        $merchantRepo->save($merchantBalance);

        $nodalBalance->addAmount($this->txn['api_fee']);
        $merchantRepo->save($nodalBalance);

        $this->txn[Transaction\Entity::BALANCE] = $merchantBalance->getBalance();
        $this->txn[Transaction\Entity::ESCROW_BALANCE] = $nodalBalance->getBalance();
    }

    protected function newTransactionRecord()
    {
        $txn = new Transaction\Entity;
        $txn->generateId();
        $txn->setReconciledAt($this->reconciledAt);

        $this->txn = $txn;
        $this->entities['transaction'] = $txn;

        return $txn;
    }

    protected function loadEntities($paymentId)
    {
        $payment  = (new Payment\Core)->retrieveById($paymentId);

        $this->merchant = $payment->merchant;
        $this->card = $payment->card;
        $this->terminal = $payment->merchant->terminal;
        $this->payment = $payment;

        return $entitiesArray = array(
            'payment' => $this->payment->toArray(),
            'merchant' => $this->merchant->toArray(),
            'card' => $this->card->toArray(),
            'terminal' => $this->terminal->toArray(),
            'transaction' => $this->txn->toArray());
    }

    protected function initRepos()
    {
        $this->txnRepo = new Transaction\Repository;
    }

    protected function checkPreviousEntries($curr, $repo)
    {
        $prev = $repo->findByEntityId($curr->entity_id);

        if ($prev === null)
        {
            return false;
        }

        $attrPrev = $prev->getAttributes();
        $attrCurr = $curr->getAttributes();

        $fields = array(
            'created_at', 'updated_at', 'balance', 'reconciled_at', 'settled_at', 'id', 'escrow_balance', 'settled');

        foreach ($fields as $field)
        {
            unset($attrPrev[$field]);
            unset($attrCurr[$field]);
        }

        $diff1 = array_diff_assoc($attrPrev, $attrCurr);
        $diff2 = array_diff_assoc($attrCurr, $attrPrev);

        $diff = false;
        $msg = '';

        if (count($diff1) > 0)
        {
            ob_start();
            print_r($diff1);
            $msg .= ob_get_clean() . PHP_EOL;
            $diff = true;
        }
        if (count($diff2) > 0)
        {
            ob_start();
            print_r($diff2);
            $msg .= ob_get_clean() . PHP_EOL;
            $diff = true;
        }

        if ($diff)
        {
            $msg = 'Entity: Transaction row' . PHP_EOL . $msg;
            $msg = 'Previous Transaction row do not match' . PHP_EOL . $msg;
            throw new Exception\LogicException($msg);
        }

        return true;
    }
}