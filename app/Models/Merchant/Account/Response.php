<?php

namespace RZP\Models\Merchant\Account;

use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\Detail;

class Response extends Core
{
    public function generateResponse(Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $data = [
            Constants::ENTITY            => Constants::ACCOUNT,
            Constants::ID                => Entity::getSignedId($account->getId()),
            Constants::MANAGED           => 1,
            Constants::EXTERNAL_ID       => $account->getExternalId(),
            Constants::NOTES             => $account->getNotes(),
            Constants::BUSINESS_ENTITY   => $accountDetails->getBusinessType(),
            Constants::LEGAL_ENTITY_ID   => $account->getLegalEntityId(),
            Constants::LEGAL_EXTERNAL_ID => $account->legalEntity->getExternalId(),
            Constants::EMAIL             => $account->getEmail(),
            Constants::REVIEW_STATUS     => $this->getReviewStatusData($account),
            Constants::PROFILE           => $this->getProfileData($account),
            Constants::PAYMENT           => $this->getPaymentData($account),
            Constants::CREATED_AT        => $account->getCreatedAt(),
        ];

        $contactMobile =  $accountDetails->getContactMobile();

        if (empty($contactMobile) === false)
        {
            // if contact mobile is present, contact info should be sent as contact info fields are mandatory
            $data[Constants::PHONE] = $contactMobile;

            $data[Constants::CONTACT_INFO] = [
                Constants::NAME => $accountDetails->getContactName(),
                Constants::EMAIL => $accountDetails->getContactEmail(),
                Constants::PHONE => $accountDetails->getContactMobile(),
            ];
        }

        $settlementData = $this->getSettlementData($account, $accountDetails);

        if (empty($settlementData) === false)
        {
            $data[Constants::SETTLEMENT] = $settlementData;
        }

        $data[Constants::SETTINGS] = [
            Constants::PAYMENT => [
                Constants::INTERNATIONAL => $accountDetails->getBusinessInternational(),
            ],
        ];

        $customFields = $accountDetails->getCustomFields();

        if (isset($customFields[Constants::TNC]) === true)
        {
            $data[Constants::TNC] = $customFields[Constants::TNC];
        }

        $this->trace->info(TraceCode::ACCOUNT_CREATION_RESPONSE, $data);

        return $data;
    }

    protected function getReviewStatusData(Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $data = [
            Constants::CURRENT_STATE => [
                Constants::STATUS             => $account->getAccountStatus(),
                Constants::PAYMENT_ENABLED    => $account->isLive(),
                Constants::SETTLEMENT_ENABLED => ($account->isActivated() === true)
                                                 and ($account->getHoldFunds() === false),
            ],
        ];

        if ($account->isKycHandledByPartner() === true)
        {
            return $data;
        }

        $activationStatus = $accountDetails->getActivationStatus();

        // if activation form is not yet submitted
        if (empty($activationStatus) === true)
        {
            $data[Constants::REQUIREMENTS] = $this->getRequirementsDataBeforeSubmission($account);
        }
        else if ($activationStatus === Detail\Status::NEEDS_CLARIFICATION)
        {
            $data[Constants::REQUIREMENTS] = $this->getRequirementsWhenNeedsClarification($account);
        }
        else
        {
            $data[Constants::REQUIREMENTS] = [];
        }

        return $data;
    }

    protected function getRequirementsDataBeforeSubmission(Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $response = (new Detail\Core)->createResponse($accountDetails);

        $requirements = [];

        if ($response['can_submit'] === false)
        {
            $requirements = [
                Constants::BUSINESSES => [
                    Constants::FIELDS    => [],
                    Constants::DOCUMENTS => [],
                ],
            ];

            $fields = $response['verification']['required_fields'];

            foreach ($fields as $field)
            {
                if (Merchant\Document\Type::isValid($field) === true)
                {
                    $requirements[Constants::BUSINESSES][Constants::DOCUMENTS][] = [
                        Constants::TYPE   => $field,
                        Constants::REASON => Constants::REQUIRED_DOCUMENT_MISSING,
                    ];
                }
                else
                {
                    $requirements[Constants::BUSINESSES][Constants::FIELDS][] = [
                        Constants::FIELD_NAME => $field,
                        Constants::REASON     => Constants::REQUIRED_FIELD_MISSING,
                    ];
                }
            }
        }

        return $requirements;
    }

