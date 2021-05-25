<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;

class Core extends Base\Core
{
    /**
     * @param array  $input
     * @param string $inputValidationOp
     *
     * @return Entity
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function create(array $input, string $inputValidationOp): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ACTIVATION_DETAIL_CREATE,
            [
                'input' => $input
            ]
        );

        $activationDetail = new Entity;

        $activationDetail->build($input);

        $validator = new Validator();

        $validator->validateInput($inputValidationOp, $input);

        $validator->validateAccountTypeForChannel($activationDetail->bankingAccount, $input);

        $this->repo->saveOrFail($activationDetail);

        return $activationDetail;
    }

    public function verifyOtpForContact($input, \RZP\Models\Merchant\Entity $merchant, \RZP\Models\User\Entity $user, Entity $bankingAccountActivationDetail)
    {
        $validator = new Validator();

        $validator->validateInput('verifyOtp', $input);

        $userCore = new \RZP\Models\User\Core();

        $userCore->verifyOtp($input + ['action' => 'verify_contact'], $merchant, $user);

        $bankingAccountActivationDetail->setContactMobileVerified(true);

        $this->repo->saveOrFail($bankingAccountActivationDetail);
    }

    public function update(Entity $activationDetail, array $input): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ACTIVATION_DETAIL_UPDATE_REQUEST,
            [
                'banking_account_id'    => $activationDetail->getBankingAccountId(),
                'input' => $input,
            ]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $validator->validateAccountTypeForChannel($activationDetail->bankingAccount, $input);

        $activationDetail->edit($input);

        $this->repo->saveOrFail($activationDetail);

        return $activationDetail;
    }
}
