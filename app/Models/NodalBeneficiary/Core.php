<?php

namespace RZP\Models\NodalBeneficiary;

use Config;

use RZP\Models\Base;

class Core extends Base\Core
{
    /**
     * @param array $input
     * @return Entity
     */
    public function create(array $input): Entity
    {
        $merchantId = $input[Entity::MERCHANT_ID];

        $bankAccountId = $input[Entity::BANK_ACCOUNT_ID];

        unset($input[Entity::MERCHANT_ID]);

        unset($input[Entity::BANK_ACCOUNT_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $bankAccount = $this->repo->bank_account->findOrFail($bankAccountId);

        $nodalBeneficiary = (new Entity)->build($input);

        $nodalBeneficiary->merchant()->associate($merchant);

        $nodalBeneficiary->bankAccount()->associate($bankAccount);

        $this->repo->saveOrFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    public function update(array $input)
    {
        $channel = $input[Entity::CHANNEL];

        $bankAccountId = $input[Entity::BANK_ACCOUNT_ID];

        unset($input[Entity::CHANNEL]);

        unset($input[Entity::BANK_ACCOUNT_ID]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                 ->fetchBeneficiaryDetailsForChannel(
                                     $bankAccountId,
                                     $channel
                                 );

        $validator->validateNewRegistrationStatus(
                        $input[Entity::REGISTRATION_STATUS],
                        $nodalBeneficiary->getRegistrationStatus()
                  );

        if ($input[Entity::REGISTRATION_STATUS] === Status::FAILED)
        {
            $this->notifyBeneficiaryRegistrationFailure(
                        $nodalBeneficiary->getRegistrationStatus(),
                        $input,
                        $bankAccountId,
                        $channel
                 );
        }

        $nodalBeneficiary = $nodalBeneficiary->edit($input);

        $this->repo->nodal_beneficiary->saveorFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * @param array $input
     * @return mixed
     */
    public function delete(array $input)
    {
        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                 ->fetchBeneficiaryDetailsForChannel(
                                     $input[Entity::BANK_ACCOUNT_ID],
                                     $input[Entity::CHANNEL]
                                 );

        return $this->repo->nodal_beneficiary->deleteOrFail($nodalBeneficiary);
    }

    /**
     * Sends beneficiary registration failure alert
     * @param string $currentStatus
     * @param array $input
     * @param string $bankAccountId
     * @param string $channel
     */
    protected function notifyBeneficiaryRegistrationFailure(string $currentStatus, array $input, string $bankAccountId, string $channel)
    {
        $message = ' *ALERT*: Beneficiary status for bank account id: ' .
                    $bankAccountId . ' on channel ' . $channel .
                    ' changed from '. $currentStatus . ' to ' . $input[Entity::REGISTRATION_STATUS];

        $channel = Config::get('slack.channels.settlements');

        $this->app['slack']->queue(
            $message,
            $input,
            [
                'color'    => 'bad',
                'icon'     => ':boom:',
                'channel'  => $channel,
                'username' => 'Beneficiary Registration',
            ]
        );
    }
}
