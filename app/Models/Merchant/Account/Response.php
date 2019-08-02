<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

class Response extends Core
{
    public function generateResponse(Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $data = [
            Constants::ENTITY          => Constants::ACCOUNT,
            Constants::ID              => Entity::getSignedId($account->getId()),
            Constants::MANAGED         => 1,
            Constants::NOTES           => $account->getNotes(),
            Constants::BUSINESS_ENTITY => $accountDetails->getBusinessType(),
            Constants::EMAIL           => $account->getEmail(),
            Constants::PHONE           => $accountDetails->getContactMobile(),
            Constants::REVIEW_STATUS   => $this->getReviewStatusData($account, $accountDetails),
            Constants::PROFILE         => $this->getProfileData($account, $accountDetails),
            Constants::PAYMENT         => $this->getPaymentData($account),
            Constants::SETTLEMENT      => $this->getSettlementData($account),
            Constants::CREATED_AT      => $account->getCreatedAt(),
        ];

        $customFields = $accountDetails->getCustomFields();

        if (isset($customFields[Constants::TNC]) === true)
        {
            $data[Constants::TNC] = $customFields[Constants::TNC];
        }

        return $data;
    }

    protected function getReviewStatusData(Merchant\Entity $account, Detail\Entity $accountDetails): array
    {
        $data = [
            Constants::CURRENT_STATE => [
                Constants::STATUS             => $account->getAccountStatus(),
                Constants::PAYMENT_ENABLED    => $account->isLive(),
                Constants::SETTLEMENT_ENABLED => ($account->isActivated() === true)
                                                 and ($account->getHoldFunds() === false),
            ],
        ];

        return $data;
    }

    protected function getProfileData(Merchant\Entity $account, Detail\Entity $accountDetails): array
    {
        $data = [
            Constants::ADDRESSES         => $this->getAddressesData($account, $accountDetails),
            Constants::NAME              => $account->getName(),
            Constants::DESCRIPTION       => $accountDetails->getBusinessDescription(),
            Constants::BUSINESS_MODEL    => $accountDetails->getBusinessPaymentDetails(),
            Constants::MCC               => (int) $account->getCategory(),
            Constants::DASHBOARD_DISPLAY => $account->getDisplayName(),
            Constants::WEBSITE           => $account->getWebsite(),
            Constants::BILLING_LABEL     => $account->getDbaName(),
            Constants::BRAND             => [
                Constants::ICON  => $account->getIconUrl(),
                Constants::LOGO  => $account->getLogoUrl(),
                Constants::COLOR => $account->getBrandColor(),
            ],
        ];

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
        $data = [
            [
                Constants::TYPE    => Constants::REGISTERED,
                Constants::LINE1   => $accountDetails->getBusinessRegisteredAddress(),
                Constants::LINE2   => $accountDetails->getBusinessRegisteredAddressLine2(),
                Constants::CITY    => $accountDetails->getBusinessRegisteredCity(),
                Constants::STATE   => $accountDetails->getBusinessRegisteredState(),
                Constants::COUNTRY => $accountDetails->getBusinessRegisteredCountry(),
                Constants::PIN     => $accountDetails->getBusinessRegisteredPin(),
            ],
            [
                Constants::TYPE    => Constants::OPERATION,
                Constants::LINE1   => $accountDetails->getBusinessOperationAddress(),
                Constants::LINE2   => $accountDetails->getBusinessOperationAddressLine2(),
                Constants::CITY    => $accountDetails->getBusinessOperationCity(),
                Constants::STATE   => $accountDetails->getBusinessOperationState(),
                Constants::COUNTRY => $accountDetails->getBusinessOperationCountry(),
                Constants::PIN     => $accountDetails->getBusinessOperationPin(),
            ],
        ];

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

    protected function getSettlementData(Merchant\Entity $account): array
    {
        $bankAccount = $account->bankAccount;

        if (empty($bankAccount) === true)
        {
            return [];
        }

        $data = [
            Constants::FUND_ACCOUNTS => [
                [
                    Constants::BANK_ACCOUNT => $bankAccount->toArrayPublic(),
                ]
            ],
        ];

        return $data;
    }
}
