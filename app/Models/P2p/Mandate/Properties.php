<?php

namespace RZP\Models\P2p\Mandate;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Error\P2p\ErrorCode;
use RZP\Exception\P2p\BadRequestException;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * Class Properties
 *
 * @package RZP\Models\P2p\Mandate
 */
class Properties
{
    /**
     * Default expire at is set to be 30 minutes in seconds
     */
    const DEFAULT_EXPIRE_AT = 1800;

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
        $this->action   = $action;
        $this->input    = $input;

        $this->relations   = new ArrayBag;

        $this->initialize($context);
    }

    /**
     * @param Context $context
     */
    protected function initialize(Context $context)
    {
        $this->input->put(Entity::GATEWAY, $context->getHandle()->getAcquirer());

        $payer = null;
        $payee = null;
        $bankAccount = null;

        switch ($this->action)
        {
            case Action::INCOMING_COLLECT:

                $payer = $this->getMandatePayer(true);
                $payee = $this->getMandatePayee(false);

                $bankAccount    = $this->getMandateBankAccount($payer);

                $this->input->putMany([
                    Entity::TYPE             => Type::COLLECT,
                    Entity::FLOW             => Flow::DEBIT,
                    Entity::MODE             => Mode::DEFAULT,
                    Entity::STATUS           => Status::REQUESTED,
                    Entity::DESCRIPTION      => $this->input->get(Entity::DESCRIPTION),
                    Entity::EXPIRE_AT        => $this->getMandateExpireAt(),
                    Entity::PAYER_ID         => $payer->getId(),
                    Entity::PAYEE_ID         => $payee->getId(),
                    Entity::BANK_ACCOUNT_ID  => $bankAccount->getId(),
                    Entity::CYCLES_COMPLETED => 0,
                ]);

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
            Entity::BANK_ACCOUNT,
        ]);
    }

    /**
     * @param bool $onus
     *
     * @return Vpa\Entity|null
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function getMandatePayer(bool $onus)
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

    /**
     * @param bool $onus
     *
     * @return Vpa\Entity|null
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function getMandatePayee(bool $onus)
    {
        $input = $this->input->get(Entity::PAYEE);

        if ($onus === false)
        {
            if (isset($input[Entity::ID]) === false)
            {
                $payee = (new Vpa\Core)->handleBeneficiary($input);
            }
            else
            {
                $payee = (new Vpa\Core)->find($input[Entity::ID]);
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

    /**
     * @param $entity
     *
     * @return \RZP\Models\P2p\BankAccount\Entity
     */
    protected function getMandateBankAccount($entity)
    {
        // Since only VPA can be an owner
        if ($entity instanceof VPA\Entity)
        {
            $bankAccount = $entity->bankAccount;
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NO_BANK_ACCOUNT_FOUND);
        }

        return $bankAccount;
    }

    /**
     * @return int
     */
    protected function getMandateExpireAt()
    {
        $expireAt = $this->input->get(Entity::EXPIRE_AT);

        if (empty($expireAt) === true)
        {
            $expireAt = Carbon::now()->addSeconds(self::DEFAULT_EXPIRE_AT)->getTimestamp();
        }

        return $expireAt;
    }

    /**
     * Attach to mandate relation
     * @param Entity $mandate
     */
    public function attachToMandate(Entity $mandate)
    {
        if ($this->relations->has(Entity::PAYEE) === true)
        {
            $mandate->payee()->associate($this->relations->get(Entity::PAYEE));
        }

        if ($this->relations->has(Entity::PAYER) === true)
        {
            $mandate->payer()->associate($this->relations->get(Entity::PAYER));
        }

        if ($this->relations->has(Entity::BANK_ACCOUNT) === true)
        {
            $mandate->bankAccount()->associate($this->relations->get(Entity::BANK_ACCOUNT));
        }

        if ($this->relations->has(Entity::CUSTOMER) === true)
        {
            $mandate->customer()->associate($this->relations->get(Entity::CUSTOMER));
        }
    }
}
