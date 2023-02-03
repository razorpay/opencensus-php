<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestValidationFailureException;

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

        $validator->validateEntityProofDocumentType($input);

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

        $validator->validateEntityProofDocumentType($input);

        $validator->validateAccountTypeForChannel($activationDetail->bankingAccount, $input);

        $activationDetail->edit($input);

        $this->repo->saveOrFail($activationDetail);

        return $activationDetail;
    }

    public function assignBankPartnerPocToApplication(Entity $bankingAccountActivationDetails, string $bankPocUserId)
    {
        $bankingAccountActivationDetails->setBankPOCUserId($bankPocUserId);

        $this->repo->saveOrFail($bankingAccountActivationDetails);
    }

    /*
     * Returns
     *  0    if skipDwt is false
     *  1    if skipDwt is true
     *  null if value need not be computed
     *  Logic for Skip DWT
     *  Address prefilled via GSTIN and
     *  All four RBL new onboarding declaration are true
     */
    /**
     * @throws LogicException
     */
    public function computeSkipDwt(?Entity $bankingAccountActivationDetails, array $input, Merchant\Entity $merchant): ?int
    {
        if (empty($bankingAccountActivationDetails))
        {
            return null;
        }

        // since this attribute is being read from a json, it is returned as a string
        $declarationStepCompleted = ($input[Entity::DECLARATION_STEP] ?? null) == 1;

        if ($declarationStepCompleted === false)
        {
            return null;
        }

        $skipDwtEligibleFlag = $this->isMerchantEligibleForSkipDwtExperiment($merchant);

        if ($skipDwtEligibleFlag === false)
        {
            return 0;
        }

        $additionalDetails = optional($bankingAccountActivationDetails)->getAdditionalDetails() ?? '{}';

        $additionalDetails = json_decode($additionalDetails, true);

        $rblNewOnboardingDeclarations = $additionalDetails[Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS] ?? [];

        $availableAtPreferredAddressToCollectDocs = $rblNewOnboardingDeclarations[Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS] ?? null;

        $signatoriesAvailableAtPreferredAddress = $rblNewOnboardingDeclarations[Entity::SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS] ?? null;

        if (is_null($availableAtPreferredAddressToCollectDocs) ||
            is_null($signatoriesAvailableAtPreferredAddress)
        )
        {
            throw new LogicException('Declarations cannot be null if merchant is eligible for SKIP_DWT experiment',
            null,
                [
                    'available_at_preferred_address_to_collect_docs'    => $availableAtPreferredAddressToCollectDocs,
                    'signatories_available_at_preferred_address'        => $signatoriesAvailableAtPreferredAddress,
                ]);
        }

        $skipDwt = 0;

        if ($availableAtPreferredAddressToCollectDocs === 1 &&
            $signatoriesAvailableAtPreferredAddress === 1)
        {
            $skipDwt = 1;
        }

        $this->trace->info(
            TraceCode::MERCHANT_ELIGIBLE_FOR_SKIP_DWT_EXPERIMENT,
            [
                'skip_dwt'                                          => $skipDwt,
                'available_at_preferred_address_to_collect_docs'    => $availableAtPreferredAddressToCollectDocs,
                'signatories_available_at_preferred_address'        => $signatoriesAvailableAtPreferredAddress,
            ]);

        return $skipDwt;
    }

    // This function is used to check if address is being updated and an additional check if value is different from existing value
    // Returns 0 if address is updated, else null to indicated
    /**
     * @throws LogicException
     */
    public function checkAddressUpdate(?Entity $bankingAccountActivationDetails, array $input): ?int
    {
        if (empty($bankingAccountActivationDetails))
        {
            return null;
        }

        $newMerchantDocumentsAddress = $input[Entity::MERCHANT_DOCUMENTS_ADDRESS] ?? null;

        $oldMerchantDocumentsAddress = $bankingAccountActivationDetails->getMerchantDocumentsAddress();

        if ((empty($oldMerchantDocumentsAddress) === false &&
            empty($newMerchantDocumentsAddress) === false) &&
            $oldMerchantDocumentsAddress !== $newMerchantDocumentsAddress)
        {
            return 0;
        }

        return null;
    }

    public function isMerchantEligibleForSkipDwtExperiment(Merchant\Entity $merchant): bool
    {
        try
        {
            $attributeCore = new Merchant\Attribute\Core;

            $skipDwtEligible = $attributeCore->fetch($merchant,
                Product::BANKING,
                Merchant\Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS,
                Merchant\Attribute\Type::SKIP_DWT_ELIGIBLE);

            $skipDwtEligibleFlag = ($skipDwtEligible->getValue() === 'enabled');
        }
        catch (\Throwable $e)
        {
            $skipDwtEligibleFlag = false;
        }

        return $skipDwtEligibleFlag;
    }
}
