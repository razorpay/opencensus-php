<?php

namespace Models\Settlement;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Card;
use Models\Gateway;
use Models\Ledger;
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

    protected $lgr;
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
        $this->lgrRepo->beginTransaction();

        try
        {
            $lgrs = $this->process($mprData, $gateway);

            $this->lgrRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->lgrRepo->rollback();

            (new SlackNotification)->queueOperationFailure('mpr_reconciliation', $e);

            throw $e;
        }

        $count = $lgrs->count();

        (new SlackNotification)->queueOperationSuccess('mpr_reconciliation', $count);

        return $lgrs;
    }

    protected function process($mprData, $gateway)
    {
        $lgrs = new Base\PublicCollection;

        foreach ($mprData as $row)
        {
            $lgr = $this->reconcileMprRecord($row, $gateway);

            $lgrs->push($lgr);
        }

        return $lgrs;
    }

    protected function reconcileMprRecord($mprRecord, $gateway)
    {
        $paymentId = Gateway::call('getPaymentId', $mprRecord, 'test');

        $lgr = $this->newLedgerRecord();

        $entitiesArray = $this->loadEntities($paymentId);

        $params = array(
            'input' => $mprRecord,
            'ledgerId' => $lgr->getKey(),
            'entities' => $entitiesArray);

        $data = Gateway::call('reconcile', $params, 'test');

        $lgr = $this->reconcileRecord($data);

        return $lgr;
    }

    protected function reconcileRecord($data)
    {
        $lgr = $this->lgr;

        $entities = $this->entities;

        list($fee, $pricingRuleId) = $this->calculateMerchantFees();

        $this->updateCardNetworkAndCountry(
            $this->card,
            $data['card']['network'],
            $data['card']['country']);

        $amount = $this->payment->getAmount();
        $credit = $amount - $fee;

        $gatewayFee = $data['ledger']['gateway_fee'];
        $apiFee = $fee - $gatewayFee;

        $lgrData = array(
            Ledger\Entity::AMOUNT => $amount,
            Ledger\Entity::GATEWAY_FEE => $data['ledger']['gateway_fee'],
            Ledger\Entity::MERCHANT_ID => $this->merchant->getKey(),
            Ledger\Entity::ENTITY_ID => $this->payment->getKey(),
            Ledger\Entity::ENTITY_TYPE => 'payment',
            Ledger\Entity::FEE => $fee,
            Ledger\Entity::CREDIT => $credit,
            Ledger\Entity::DEBIT => 0,
            Ledger\Entity::CURRENCY => 'INR',
            Ledger\Entity::PRICING_RULE_ID => $pricingRuleId,
            Ledger\Entity::API_FEE => $apiFee,
            Ledger\Entity::SETTLED_AT => self::$settledAt);

        $this->lgr->fill($lgrData);

        if ($this->checkPreviousEntries($lgr, $this->lgrRepo) === false)
        {
            $this->updateBalances($lgr);

            $this->lgrRepo->save($lgr);
        }

        return $lgr;
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

        $merchantBalance->addAmount($this->lgr['credit']);
        $merchantBalance->subAmount($this->lgr['debit']);
        $merchantRepo->save($merchantBalance);

        $nodalBalance->addAmount($this->lgr['api_fee']);
        $merchantRepo->save($nodalBalance);

        $this->lgr[Ledger\Entity::BALANCE] = $merchantBalance->getBalance();
        $this->lgr[Ledger\Entity::ESCROW_BALANCE] = $nodalBalance->getBalance();
    }

    protected function newLedgerRecord()
    {
        $lgr = new Ledger\Entity;
        $lgr->generateId();
        $lgr->setReconciledAt($this->reconciledAt);

        $this->lgr = $lgr;
        $this->entities['ledger'] = $lgr;

        return $lgr;
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
            'ledger' => $this->lgr->toArray());
    }

    protected function initRepos()
    {
        $this->lgrRepo = new Ledger\Repository;
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
            $msg = 'Entity: Ledger row' . PHP_EOL . $msg;
            $msg = 'Previous Ledger row do not match' . PHP_EOL . $msg;
            throw new Exception\LogicException($msg);
        }

        return true;
    }
}