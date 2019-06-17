<?php

namespace RZP\Models\NodalBeneficiary;

use Config;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\SlackNotification;

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
     *
     * @return mixed
     */
    public function update(array $input)
    {
        $bankAccountId = $input[Entity::BANK_ACCOUNT_ID];

        unset($input[Entity::BANK_ACCOUNT_ID]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $channel = $input[Entity::CHANNEL];

        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                 ->fetchBeneficiaryDetailsForChannel(
                                     $bankAccountId,
                                     $channel
                                 );

        $validator->validateBankAccount($nodalBeneficiary, $bankAccountId);

        $validator->validateNewRegistrationStatus(
                        $input[Entity::REGISTRATION_STATUS],
                        $nodalBeneficiary->getRegistrationStatus()
                  );

        $nodalBeneficiary = $nodalBeneficiary->edit($input);

        $this->repo->nodal_beneficiary->saveOrFail($nodalBeneficiary);

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
     * Creates or Updates the beneficiary using fund_account_id
     *
     * @param array $input
     * @return Entity
     */
    public function createOrUpdateBeneficiaryForFTS(array $input)
    {
        try
        {
            $validator = new Validator;

            $validator->validateInput('update', $input);

            $ftsFundAccountId = $input['fund_account_id'];

            $bankAccount = $this->repo->bank_account->getBankAccountByFtsFundAccountId($ftsFundAccountId);

            $nodalBeneficiary = [
                Entity::CHANNEL             => $input['channel'],
                Entity::MERCHANT_ID         => $bankAccount->merchant->getId(),
                Entity::BANK_ACCOUNT_ID     => $bankAccount->getId(),
                Entity::BENEFICIARY_CODE    => $bankAccount->getBeneficiaryCode(),
                Entity::REGISTRATION_STATUS => $input['status'],
            ];

            return $this->create($nodalBeneficiary);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_BENEFICIARY_CREATE_OR_UPDATE_FAILED,
                [
                    'error' => $e->getMessage()
                ]);
        }
    }
}
