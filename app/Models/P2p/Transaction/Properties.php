<?php

namespace RZP\Models\P2p\Transaction;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Properties
{
    /**
     * Default expire at is set to be 30 minutes in seconds
     */
    const DEFAULT_EXPIRE_AT = 1800;

    /**
     * As per NPCI guidelines default description is UPI
     */
    const DEFAULT_UPI_DESCRIPTION = 'UPI';

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
                        Entity::MODE                => $this->getTransactionMode(),
                        Entity::DESCRIPTION         => $this->getTransactionDescription(),
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
                        Entity::DESCRIPTION         => $this->getTransactionDescription(),
                        Entity::STATUS              => Status::CREATED,
                        Entity::INTERNAL_STATUS     => Status::CREATED,
                        Entity::EXPIRE_AT           => $this->getTransactionExpireAt(),
                    ]);

                $payer          = $this->getTransactionPayer(false);
                $payee          = $this->getTransactionPayee(true);
                $bankAccount    = $this->getTransactionBankAccount($payee);
                break;

            case Action::INCOMING_COLLECT :

                $this->input->putMany([
                    Entity::TYPE                => Type::COLLECT,
                    Entity::FLOW                => Flow::DEBIT,
                    Entity::MODE                => Mode::DEFAULT,
                    Entity::DESCRIPTION         => $this->getTransactionDescription(),
                    Entity::STATUS              => Status::CREATED,
                    Entity::INTERNAL_STATUS     => Status::CREATED,
                    Entity::EXPIRE_AT           => $this->getTransactionExpireAt(),
                ]);

                $payer          = $this->getTransactionPayer(true);
                $payee          = $this->getTransactionPayee(false);
                $bankAccount    = $this->getTransactionBankAccount($payer);
                break;

            case Action::INCOMING_PAY :

                $this->input->putMany([
                    Entity::TYPE                => Type::PAY,
                    Entity::FLOW                => Flow::CREDIT,
                    Entity::MODE                => Mode::DEFAULT,
                    Entity::DESCRIPTION         => $this->getTransactionDescription(),
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
            Entity::UPI,
        ]);
    }

    protected function getTransactionPayer(bool $onus)
    {
        $input = $this->input->get(Entity::PAYER);

        if ($onus === false)
        {
            if (isset($input[Entity::ID]) === false)
            {
                $payer = (new Vpa\Core)->handleBeneficiary($input);
            }
            else
            {
                $payer = (new Vpa\Core)->find($input[Entity::ID]);
            }
        }
        else
        {
            if (isset($input[Entity::ID]) === false)
            {
                $payer = (new Vpa\Core)->fetchByUsernameHandle($input);
            }
            else
            {
                $payer = (new Vpa\Core)->fetch($input[Entity::ID]);
            }
        }

        return $payer;
    }

    protected function getTransactionPayee(bool $onus)
    {
        $input = $this->input->get(Entity::PAYEE);

        if ($onus === false)
        {
            $core = ($input[Entity::TYPE] ?? null) === Entity::BANK_ACCOUNT ?
                                                        new BankAccount\Core :
                                                        new Vpa\Core;

            if (isset($input[Entity::ID]) === false)
            {
                $payee = $core->handleBeneficiary($input);
            }
            else
            {
                $payee = $core->find($input[Entity::ID]);
            }
        }
        else
        {
            if (isset($input[Entity::ID]) === false)
            {
                $payee = (new Vpa\Core)->fetchByUsernameHandle($input);
            }
            else
            {
                $payee = (new Vpa\Core)->fetch($input[Entity::ID]);
            }
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
        else
        {
            assert(false, 'Only VPA can have bank account');
        }

        return $bankAccount;
    }

    protected function getTransactionExpireAt()
    {
        $expireAt = $this->input->get(Entity::EXPIRE_AT);

        if (empty($expireAt) === true)
        {
            $expireAt = Carbon::now()->addSeconds(self::DEFAULT_EXPIRE_AT)->getTimestamp();
        }

        return $expireAt;
    }

    protected function getTransactionMode()
    {
        return $this->input->get(Entity::MODE, Mode::DEFAULT);
    }

    protected function getTransactionDescription()
    {
        return $this->input->get(Entity::DESCRIPTION, self::DEFAULT_UPI_DESCRIPTION);
    }
}
