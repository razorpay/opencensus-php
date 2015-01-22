<?php

namespace Models\Settlement\Mpr;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Card;
use Models\Gateway;
use Models\Merchant;
use Models\Payment;
use Models\Pricing;
use Models\Settlement;
use Models\Transaction;

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

    protected $transaction;
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

            (new Settlement\SlackNotification)->queueOperationFailure('mpr_reconciliation', $e);

            throw $e;
        }

        $data = ['txn_count' => $txns->count()];

        (new Settlement\SlackNotification)->queueOperationSuccess('mpr_reconciliation', $data);

        return $txns;
    }

    protected function process($mprData, $gateway)
    {
        $txns = new Base\PublicCollection;

        foreach ($mprData as $row)
        {
            $transaction = $this->reconcileMprRecord($row, $gateway);

            if ($transaction !== null)
            {
                $txns->push($transaction);
            }
        }

        return $txns;
    }

    protected function reconcileMprRecord($mprRecord, $gateway)
    {
        $paymentId = Gateway::call('hdfc', 'getPaymentId', $mprRecord, 'test');

        $entitiesArray = $this->loadEntities($paymentId);

        $params = array(
            'input' => $mprRecord,
            'transactionId' => $this->transaction->getKey(),
            'entities' => $entitiesArray);

        $data = Gateway::call('hdfc', 'reconcile', $params, 'test');

        $transaction = $this->reconcileRecord($data);

        return $transaction;
    }

    protected function reconcileRecord($data)
    {
        $transaction = $this->transaction;

        if ($transaction->isReconciled())
        {
            // @todo: trace this
            return;
        }

        $this->updateCardNetworkAndCountry(
            $this->card,
            $data['card']['network'],
            $data['card']['country']);

        $amount = $this->payment->getAmount();
        $fee = $this->payment->getAttribute(Transaction\Entity::FEE);
        $credit = $amount - $fee;

        $gatewayFee = $data['transaction']['gateway_fee'];
        $apiFee = $fee - $gatewayFee;

        $txnData = array(
            Transaction\Entity::GATEWAY_FEE => $data['transaction']['gateway_fee'],
            Transaction\Entity::API_FEE => $apiFee,
            Transaction\Entity::SETTLED_AT => self::$settledAt);

        $transaction->fill($txnData);
        $transaction->setReconciledAt($this->reconciledAt);

//        $this->updateBalances($transaction);

        $this->txnRepo->save($transaction);

        return $transaction;
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

    protected function updateBalances($txn)
    {
        return (new Transaction\Core)->updateBalances($txn);
    }

    protected function loadEntities($paymentId)
    {
        $payment  = (new Payment\Core)->retrieveById($paymentId);

        $this->merchant = $payment->merchant;
        $this->card = $payment->card;
        $this->terminal = $payment->merchant->terminal;
        $this->payment = $payment;
        $this->transaction = $payment->transaction;

        return $entitiesArray = array(
            'card'          => $this->card->toArray(),
            'payment'       => $this->payment->toArray(),
            'merchant'      => $this->merchant->toArray(),
            'terminal'      => $this->terminal->toArray(),
            'transaction'   => $this->transaction->toArray());
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