<?php

namespace RZP\Models\BankAccount;

use Mail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Mail\Merchant\AccountChange as BankAccountChangeMail;


class Core extends Base\Core
{
    public function createOrChangeBankAccount($input, $merchant)
    {
        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        if ($oldBankAccount === null)
        {
            return $this->createBankAccount($input, $merchant, $this->mode);
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

        return $this->changeBankAccount($input, $merchant, $oldBankAccount);
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

        return $newBankAccount;
    }

    /**
     * This takes the oldBank Account as it's last parameter
     *
     * @param  array              $input Input Array with new bank account details
     * @param  MerchantEntity     $merchant
     * @param  BankAccount\Entity $oldBankAccount
     *
     * @return mixed
     */
    protected function changeBankAccount($input, $merchant, $oldBankAccount)
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

        $this->app['workflow']
             ->setEntityAndId($oldBankAccount->getEntity(), $oldBankAccount->getId())
             ->handle($oldBankAccountArray, $newBankAccountArray);

        return $this->repo->transaction(
            function() use ($merchant, $oldBankAccount, $input, $detail)
            {
                $this->repo->delete($oldBankAccount);

                $ba = $this->createBankAccount($input, $merchant, $this->mode);

                $this->sendBankAccountChangeEmail($ba, $merchant);

                $merchantDetails = $merchant->merchantDetail;

                if ($merchantDetails !== null)
                {
                    // Doing a fill only for ADDRESS_PROOF_URL because Details\Validator
                    // expects it to be a file object where as we're passing a File ID.
                    $merchantDetails->fill($input);

                    $merchantDetails->edit($detail);

                    $this->repo->merchant_detail->saveOrFail($merchantDetails);
                }

                return $ba;
            });
    }

    protected function uploadAddressProof($merchant, $input)
    {
        (new Validator)->validateAddressProofUploadOverProxyAuth($input);

        $merchantDetailService = new Detail\Service();

        $merchantDetails = $merchantDetailService->core()->getMerchantDetails($merchant);

        $fileInputs = [
            Detail\Entity::ADDRESS_PROOF_URL => $input[Detail\Entity::ADDRESS_PROOF_URL]
        ];

        $uploadedFileIds = $merchantDetailService->storeActivationFile(
            $merchantDetails,
            $fileInputs);

        if ((is_array($uploadedFileIds) === false) or
            (isset($uploadedFileIds[Detail\Entity::ADDRESS_PROOF_URL]) === false))
        {
            throw new Exception\ServerErrorException(
                'Address Proof URL upload failed.',
                ErrorCode::SERVER_ERROR);
        }

        $input[Detail\Entity::ADDRESS_PROOF_URL] = $uploadedFileIds[Detail\Entity::ADDRESS_PROOF_URL];

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

        return $ba;
    }

    /**
     * All bank account creation happens via this function
     *
     * @param  array  $input
     * @param  string $mode
     * @return BankAccount\Entity
     */
    protected function createBankAccount($input, $merchant, $mode)
    {
        $ba = $this->buildBankAccount($input, $merchant, $mode);

        $ba->associateMerchant($merchant);

        $ba->generateBeneficiaryCode();

        $this->repo->saveOrFail($ba);

        return $ba;
    }

    protected function buildBankAccount($input, $merchant, $mode)
    {
        $ba = new BankAccount\Entity;

        $ba->setConnection($mode);

        $ba = $ba->build($input);

        $ba->getValidator()->validateIfscCode($mode);

        $ba->merchant()->associate($merchant);

        return $ba;
    }

    protected function sendBankAccountChangeEmail($newBankAccount, $merchant)
    {
        if ($this->shouldNotifyViaEmail($merchant) === false)
        {
            return;
        }

        $newBankAccount = $newBankAccount->toArray();

        $merchant = $merchant->toArray();

        $bankAccountChangeMail = new BankAccountChangeMail($newBankAccount, $merchant);

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
}