    protected function getRequirementsWhenNeedsClarification(Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $reasons = $accountDetails->getKycClarificationReasons();

        if (empty($reasons) === true)
        {
            return [];
        }

        $clarificationReasons = $reasons[Detail\Entity::CLARIFICATION_REASONS] ?? [];

        $requirements = [];

        foreach ($clarificationReasons as $fieldName => $reasons)
        {
            foreach ($reasons as $reason)
            {
                $requirement = [
                    Constants::FIELD_NAME => $fieldName,
                ];

                if ($reason[Merchant\Constants::REASON_TYPE] === Merchant\Constants::PREDEFINED_REASON_TYPE)
                {
                    $requirement[Constants::REASON] = $reason[Merchant\Constants::REASON_CODE];
                }
                else
                {
                    $requirement[Constants::REASON] = Detail\NeedsClarificationMetaData::OTHERS;
                }

                $group = (Merchant\Document\Type::isValid($fieldName) === true) ? Constants::DOCUMENTS : Constants::FIELDS;

                $requirements[Constants::BUSINESSES][$group][] = $requirement;
            }
        }

        return $requirements;
    }

    protected function getProfileData(Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $data = [
            Constants::ADDRESSES         => $this->getAddressesData($account, $accountDetails),
            Constants::NAME              => $account->getName(),
            Constants::DESCRIPTION       => $accountDetails->getBusinessDescription(),
            Constants::BUSINESS_MODEL    => $accountDetails->getBusinessPaymentDetails(),
            Constants::MCC               => $account->getCategory(),
            Constants::DASHBOARD_DISPLAY => $account->getDisplayName(),
            Constants::WEBSITE           => $account->getWebsite(),
            Constants::BILLING_LABEL     => $account->getDbaName(),
            Constants::BRAND             => [
                Constants::ICON  => $account->getIconUrl(),
                Constants::LOGO  => $account->getLogoUrl(),
                Constants::COLOR => $account->getBrandColor(),
            ],
        ];

        $companyPan = $accountDetails->getPan();

        if (empty($companyPan) === false)
        {
            $data[Constants::IDENTIFICATION][] = [
                Constants::TYPE                  => DocumentType::COMPANY_PAN,
                Constants::IDENTIFICATION_NUMBER => $companyPan,
            ];
        }

        $gstin = $accountDetails->getGstin();

        if (empty($gstin) === false)
        {
            $data[Constants::IDENTIFICATION][] = [
                Constants::TYPE                  => DocumentType::GSTIN,
                Constants::IDENTIFICATION_NUMBER => $gstin,
            ];
        }

        $promoterPan = $accountDetails->getPromoterPan();

        if (empty($promoterPan) === false)
        {
            $data[Constants::OWNER_INFO] = [
                Constants::NAME           => $accountDetails->getPromoterPanName(),
                Constants::IDENTIFICATION => [
                    [
                        Constants::TYPE                  => DocumentType::OWNER_PAN,
                        Constants::IDENTIFICATION_NUMBER => $accountDetails->getPromoterPan(),
                    ],
                ]
            ];
        }

        $customFields = $accountDetails->getCustomFields();

        if (isset($customFields[Constants::APPS]) === true)
        {
            $data[Constants::APPS] = $customFields[Constants::APPS];
        }

        // add objects of support details, charge back details, etc
        foreach ($account->emails as $email)
        {
            $data[$email->getType()] = $email->toArrayPublic();
        }

        return $data;
    }

