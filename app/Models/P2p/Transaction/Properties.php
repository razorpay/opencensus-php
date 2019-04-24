<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Properties
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @var ArrayBag
     */
    protected $input;

    /**
     * @var ArrayBag
     */
    protected $relations;

    public function __construct(Context $context, string $action, ArrayBag $input)
    {
        $this->action       = $action;
        $this->input        = $input;

        $this->relations   = new ArrayBag;

        $this->initialize($context);
    }

    public function attachToTransaction(Entity $transaction)
    {
        if ($this->relations->has(Entity::PAYEE) === true)
        {
            $transaction->payee()->associate($this->relations->get(Entity::PAYEE));
        }

        if ($this->relations->has(Entity::PAYER) === true)
        {
            $transaction->payer()->associate($this->relations->get(Entity::PAYER));
        }

        if ($this->relations->has(Entity::BANK_ACCOUNT) === true)
        {
            $transaction->bankAccount()->associate($this->relations->get(Entity::BANK_ACCOUNT));
        }

        if ($this->relations->has(Entity::CUSTOMER) === true)
        {
            $transaction->customer()->associate($this->relations->get(Entity::CUSTOMER));
        }
    }

    protected function initialize(Context $context)
    {
        $this->input->put(Entity::METHOD, Entity::UPI);
        $this->input->put(Entity::GATEWAY, $context->getHandle()->getAcquirer());

        $payer       = null;
        $payee       = null;
        $bankAccount = null;

        switch ($this->action)
        {
            case Action::INITIATE_PAY :

                $this->input->putMany([
                        Entity::TYPE                => Type::PAY,
                        Entity::FLOW                => Flow::DEBIT,
                        Entity::MODE                => Mode::DEFAULT,
                        Entity::STATUS              => Status::CREATED,
                        Entity::INTERNAL_STATUS     => Status::CREATED,
                    ]);

                $payer          = $this->getTransactionPayer(true);
                $payee          = $this->getTransactionPayee(false);
                $bankAccount    = $this->getTransactionBankAccount($payer);
                break;

            case Action::INITIATE_COLLECT :

                $this->input->putMany([
                        Entity::TYPE                => Type::COLLECT,
                        Entity::FLOW                => Flow::CREDIT,
                        Entity::MODE                => Mode::DEFAULT,
                        Entity::STATUS              => Status::CREATED,
                        Entity::INTERNAL_STATUS     => Status::CREATED,
                    ]);

                $payer          = $this->getTransactionPayer(false);
                $payee          = $this->getTransactionPayee(true);
                $bankAccount    = $this->getTransactionBankAccount($payee);

                break;
        }

        $this->relations->putMany([
            Entity::PAYER           => $payer,
            Entity::PAYEE           => $payee,
            Entity::BANK_ACCOUNT    => $bankAccount,
            Entity::CUSTOMER        => $context->getDevice()->customer
        ]);

        $this->input->forget([
            Entity::PAYER,
            Entity::PAYEE,
        ]);
    }

    protected function getTransactionPayer(bool $onus)
    {
        // Since only VPA as Payer is allowed
        if ($onus === false)
        {
            $payer = (new Vpa\Core)->find($this->input->get(Entity::PAYER)[Entity::ID]);
        }
        else
        {
            $payer = (new Vpa\Core)->fetch($this->input->get(Entity::PAYER)[Entity::ID]);
        }

        return $payer;
    }

    protected function getTransactionPayee(bool $onus)
    {
        // Since only VPA as Payee is allowed
        if ($onus === false)
        {
            $payee = (new Vpa\Core)->find($this->input->get(Entity::PAYEE)[Entity::ID]);
        }
        else
        {
            $payee = (new Vpa\Core)->fetch($this->input->get(Entity::PAYEE)[Entity::ID]);
        }

        return $payee;
    }

    protected function getTransactionBankAccount($entity)
    {
        // Since only VPA can owner
        if ($entity instanceof VPA\Entity)
        {
            $bankAccount = $entity->bankAccount;
        }

        return $bankAccount;
    }
}
