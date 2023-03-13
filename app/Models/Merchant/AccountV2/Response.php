<?php

namespace RZP\Models\Merchant\AccountV2;


use App;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\Account\Constants;
use RZP\Models\Merchant\Account\Entity;
use RZP\Trace\TraceCode;

class Response extends Core
{
    public function getAccountResponse(Merchant\Entity $partner, Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $status = $account->isSuspended() === true ? Constants::SUSPENDED : Constants::CREATED;

        $data = [
            Constants::ID           => Entity::getSignedId($account->getId()),
            Constants::TYPE         => ($account->isLinkedAccount() === true) ? Type::ROUTE : Type::STANDARD,
            Constants::STATUS       => $status,
            Constants::EMAIL        => $account->getEmail(),
            Constants::PROFILE      => $this->getProfileData($account),
            Constants::NOTES        => $account->getNotes(),
            Constants::CREATED_AT   => $account->getCreatedAt()
        ];

        if ($status === Constants::SUSPENDED)
        {
            $data[Constants::SUSPENDED_AT] = $account->getSuspendedAt();
        }

        if ($this->checkIfPaymentAcceptanceFieldsToBeAdded($partner->getId()))
        {
            $data[Constants::STATUS] = $this->getAccountStatus($account, $accountDetails->getActivationStatus());

            $data[Constants::LIVE] = $account->isLive();

            $data[Constants::HOLD_FUNDS] = $account->isFundsOnHold();

            if ($data[Constants::STATUS] === Constants::ACTIVATED)
            {
                $data[Constants::ACTIVATED_AT] = $account->getActivatedAt();
            }
        }

        $contactMobile =  $accountDetails->getContactMobile();

        if (empty($contactMobile) === false)
        {
            $data[Constants::PHONE] = $contactMobile;
        }

        $contactName = $accountDetails->getContactName();

        if (empty($contactName) === false)
        {
            $data[Constants::CONTACT_NAME] = $contactName;
        }

        $accountCode = $account->getAccountCode();

        if (empty($accountCode) === false)
        {
            $data[Constants::REFERENCE_ID] = $accountCode;
        }

        $businessType = $accountDetails->getBusinessType();

        $data[Constants::BUSINESS_TYPE] = $businessType;

        $businessName = $accountDetails->getBusinessName();

        if (empty($businessName) === false)
        {
            $data[Constants::LEGAL_BUSINESS_NAME] = $businessName;
        }

        $billingLabel = $account->getBillingLabel();

        if (empty($billingLabel) === false)
        {
            $data[Constants::CUSTOMER_FACING_BUSINESS_NAME] = $billingLabel;
        }

        $data = $this->getLegalInfoData($accountDetails, $data);

        $data = $this->getAppsData($accountDetails, $data);

        $data = $this->getBrandData($account, $data);

        $data = $this->getContactInfo($account, $data);

        $data = $this->getTosAcceptanceData($accountDetails, $data);

        $this->trace->info(TraceCode::ACCOUNT_CREATION_V2_RESPONSE, $data);

        return $data;
    }

    protected function getTosAcceptanceData(Detail\Entity $accountDetails, array $data): array
    {
        $customFields = $accountDetails->getCustomFields();

        if (isset($customFields[Constants::TOS_ACCEPTANCE]) === true)
        {
            $data[Constants::TOS_ACCEPTANCE] = $customFields[Constants::TOS_ACCEPTANCE];
        }

        return $data;
    }

    protected function getBrandData(Merchant\Entity $account, array $data): array
    {
        $brandColor = $account->getBrandColor();

        if (empty($brandColor) === false)
        {
            $data[Constants::BRAND][Constants::COLOR] = $brandColor;
        }

        return $data;
    }

    protected function getLegalInfoData(Detail\Entity $accountDetails, array $data): array
    {
        $companyPan = $accountDetails->getPan();

        if (empty($companyPan) === false)
        {
            $data[Constants::LEGAL_INFO][Constants::PAN] = $companyPan;
        }

        $gstin = $accountDetails->getGstin();

        if (empty($gstin) === false)
        {
            $data[Constants::LEGAL_INFO][Constants::GST] = $gstin;
        }

        $cin = $accountDetails->getCompanyCin();

        if (empty($cin) === false)
        {
            $data[Constants::LEGAL_INFO][Constants::CIN] = $cin;
        }

        return $data;
    }

