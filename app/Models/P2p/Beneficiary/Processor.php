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
        $this->initialize(Action::ADD, $input);

        return [
            'id'               => 'vpa_8zIfY8quFElCbH',
            'entity'           => 'vpa',
            'beneficiary_name' => $input['beneficiary_name'],
            'address'          => $input['address'],
            'username'         => 'beneficiary',
            'handle'           => 'razorhdfc',
            'created_at'       => time()
        ];
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

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        return [
            'entity'   => 'collection',
            'count'    => 2,
            'items'    => [
                [
                    'id'                    => 'vpa_8zIfY8quFElCbH',
                    'entity'                => 'vpa',
                    'beneficiary_name'      => 'Beneficiary Name',
                    'address'               => 'beneficiary@razorhdfc',
                    'username'              => 'beneficiary',
                    'handle'                => 'razorhdfc',
                    'created_at'            => time()
                ],
                [
                    'id'                    => 'ba_8zIfY7hSkCF8wr',
                    'entity'                => 'bank_account',
                    'beneficiary_name'      => 'Beneficiary Name',
                    'masked_account_number' => '*********1234',
                    'ifsc_code'             => 'RAZ00000001',
                    'bank_name'             => 'Razorpay',
                    'address'               => '100010001000@RAZ00000001.ifsc.npci',
                    'created_at'            => time()
                ]
            ]
        ];
    }

    protected function getEntity()
    {
        return $this->input->get(Entity::TYPE);
    }
}