    protected function getAddressesData(Merchant\Entity $account, Detail\Entity $accountDetails): array
    {
        $data = [];

        if ($accountDetails->hasBusinessRegisteredAddress() === true)
        {
            $data[] = [
                Constants::TYPE          => Constants::REGISTERED,
                Constants::LINE1         => $accountDetails->getBusinessRegisteredAddress(),
                Constants::LINE2         => $accountDetails->getBusinessRegisteredAddressLine2(),
                Constants::CITY          => $accountDetails->getBusinessRegisteredCity(),
                Constants::DISTRICT_NAME => $accountDetails->getBusinessRegisteredDistrict(),
                Constants::STATE         => IndianStates::getStateNameByCode($accountDetails->getBusinessRegisteredState()),
                Constants::COUNTRY       => $accountDetails->getBusinessRegisteredCountry(),
                Constants::PIN           => $accountDetails->getBusinessRegisteredPin(),
            ];
        }

        if ($accountDetails->hasBusinessOperationAddress() === true)
        {
            $data[] = [
                Constants::TYPE          => Constants::OPERATION,
                Constants::LINE1         => $accountDetails->getBusinessOperationAddress(),
                Constants::LINE2         => $accountDetails->getBusinessOperationAddressLine2(),
                Constants::CITY          => $accountDetails->getBusinessOperationCity(),
                Constants::DISTRICT_NAME => $accountDetails->getBusinessOperationDistrict(),
                Constants::STATE         => IndianStates::getStateNameByCode($accountDetails->getBusinessOperationState()),
                Constants::COUNTRY       => $accountDetails->getBusinessOperationCountry(),
                Constants::PIN           => $accountDetails->getBusinessOperationPin(),
            ];
        }

        return $data;
    }

    protected function getPaymentData(Merchant\Entity $account): array
    {
        $flashCheckoutEnabled = ($account->isFeatureEnabled(Feature\Constants::NOFLASHCHECKOUT) === false);

        $data = [
            Constants::FLASH_CHECKOUT => $flashCheckoutEnabled,
            Constants::INTERNATIONAL  => $account->isInternational(),
        ];

        return $data;
    }

    protected function getSettlementData(Merchant\Entity $account, Detail\Entity $accountDetails): array
    {
        $bankAccount = $account->bankAccount;

        $bankAccountArray = [];

        $status = Constants::PENDING_VERIFICATION;

        if (($this->mode === Mode::LIVE) and (empty($bankAccount) === false))
        {
            $bankAccountArray = [
                Constants::ID             => $bankAccount->getPublicId(),
                Constants::ACCOUNT_NUMBER => $bankAccount->getAccountNumber(),
                Constants::IFSC           => $bankAccount->getIfscCode(),
                Constants::NAME           => $bankAccount->getBeneficiaryName(),
                Constants::NOTES          => $bankAccount->getNotes()->toArray(),
            ];

            $status = Constants::ACTIVE;
        }
        else if($accountDetails->hasBankAccountDetails() === true)
        {
            $testBankAccount = $this->repo->bank_account->getBankAccountOnConnection($account, Mode::TEST);

            $bankAccountArray = [
                Constants::ACCOUNT_NUMBER => $accountDetails->getBankAccountNumber(),
                Constants::IFSC           => $accountDetails->getBankBranchIfsc(),
                Constants::NAME           => $accountDetails->getBankAccountName(),
                Constants::NOTES          => $testBankAccount->getNotes()->toArray(),
            ];
        }

        if (empty($bankAccountArray) === true)
        {
            return $bankAccountArray;
        }

        $data = [
            Constants::FUND_ACCOUNTS => [
                [
                    Constants::BANK_ACCOUNT => $bankAccountArray,
                    Constants::STATUS       => $status,
                ]
            ],
        ];

        return $data;
    }
}
