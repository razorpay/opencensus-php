<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use Razorpay\IFSC\IFSC;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function add(array $input): array
    {
        $this->initialize(Action::ADD, $input, true);

        $beneficiary = $this->findByEntity($this->input->get(Entity::TYPE), $this->input->get(Entity::ID));

        $entity = $this->core->findOrCreate($beneficiary, $this->input->toArray());

        return $entity->toArrayPublic();
    }

    public function validate(array $input): array
    {
        $this->initialize(Action::VALIDATE, $input, true);

        // For Bank Account, we don't have to hit gateway
        if ($this->input->get(Entity::TYPE) === BankAccount\Entity::BANK_ACCOUNT)
        {
            $validated = IFSC::validate($this->input->get(BankAccount\Entity::IFSC));

            $this->input->put(Entity::VALIDATED, $validated);

            return $this->validateSuccess($this->input->toArray());
        }

        $this->gatewayInput->putMany($this->input->toArray());

        return $this->callGateway();
    }

    protected function validateSuccess(array $input): array
    {
        $this->initialize(Action::VALIDATE_SUCCESS, $input, true);

        $type       = $this->input->get(Entity::TYPE);
        $validated  = $this->input->get(Entity::VALIDATED);

        $this->input->forget([Entity::TYPE, Entity::VALIDATED]);

        if (empty($validated) === true)
        {
            return [
                Entity::VALIDATED   => false,
                Entity::TYPE        => $type,
            ];
        }

        switch ($type)
        {
            case Vpa\Entity::VPA:
                $beneficiary = (new Vpa\Core)->handleBeneficiary($this->input->toArray());

                break;

            case BankAccount\Entity::BANK_ACCOUNT:
                $beneficiary = (new BankAccount\Core)->createBeneficiary($this->input->toArray());
        }

        return $beneficiary->toArrayBeneficiary();
    }

    protected function getEntity()
    {
        return $this->input->get(Entity::TYPE);
    }

    protected function findByEntity(string $type, string $id)
    {
        switch ($type)
        {
            case BankAccount\Entity::BANK_ACCOUNT:
                $beneficiary = (new BankAccount\Core)->find($id);
                break;

            case Vpa\Entity::VPA:
                $beneficiary = (new Vpa\Core)->find($id);
                break;
        }

        return $beneficiary;
    }
}
