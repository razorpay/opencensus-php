<?php

namespace RZP\Models\Settlement\Mpr;

use App;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Gateway;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
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
    use Parser;

    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    protected $settledAt = null;
    protected $app;
    protected $mode;
    protected $env;

    protected $feeCalculator;
    protected $transaction;
    protected $merchant;
    protected $payment;
    protected $card;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->mode = $this->app['rzp.mode'];
        $this->env = $this->app->environment();

        $this->reconciledAt = time();

        $this->feeCalculator = new Pricing\Fee;

        $this->initRepos();
    }

    public function process($input)
    {
        $this->checkInput($input);

        $this->initSettledAtTimestamp($input);

        $mprFile = $input['attachment-1'];

        $mprData = $this->parseMprFile($mprFile);

        return $this->reconcile($mprData, 'hdfc');
    }

    protected function reconcile($mprData, $gateway)
    {
        $this->txnRepo->beginTransaction();

        try
        {
            $txns = $this->processData($mprData, $gateway);

            $this->txnRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->txnRepo->rollback();

            (new Settlement\SlackNotification)->failure('mpr_reconciliation', $e);

            throw $e;
        }

        $data = ['txn_count' => $txns->count()];

        (new Settlement\SlackNotification)->success('mpr_reconciliation', $data);

        return $txns;
    }

    protected function processData($mprData, $gateway)
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
        $entityInfo = $this->app['gateway']->call(
                                                'hdfc',
                                                'getPaymentOrRefundId',
                                                $mprRecord,
                                                $this->mode);

        list($transaction, $entitiesArray) = $this->loadTransactionAndRelations($entityInfo);

        $params = array(
            'input' => $mprRecord,
            'transactionId' => $this->transaction->getId(),
            'entities' => $entitiesArray);

        $data = $this->app['gateway']->call('hdfc', 'reconcile', $params, $this->mode);

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
                $transaction->source->card,
                $data['card']);

            $gatewayFee = $data['transaction']['gateway_fee'];
            $apiFee = $fee - $gatewayFee;

            $txnData = array(
                Transaction\Entity::GATEWAY_FEE => $data['transaction']['gateway_fee'],
                Transaction\Entity::API_FEE => $apiFee);
        }

        $txnData[Transaction\Entity::SETTLED_AT] = $this->settledAt;
        $transaction->fill($txnData);

        $transaction->setReconciledAt($this->reconciledAt);

        $this->txnRepo->save($transaction);

        return $transaction;
    }

    protected function initSettledAtTimestamp($input)
    {
        if ((isset($input['settled_at'])) and
            (ctype_digit($input['settled_at'])))
        {
            $settledAt = $input['settled_at'];

            $this->settledAt = $settledAt;
        }

        if ($this->settledAt === null)
        {
            $timestamp = Carbon::tomorrow('Asia/Kolkata')->timestamp;
            $this->settledAt = $timestamp;
        }
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
        $payment  = (new Payment\Core)->retrievePaymentById($paymentId);

        $transaction = $payment->transaction;

        $terminal = $payment->terminal;
        $merchant = $transaction->merchant;
        $card = $payment->card;

        $transaction->source()->associate($payment);

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

        $transaction->source()->associate($refund);

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
