<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\BankingAccount\Gateway;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    protected $processor;

    public function createBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        // TODO: Validate if account does not already exist for the merchant

        $channel = $input[Entity::CHANNEL];

        $processor = $this->getProcessor($channel);

        $bankContent = $processor->validateAndPreProcessInputForAccountCreation($input);

        $input = array_merge($input, $bankContent);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    public function processAccountInfoWebhook(string $channel, array $input)
    {
        $processor = $this->getProcessor($channel);

        try
        {
            $reference = $processor->preProcessAccountInfoNotification($input);

            $bankingAccount = $this->repo->banking_account->findByBankReferenceAndChannel($reference, $channel);

            $attributes = $processor->processAccountInfoNotification($input);

            $this->updateBankingAccount($bankingAccount, $attributes);

            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::PROCESSED);
        }
        catch (\Throwable $e)
        {
            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::CANCELLED);
        }

        return $response;
    }

    public function updateBankingAccount(Entity $bankingAccount, array $input)
    {
        $channel = $bankingAccount->getChannel();

        $processor = $this->getProcessor($channel);

        $processor->validateAccountDetailsBeforeUpdating($input);

        $this->checkMerchantIsActivatedBeforeAccountActivation($bankingAccount, $input);

        $bankingAccount = $bankingAccount->edit($input);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    public function addServiceablePincodes(array $pincodes, string $channel)
    {
        $processor = $this->getProcessor($channel);

        $processor->addServiceablePincodes($pincodes);
    }

    public function deleteServiceablePincodes(array $pincodes, string $channel)
    {
        $processor = $this->getProcessor($channel);

        $processor->deleteServiceablePincodes($pincodes);
    }

    /**
     * This method is responsible for checking that unless the merchant is L2 activated, no one can update
     * the status of current account to processed. This to avoid cases of manual error by Bizops.
     *
     * @param Entity $bankingAccount
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    protected function checkMerchantIsActivatedBeforeAccountActivation(Entity $bankingAccount, array $input)
    {
        $status = $input[Entity::STATUS];

        if ((isset($input[Entity::STATUS]) === true) and
            ($status === Status::PROCESSED))
        {
            $merchant = $bankingAccount->merchant;

            $merchantActivationStatus = $merchant->merchantDetail->getActivationStatus();

            if ($merchantActivationStatus !== Detail\Status::ACTIVATED)
            {
                throw new BadRequestValidationFailureException(
                    'Operation not allowed, merchant is not L2 activated',
                    null,
                    [
                        'merchant_activation_status' => $merchant->merchantDetail->getActivationStatus(),
                        'banking_account'            => $bankingAccount->getId(),
                    ]);
            }
        }
    }

    protected function getProcessor(string $channel): Gateway\Base\Processor
    {
        $processor = __NAMESPACE__ . '\\' . 'Gateway';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Processor';

        return new $processor();
    }
}
