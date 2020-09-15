<?php

namespace RZP\Models\BankAccount;

use Mail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Traits\TrimSpace;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Document;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Merchant\Document\FileHandler;
use RZP\Models\Settlement\OndemandFundAccount;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Core as DocumentCore;

class Core extends Base\Core
{
    use TrimSpace;

    public function createOrChangeBankAccount($input, $merchant)
    {
        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        if ($oldBankAccount === null)
        {
            $ba = $this->createBankAccount($input, $merchant, $this->mode);

            if ($this->settlementServiceRamp($ba->getMerchantId()) === true)
            {
                app('settlements_dashboard')->createBankAccount($ba);
            }

            return $ba;
        }

        $newBankAccount = $this->buildBankAccount($input, $merchant, $this->mode);

        if ($newBankAccount->equals($oldBankAccount))
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                [
                    'new' => $newBankAccount->toArray(),
                    'old' => $oldBankAccount->toArray(),
                ]);

            return $oldBankAccount;
        }

        $ba = $this->changeBankAccount($input, $merchant, $oldBankAccount);

        if ($this->settlementServiceRamp($ba->getMerchantId()) === true)
        {
            app('settlements_dashboard')->changeBankAccount($ba);
        }

        return $ba;
    }

    public function addOrUpdateBankAccountForCustomer($input, $customer)
    {
        $currentAccounts = $this->repo->bank_account->getBankAccountsForCustomer($customer);

        $newBankAccount = $this->buildBankAccount($input, $customer->merchant, $this->mode);

        $newBankAccount->associateCustomer($customer);

        foreach ($currentAccounts as $existingAccount)
        {
            if ($newBankAccount->equals($existingAccount))
            {
                $this->trace->info(
                    TraceCode::MISC_TRACE_CODE,
                    [
                        'new' => $newBankAccount->toArray(),
                        'old' => $existingAccount->toArray(),
                    ]);

                return $existingAccount;
            }
        }

        $this->repo->saveOrFail($newBankAccount);

        (new Beneficiary)->enqueueForBeneficiaryRegistration($newBankAccount);

        return $newBankAccount;
    }

    /**
     * `source` entity can be customer|contact
     *
     * @param array             $input
     * @param MerchantEntity    $merchant
     * @param Base\PublicEntity $source
     *
     * @return Entity
     */
    public function createBankAccountForFundAccount(array $input,
                                                    Merchant\Entity $merchant,
                                                    Base\PublicEntity $source = null): Entity
    {
        (new Validator)->validateIfscCode($input, $this->mode);

        $trimmedInput = $this->trimSpacesIfMerchantEnabled($input, $merchant->getId());

        $ba = $this->createBankAccountForSource(
                        $trimmedInput,
                        $merchant,
                        $source,
                        'add_fund_account_bank_account');

        return $ba;
    }

    public function editBankAccount(Entity $bankAccount, array $input)
    {
        $this->trace->info(
            TraceCode::BANK_ACCOUNT_EDIT,
            [
                'edit_input' => $input,
                'bank_account' => $bankAccount->toArray()
            ]);

        $bankAccount = $bankAccount->edit($input);

        $this->repo->saveOrFail($bankAccount);

        $this->updateOndemandFundAccountIfRequired($bankAccount);

        return $bankAccount;
    }

    public function updateOndemandFundAccountIfRequired($bankAccount)
    {
        $merchantId = $bankAccount[Entity::MERCHANT_ID];

        if ($bankAccount->getType() === Type::MERCHANT)
        {
            /** @var Merchant\Entity $merchant */
            $merchant = $this->repo->merchant->find($merchantId);

            if ($merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND) === true)
            {
                (new OndemandFundAccount\Service)->dispatchSettlementOndemandFundAccountUpdateJob($merchantId);
            }
        }
    }

    /**
     * This takes the oldBank Account as it's last parameter
     *
     * @param  array              $input Input Array with new bank account details
     * @param  MerchantEntity     $merchant
     * @param  BankAccount\Entity $oldBankAccount
     *
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    protected function changeBankAccount(array $input, MerchantEntity $merchant, BankAccount\Entity $oldBankAccount)
    {
        $detail = $this->formatBankAccountForMerchantDetail($input);

        $newBankAccount = $this->buildBankAccount($input, $merchant, $this->mode);

        $newBankAccount->associateMerchant($merchant);

        $newBankAccount->generateBeneficiaryCode();

        $oldBankAccountArray = $oldBankAccount->toArrayPublic();
        $newBankAccountArray = $newBankAccount->toArrayPublic();

        // add code for check of Bank File here and change the entities
        // Upload the file and get the file id
        if (isset($input[Detail\Entity::ADDRESS_PROOF_URL]) === true)
        {
            // upload the file and then add the file id in the array
            if (is_object($input[Detail\Entity::ADDRESS_PROOF_URL]) === true)
            {
                $input = $this->uploadAddressProof($merchant, $input);
            }

            $newBankAccountArray[Detail\Entity::ADDRESS_PROOF_URL] = $input[Detail\Entity::ADDRESS_PROOF_URL];

            $oldBankAccountArray[Detail\Entity::ADDRESS_PROOF_URL] = (new Detail\Core())
                ->getMerchantDetails($merchant)
                ->getAddressProofFile();

            // to replace the file with file id in request for workflow payload
            $this->app['request']->replace($input);
        }

        return $this->repo->transaction(
            function() use ($merchant, $oldBankAccountArray, $newBankAccountArray, $oldBankAccount, $input, $detail)
            {
                //
                // Creating a bank account entity to send email. This will be rolled back if workflow if enabled.
                // Hence creating only single entity.
                //
                $ba = $this->createBankAccount($input, $merchant, $this->mode);

                //
                // Send Email if it is not a workflow execution flow, since we want to send the request received
                // email only once and not again after the workflow has been approved.
                //
                if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false)
                {
                    $this->sendBankAccountChangeEmail($ba, $merchant, true);
                }

                $this->app['workflow']
                     ->setEntityAndId($oldBankAccount->getEntity(), $oldBankAccount->getId())
                     ->handle($oldBankAccountArray, $newBankAccountArray);

                $this->repo->delete($oldBankAccount);

                $this->sendBankAccountChangeEmail($ba, $merchant);

                $merchantDetails = $merchant->merchantDetail;

                if ($merchantDetails !== null)
                {
                    if (isset($input[Detail\Entity::ADDRESS_PROOF_URL]) === true)
                    {
                        $this->handleAddressProofUrl($input, $merchantDetails, $merchant);
                    }

                    // Doing a fill only for ADDRESS_PROOF_URL because Details\Validator
                    // expects it to be a file object where as we're passing a File ID.
                    $merchantDetails->fill($input);

                    $merchantDetails->edit($detail);

                    $this->repo->merchant_detail->saveOrFail($merchantDetails);
                }

                $this->updateOndemandFundAccountIfRequired($ba);

                return $ba;
            });
    }

    /**
     *
     * @param array          $input
     * @param DetailEntity   $merchantDetails
     * @param MerchantEntity $merchant
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    function handleAddressProofUrl(array &$input, Merchant\Detail\Entity $merchantDetails, Merchant\Entity $merchant): void
    {
        $params = [
            Detail\Entity::ADDRESS_PROOF_URL => $input[Detail\Entity::ADDRESS_PROOF_URL]
        ];

        $detailService = new Detail\Service();

        $detailService->deleteExistingDocuments($params, $merchantDetails);

        $documentParams[Detail\Entity::ADDRESS_PROOF_URL] = [
            Document\Constants::FILE_ID => $input[Detail\Entity::ADDRESS_PROOF_URL],
            Document\Constants::SOURCE  => (new FileHandler\Factory())->getDocumentSource($input[Detail\Entity::ADDRESS_PROOF_URL], $merchant->getId())
        ];

        (new DocumentCore)->storeInMerchantDocument($merchant, $documentParams);
    }


    protected function uploadAddressProof($merchant, $input)
    {
        (new Validator)->validateAddressProofUploadOverProxyAuth();

        $merchantDetailService = new Detail\Service();

        $merchantDetails = $merchantDetailService->core()->getMerchantDetails($merchant);

        $fileInputs = [
            Detail\Entity::ADDRESS_PROOF_URL => $input[Detail\Entity::ADDRESS_PROOF_URL]
        ];

        $fileAttributes = $merchantDetailService->storeActivationFile($merchantDetails, $fileInputs);

        if ((is_array($fileAttributes) === false) or
            (isset($fileAttributes[Detail\Entity::ADDRESS_PROOF_URL]) === false))
        {
            throw new Exception\ServerErrorException(
                'Address Proof URL upload failed.',
                ErrorCode::SERVER_ERROR);
        }

        $input[Detail\Entity::ADDRESS_PROOF_URL] = $fileAttributes[Detail\Entity::ADDRESS_PROOF_URL][Document\Constants::FILE_ID];

        return $input;
    }

    public function updateBeneficiaryCodes()
    {
        $bas = $this->repo->bank_account->fetchBankAccountsWithoutBeneCode();

        foreach ($bas as $ba)
        {
            $ba->generateBeneficiaryCode();

            $this->repo->saveOrFail($ba);
        }

        $result['count'] = $bas->count();

        return $result;
    }

    public function createTestBankAccount($merchant)
    {
        $input = array(
            'ifsc_code'             => Entity::SPECIAL_IFSC_CODE,
            'beneficiary_name'      => 'Test ' . $merchant->getId(),
            'beneficiary_email'     => $merchant->getEmail(),
            'account_number'        => random_integer(11),
            'beneficiary_address1'  => 'Bengaluru Palace',
            'beneficiary_address2'  => 'Palace Rd, Vasanth Nagar',
            'beneficiary_city'      => 'Banglore',
            'beneficiary_state'     => 'KA',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '560052',
            'beneficiary_mobile'    => '18002700323',
        );

        $ba = $this->createBankAccount($input, $merchant, Mode::TEST);

        // this won't be trigger until the ramp up is 100% because when the merchant is signs up then
        // only this function will call and we can not have the merchant id configured in front
        if ($this->settlementServiceRamp($merchant->getId()) === true)
        {
            app('settlements_dashboard')->createBankAccount($ba, Mode::TEST);
        }

        return $ba;
    }

    // pushToQueue is added to configure queue push and razorx call later since this function is used within
    // a DB transaction.
    public function createBankAccountForSource(
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $source = null,
        string $addRule,
        bool $pushToQueue=true): Entity
    {
        $ba = new BankAccount\Entity;

        $ba = $ba->build($input, $addRule);

        $ba->merchant()->associate($merchant);

        $ba->source()->associate($source);

        $this->repo->saveOrFail($ba);

        if ($pushToQueue === true) {
            (new Beneficiary)->enqueueForBeneficiaryRegistration($ba);
        }

        return $ba;
    }

    /**
     * All bank account creation happens via this function
     *
     * @param  array  $input
     * @param         $merchant
     * @param  string $mode
     *
     * @return BankAccount\Entity
     */
    protected function createBankAccount($input, $merchant, $mode)
    {
        $ba = $this->buildBankAccount($input, $merchant, $mode);

        $ba->associateMerchant($merchant);

        $ba->generateBeneficiaryCode();

        $this->repo->saveOrFail($ba);

        (new Beneficiary)->enqueueForBeneficiaryRegistration($ba);

        return $ba;
    }

    protected function buildBankAccount($input, $merchant, $mode)
    {
        $ba = new BankAccount\Entity;

        $ba->setConnection($mode);

        // if live mode and input does not already contain notes, copy test mode notes
        if (($mode === Mode::LIVE) and (empty($input[Entity::NOTES]) === true))
        {
            $testBankAccount = $this->repo->bank_account->getBankAccountOnConnection($merchant, Mode::TEST);

            if (empty($testBankAccount) === false)
            {
                $notes = $testBankAccount->getNotes();

                $input[Entity::NOTES] = $notes->toArray();
            }
        }

        $ba = $ba->build($input);

        $ba->getValidator()->validateIfscCode($input, $mode);

        $ba->merchant()->associate($merchant);

        return $ba;
    }

    protected function sendBankAccountChangeEmail($newBankAccount, $merchant, $request = false)
    {
        if ($this->shouldNotifyViaEmail($merchant) === false)
        {
            return;
        }

        $newBankAccount = $newBankAccount->toArray();

        $recipients = (new Merchant\Core)->getEmailsOfOwnersAndAdmins($merchant);

        $merchant = $merchant->toArray();

        $class = 'RZP\Mail\Merchant\AccountChange';

        if ($request === true)
        {
            $class = 'RZP\Mail\Merchant\AccountChangeRequest';
        }

        $bankAccountChangeMail = new $class($newBankAccount, $merchant, $recipients);

        Mail::queue($bankAccountChangeMail);
    }

    public function buildBankAccountArrayFromMerchantDetail(DetailEntity $detail, bool $linkedAccount = false): array
    {
        $details = $detail->toArray();

        $data = [
            Entity::IFSC_CODE             => $details[DetailEntity::BANK_BRANCH_IFSC],
            Entity::BENEFICIARY_NAME      => $details[DetailEntity::BANK_ACCOUNT_NAME],
            Entity::ACCOUNT_NUMBER        => $details[DetailEntity::BANK_ACCOUNT_NUMBER],
            Entity::BENEFICIARY_COUNTRY   => 'IN',
            Entity::BENEFICIARY_EMAIL     => $details[DetailEntity::CONTACT_EMAIL],
            Entity::BENEFICIARY_MOBILE    => $details[DetailEntity::CONTACT_MOBILE],
        ];

        //
        // For Marketplace linked accounts, the bank fields set below are not
        // required in the activation form but needed for API validation
        // Setting default values here to overcome this
        //
        if ($linkedAccount === true)
        {
            $data[Entity::BENEFICIARY_MOBILE]   = 9999999999;
        }

        return $data;
    }

    protected function formatBankAccountForMerchantDetail(array $input): array
    {
        $detail = [
            Detail\Entity::BANK_BRANCH_IFSC          => $input[Entity::IFSC_CODE],
            Detail\Entity::BANK_ACCOUNT_NUMBER       => $input[Entity::ACCOUNT_NUMBER],
            Detail\Entity::BANK_ACCOUNT_NAME         => $input[Entity::BENEFICIARY_NAME],
        ];

        return $detail;
    }

    protected function shouldNotifyViaEmail(MerchantEntity $merchant): bool
    {
        // In dev and testing environments we want to send mail even if Mode is TEST
        if (($this->mode === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return false;
        }

        // Do not email linked accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return false;
        }

        return true;
    }

    public function getBankAccountEntity(string $id)
    {
        return $this->repo->bank_account->find($id);
    }

//    public function updateBankAccountWithFtsId(Entity $entity, $ftsFundAccountId)
//    {
//        $entity->setFtsFundAccountId($ftsFundAccountId);
//
//        $this->repo->saveOrFail($entity);
//    }

    public function getBankAccountByFtsFundAccountId($ftsFundAccountId)
    {
        return $this->repo->bank_account->getBankAccountByFtsFundAccountId($ftsFundAccountId);
    }

    public function settlementServiceRamp(string $merchantId)
    {
        return (new Bucket\Core)->shouldProcessViaNewService($merchantId);
    }

    public function MigrateBankAccountsToSettlementService($merchantId, $mode)
    {
        $merchant = $this->repo->merchant->fetchMerchantOnConnection($merchantId, $mode);

        $ba  = $this->repo->bank_account->getBankAccountOnConnection($merchant, $mode);

        if($ba === null)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_BA_MIGRATION_SKIPPED,
                [
                    'merchant_id' => $merchant->getId(),
                    'mode'        => $mode,
                ]);

            return;
        }

        app('settlements_api')->migrateBankAccount($ba, $mode);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_BA_MIGRATION_SUCCESS,
            [
                'merchant_id' => $merchant->getId(),
                'mode'        => $mode,
            ]);
    }
}
