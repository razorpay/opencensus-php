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
        $app = \App::getFacadeRoot();
        $this->mode = $app['rzp.mode'];

        $this->reconciledAt = time();

        $this->feeCalculator = new Pricing\Fee;

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
        $entityInfo = Gateway::call(
                            'hdfc',
                            'getPaymentOrRefundId',
                            $mprRecord,
                            $this->mode);

        list($transaction, $entitiesArray) = $this->loadTransactionAndRelations($entityInfo);

        $params = array(
            'input' => $mprRecord,
            'transactionId' => $this->transaction->getId(),
            'entities' => $entitiesArray);

        $data = Gateway::call('hdfc', 'reconcile', $params, $this->mode);

        $transaction = $this->reconcileRecord($transaction, $data);

        return $transaction;
    }

    protected function reconcileRecord($transaction, $data)
    {
        if ($transaction->isReconciled())
        {
            // @todo: trace this
            return;
        }

        $amount = $transaction->getAmount();
        $fee = $transaction->getAttribute(Transaction\Entity::FEE);

        $txnData = [];

        if ($transaction->isTypePayment())
        {
            $this->updateCardDetail(
                $transaction->entity->card,
                $data['card']);

            $gatewayFee = $data['transaction']['gateway_fee'];
            $apiFee = $fee - $gatewayFee;

            $txnData = array(
                Transaction\Entity::GATEWAY_FEE => $data['transaction']['gateway_fee'],
                Transaction\Entity::API_FEE => $apiFee);

        }

        $txnData[Transaction\Entity::SETTLED_AT] = self::$settledAt;
        $transaction->fill($txnData);

        $transaction->setReconciledAt($this->reconciledAt);

        $this->txnRepo->save($transaction);

        return $transaction;
    }

    protected function updateCardDetail($card, $data)
    {
        if (isset($data['country']))
        {
            $card->setCountry($data['country']);
        }

        $card->setInternational($data['international']);

        $card->setTrivia($data['trivia']);

        (new Card\Repository)->saveOrFail($card);
    }

    protected function updateBalances($txn)
    {
        return (new Transaction\Core)->updateBalances($txn);
    }

    protected function loadTransactionAndRelations($data)
    {
        $array = [];

        if ($data['type'] === Transaction\Type::PAYMENT)
        {
            $array = $this->getPaymentAndRelations($data['id']);
        }
        else if ($data['type'] === Transaction\Type::REFUND)
        {
            $array = $this->getRefundAndRelations($data['id']);
        }

        return [$this->transaction, $array];
    }

    protected function getPaymentAndRelations($paymentId)
    {
        $payment  = (new Payment\Core)->retirevePaymentById($paymentId);

        $transaction = $payment->transaction;

        $terminal = $payment->terminal;
        $merchant = $transaction->merchant;
        $card = $payment->card;

        $transaction->entity()->associate($payment);

        $this->transaction = $transaction;

        return $array = array(
            'card'          => $card->toArray(),
            'payment'       => $payment->toArray(),
            'merchant'      => $merchant->toArray(),
            'terminal'      => $terminal->toArray(),
            'transaction'   => $transaction->toArray());
    }

    protected function getRefundAndRelations($refundId)
    {
        $refund = (new Payment\Core)->retrieveRefundById($refundId);

        $transaction = $refund->transaction;
        $payment = $refund->payment;

        $terminal = $refund->payment->terminal;
        $merchant = $transaction->merchant;
        $card = $refund->payment->card;

        $transaction->entity()->associate($refund);

        $this->transaction = $transaction;

        return $array = array(
            'card'          => $card->toArray(),
            'payment'       => $payment->toArray(),
            'refund'        => $refund->toArray(),
            'merchant'      => $merchant->toArray(),
            'terminal'      => $terminal->toArray(),
            'transaction'   => $transaction->toArray());
    }

    protected function initRepos()
    {
        $this->txnRepo = new Transaction\Repository;
    }
}