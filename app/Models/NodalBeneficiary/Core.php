<?php

namespace RZP\Models\NodalBeneficiary;

use Config;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Jobs\FTS\CreateAccount;
use RZP\Models\FundAccount\Type;
use RZP\Models\Settlement\Metric as Metric;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Type as ProductType;

class Core extends Base\Core
{
    /**
     * Create Nodal Beneficiary with bank account.
     *
     * @param array $input
     * @return Entity
     */
    public function createWithBankAccount(array $input): Entity
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
     * Create Nodal Beneficiary with card.
     *
     * @param array $input
     * @return Entity
     */
    public function createWithCard(array $input): Entity
    {
        $merchantId = $input[Entity::MERCHANT_ID];

        $cardId = $input[Entity::CARD_ID];

        unset($input[Entity::MERCHANT_ID]);

        unset($input[Entity::CARD_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $card = $this->repo->card->findOrFail($cardId);

        $nodalBeneficiary = (new Entity)->build($input);

        $nodalBeneficiary->merchant()->associate($merchant);

        $nodalBeneficiary->card()->associate($card);

        $this->repo->saveOrFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * Update Nodal beneficiary with Bank Account
     *
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\LogicException
     */
    public function updateNodalBeneficiaryWithBankAccount(array $input)
    {
        $bankAccountId = $input[Entity::BANK_ACCOUNT_ID];

        unset($input[Entity::BANK_ACCOUNT_ID]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $channel = $input[Entity::CHANNEL];

        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                 ->fetchBankAccountBeneficiaryDetailsForChannel(
                                     $bankAccountId,
                                     $channel
                                 );

        $validator->validateBankAccount($nodalBeneficiary, $bankAccountId);

        $validator->validateNewRegistrationStatus(
                        $input[Entity::REGISTRATION_STATUS],
                        $nodalBeneficiary->getRegistrationStatus()
                  );

        if (($input[Entity::REGISTRATION_STATUS] === Status::FAILED) and
            ($nodalBeneficiary->getRegistrationStatus() !== Status::FAILED))
        {
            $this->trace->count(
                Metric::BENEFICIARY_REGISTRATION_STATUS,
                [
                    Metric::CHANNEL => $channel
                ],
                1
            );
        }

        $nodalBeneficiary = $nodalBeneficiary->edit($input);

        $this->repo->nodal_beneficiary->saveOrFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * Update Nodal Beneficiary with card
     *
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\LogicException
     */
    public function updateNodalBeneficiaryWithCard(array $input)
    {
        $cardId = $input[Entity::CARD_ID];

        unset($input[Entity::CARD_ID]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $channel = $input[Entity::CHANNEL];

        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                       ->fetchCardBeneficiaryDetailsForChannel(
                                           $cardId,
                                           $channel
                                       );

        $validator->validateCard($nodalBeneficiary, $cardId);

        $validator->validateNewRegistrationStatus(
            $input[Entity::REGISTRATION_STATUS],
            $nodalBeneficiary->getRegistrationStatus()
        );

        if (($input[Entity::REGISTRATION_STATUS] === Status::FAILED) and
            ($nodalBeneficiary->getRegistrationStatus() !== Status::FAILED))
        {
            $this->trace->count(
                Metric::BENEFICIARY_REGISTRATION_STATUS,
                [
                    Metric::CHANNEL => $channel
                ],
                1
            );
        }

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
                                 ->fetchBankAccountBeneficiaryDetailsForChannel(
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

            return $this->createWithBankAccount($nodalBeneficiary);
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