    protected function getContactInfo(Merchant\Entity $account, array $data): array
    {
        // add objects of support details, charge back details, etc in Contact Info
        foreach ($account->emails as $email)
        {
            $data[Constants::CONTACT_INFO][$email->getType()][Constants::EMAIL]      = $email[Constants::EMAIL];
            $data[Constants::CONTACT_INFO][$email->getType()][Constants::PHONE]      = $email[Constants::PHONE];
            $data[Constants::CONTACT_INFO][$email->getType()][Constants::POLICY_URL] = $email[Constants::URL];
        }

        return $data;
    }

    protected function getAppsData(Detail\Entity $accountDetails, array $data): array
    {
        $website = $accountDetails->getWebsite();

        $additionalWebsites = $accountDetails->getAdditionalWebsites() ?? [];

        if (empty($website) === true and empty($additionalWebsites) === true)
        {
            return $data;
        }

        $appWebsites = [];

        array_push($appWebsites, $website);

        $appWebsites = array_merge($appWebsites, $additionalWebsites);

        if (count($appWebsites) >= 1)
        {
            $data[Constants::APPS][Constants::WEBSITES] = $appWebsites;
        }

        $clientApplications = $accountDetails->getClientApplications();

        if (isset($clientApplications[Constants::ANDROID]) === true)
        {
            $data[Constants::APPS][Constants::ANDROID] = $clientApplications[Constants::ANDROID];
        }

        if (isset($clientApplications[Constants::IOS]) === true)
        {
            $data[Constants::APPS][Constants::IOS] = $clientApplications[Constants::IOS];
        }

        return $data;
    }

    protected function getProfileData(Merchant\Entity $account): array
    {
        $accountDetails = $account->merchantDetail;

        $data = [
            Constants::CATEGORY       => $accountDetails->getBusinessCategory(),
            Constants::SUBCATEGORY    => $accountDetails->getBusinessSubcategory(),
            Constants::ADDRESSES      => $this->getAddressesData($accountDetails),
        ];

        $businessDescription = $accountDetails->getBusinessDescription();

        if (empty($businessDescription) === false)
        {
            $data[Constants::DESCRIPTION] = $businessDescription;
        }

        $businessModel = $accountDetails->getBusinessModel();

        if (empty($businessModel) === false)
        {
            $data[Constants::BUSINESS_MODEL] = $businessModel;
        }

        return $data;
    }

    protected function getAddressesData(Detail\Entity $accountDetails): array
    {
        $data = [];

        if ($accountDetails->hasBusinessRegisteredAddress() === true)
        {
            $data[Constants::REGISTERED] = [
                Constants::STREET1     => $accountDetails->getBusinessRegisteredAddress(),
                Constants::STREET2     => $accountDetails->getBusinessRegisteredAddressLine2(),
                Constants::CITY        => $accountDetails->getBusinessRegisteredCity(),
                Constants::STATE       => IndianStates::getStateNameByCode($accountDetails->getBusinessRegisteredState()),
                Constants::POSTAL_CODE => $accountDetails->getBusinessRegisteredPin(),
                Constants::COUNTRY     => $accountDetails->getBusinessRegisteredCountry(),

            ];
        }

        if ($accountDetails->hasBusinessOperationAddress() === true)
        {
            $data[Constants::OPERATION] = [
                Constants::STREET1     => $accountDetails->getBusinessOperationAddress(),
                Constants::STREET2     => $accountDetails->getBusinessOperationAddressLine2(),
                Constants::CITY        => $accountDetails->getBusinessOperationCity(),
                Constants::STATE       => IndianStates::getStateNameByCode($accountDetails->getBusinessOperationState()),
                Constants::COUNTRY     => $accountDetails->getBusinessOperationCountry(),
                Constants::POSTAL_CODE => $accountDetails->getBusinessOperationPin(),
            ];
        }

        return $data;
    }

    protected function getAccountStatus(Merchant\Entity $account, $activationStatus) : string
    {
        if ($account->isSuspended())
        {
            return Constants::SUSPENDED;
        }
        return Constants::ACTIVATION_STATUS_ACCOUNT_STATUS_MAPPING[$activationStatus];
    }

    private function checkIfPaymentAcceptanceFieldsToBeAdded(string $partnerId) : bool
    {
        $app = App::getFacadeRoot();

        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $app['config']->get('app.add_payment_acceptance_fields_to_account_v2_response'),
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }
}
