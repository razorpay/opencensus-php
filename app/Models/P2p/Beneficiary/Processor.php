<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use Razorpay\IFSC\IFSC;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;
use RZP\Models\Base\PublicCollection;

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
        // convert to lower case in case of entity type is vpa
        if(isset($input[BankAccount\Entity::HANDLE]) === true and
           $input[Entity::TYPE] === Vpa\Entity::VPA)
        {
            $input[BankAccount\Entity::HANDLE] = strtolower($input[BankAccount\Entity::HANDLE]);
        }

        $this->initialize(Action::VALIDATE, $input, true);

        // For Bank Account, we don't have to hit gateway
        if ($this->input->get(Entity::TYPE) === BankAccount\Entity::BANK_ACCOUNT)
        {
            $validated = true;

            if ($this->shouldValidateIfsc($this->input->get(BankAccount\Entity::IFSC)))
            {
                $validated = IFSC::validate($this->input->get(BankAccount\Entity::IFSC));
            }

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
                $beneficiary = (new BankAccount\Core)->handleBeneficiary($this->input->toArray());
        }

        return $beneficiary->toArrayBeneficiary();
    }

    public function handleBeneficiary(array $input): array
    {
        $this->initialize(Action::HANDLE_BENEFICIARY, $input, true);

        $this->gatewayInput = $this->input;

        return $this->callGateway();
    }

    protected function handleBeneficiarySuccess(array $input): array
    {
        $this->initialize(Action::HANDLE_BENEFICIARY_SUCCESS, $input, true);

        $response = $this->toBeneficiaryVpa($this->input->toArray());

        return $response;
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        if (empty($this->input->get(Entity::BLOCKED)) === false)
        {
            $this->input->put(Entity::TYPE, Vpa\Entity::VPA);

            $this->gatewayInput = $this->input;

            return $this->callGateway();
        }

        return parent::fetchAll($input);
    }

    public function fetchAllSuccess(array $input): array
    {
        $this->initialize(Action::FETCH_ALL_SUCCESS, $input);

        $collection = new PublicCollection();

        foreach ($this->input->get(Entity::DATA) as $beneficiary)
        {
            $collection->push($this->toBeneficiaryVpa($beneficiary));
        }

        $output[PublicCollection::ENTITY]   = 'collection';
        $output[PublicCollection::COUNT]    = $collection->count();
        $output[PublicCollection::ITEMS]    = $collection->toArray();

        return $output;
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

    protected function toBeneficiaryVpa(array $input): array
    {
        $response = array_only($input, [
            Vpa\Entity::USERNAME,
            Vpa\Entity::HANDLE,
            Vpa\Entity::BENEFICIARY_NAME,
            Entity::BLOCKED,
            Entity::SPAMMED,
            Entity::BLOCKED_AT,
        ]);

        $response[Vpa\Entity::ENTITY]  = Vpa\Entity::VPA;
        $response[Vpa\Entity::ADDRESS] = Vpa\Entity::toAddress($input);

        return $response;
    }

    protected function shouldValidateIfsc(string $ifsc)
    {
        if ($this->isProductionAndLive() === true)
        {
            return true;
        }

        // We are going to whitelist the IFSC code
        if (starts_with($ifsc, ['AXIS']))
        {
            return false;
        }

        return true;
    }
}
