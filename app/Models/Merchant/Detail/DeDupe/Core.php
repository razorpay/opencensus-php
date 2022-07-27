<?php


namespace RZP\Models\Merchant\Detail\DeDupe;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Partner;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\MerchantRiskAlert;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use \RZP\Models\User\Entity as UserEntity;
use RZP\Models\User\Service as UserService;
use RZP\Services\MerchantRiskClient;
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;
use RZP\Models\Merchant\Detail\RetryStatus as RetryStatus;


class Core extends Base\Core
{
    protected $merchantRiskClient;

    public function __construct()
    {
        parent::__construct();

        $this->merchantRiskClient = $this->app->merchantRiskClient;
    }

    public function setMerchantRiskClient($merchantRiskClient)
    {
        $this->merchantRiskClient = $merchantRiskClient;
    }

    private function isDedupeExperimentEnabled(string $merchantId, string $experiment): bool
    {
        $mode = $this->mode ?? Mode::LIVE;

        $variant = $this->app->razorx->getTreatment(
            $merchantId, $experiment, $mode);

        return ($variant === 'on');
    }

    public function isDedupeRequired(Merchant\Entity $merchant, $force = false)
    {
        /**
         * If feature flag is enabled on Org and dedupe flag is set then return true,
         * To perform dedupe on Merchant irrespective of Org.
         */
        if(($merchant->org->isFeatureEnabled(Feature\Constants::ORG_SUB_MERCHANT_MCC_PENDING) === true) and ($force === true))
        {
            return true;
        }

        if ($merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        if ($merchant->isLinkedAccount() === true)
        {
            return false;
        }

        $partnerCore = (new Partner\Core);

        if ($partnerCore->isFullyManagedSubMerchant($merchant) === true)
        {
            return false;
        }

        return true;
    }

    public function getDedupeMatchedFields(Merchant\Entity $merchant): array
    {
        if ($this->isDedupeRequired($merchant) === false)
        {
            return [];
        }

        $riskScores = $this->merchantRiskClient->getMerchantImpersonatedDetails(
            Constants::MERCHANT_RISK_CLIENT_TYPE_ONBOARDING, $merchant->getId());

        return $this->getMatchedFieldsFromRiskScore($riskScores);
    }

    public function isMerchantImpersonated(Merchant\Entity $merchant): bool
    {
        if ((new MerchantRiskAlert\Service())->isRasSignupFraudMerchant($merchant->getId()) === true)
        {
            return true;
        }

        if ($this->isDedupeRequired($merchant) === false)
        {
            return false;
        }

        $riskScores = $this->merchantRiskClient->getMerchantImpersonatedDetails(
            Constants::MERCHANT_RISK_CLIENT_TYPE_ONBOARDING, $merchant->getId());

        [$isImpersonated, $action] = $this->checkImpersonationFromRiskScore($riskScores, false);

        return $isImpersonated;
    }

    public function isDedupeBlocked(Merchant\Entity $merchant): bool
    {
        if ((new MerchantRiskAlert\Service())->isRasSignupFraudMerchant($merchant->getId()) === true)
        {
            return true;
        }

        if ($this->isDedupeRequired($merchant) === false)
        {
            return false;
        }

        $riskScores = $this->merchantRiskClient->getMerchantImpersonatedDetails(
            Constants::MERCHANT_RISK_CLIENT_TYPE_ONBOARDING, $merchant->getId());

        [$isImpersonated, $action] = $this->checkImpersonationFromRiskScore($riskScores, false);

        if (($isImpersonated === false) or
            (empty($action) === true))
        {
            return false;
        }

        switch ($action)
        {
            case Constants::DEACTIVATE:
                return true;

            case Constants::UNREG_DEACTIVATE:
                if ($merchant->merchantDetail->isUnregisteredBusiness() === true)
                {
                    return true;
                }
        }

        return false;
    }

    public function match(Merchant\Entity $merchant, $force = false): array
    {
        if ((new MerchantRiskAlert\Service())->isRasSignupFraudMerchant($merchant->getId()) === true)
        {
            return [true, Constants::RAS_SIGNUP_LOCK];
        }

        if ($this->isDedupeRequired($merchant, $force) === false)
        {
            return [false, null];
        }

        $fields = [];

        $isSubMerchant = (new AccessMapCore)->isSubMerchant($merchant->getMerchantId());

        foreach (Constants::MERCHANT_RISK_CONFIG as $key => $value)
        {
            if ($isSubMerchant === true && $key == 'business_website')
            {
                continue;
            }

            foreach ($value['lists'] as $list)
            {

                $field = [
                    'field'      => $key,
                    'list'       => $list,
                    'config_key' => $value['config_key']
                ];

                $field['value'] = $this->getFieldValue($key, $merchant);

                if ($field['value'] != null)
                {
                    $fields[] = $field;
                }


            }
        }

        $riskScores = $this->merchantRiskClient->getMerchantRiskScores(
            Constants::MERCHANT_RISK_CLIENT_TYPE_ONBOARDING, $merchant->getId(), $fields);

        return $this->checkImpersonationFromRiskScore($riskScores);
    }

    public function matchAndGetMatchedMIDs(Merchant\Entity $merchant) : array
    {
        $isMatch = $this->match($merchant);

        if ($isMatch === false)
        {
            return [false, null];
        }

        $riskScores = $this->merchantRiskClient->getMerchantImpersonatedDetails(
            Constants::MERCHANT_RISK_CLIENT_TYPE_ONBOARDING, $merchant->getId());

        return [true, $this->getMatchedMIDsFromRiskScores($riskScores)];
    }

    protected function getMatchedMIDsFromRiskScores($riskScores)
    {
        $matchedMerchantIds = [];

        if (isset($riskScores[Constants::FIELDS]) === false)
        {
            return $matchedMerchantIds;
        }

        foreach ($riskScores[Constants::FIELDS] as $riskScore)
        {
            if (isset($riskScore[Constants::MATCHED_ENTITY]) === false)
            {
                continue;
            }

            foreach ($riskScore[Constants::MATCHED_ENTITY] as $matchedEntity)
            {
                if ((isset($matchedEntity[Constants::KEY]) === true) and
                    (isset($matchedEntity[Constants::VALUE]) === true) and
                    ($matchedEntity[Constants::KEY] === Entity::ID))
                {
                    array_push($matchedMerchantIds, $matchedEntity[Constants::VALUE]);
                    break;
                }
            }
        }

        return array_unique($matchedMerchantIds);
    }

    private function getMatchedFieldsFromRiskScore($riskScores): array
    {
        $matchedFields = [];

        if (isset($riskScores['fields']) === false)
        {
            return [];
        }

        $response = [];
        foreach ($riskScores['fields'] as $riskScore)
        {
            $response[$riskScore['field']][$riskScore['list']] = $riskScore['score'];
        }

        foreach (Constants::MERCHANT_RISK_ACTIONS as $action)
        {
            foreach ($action['keysToCheck'] as $key => $value)
            {
                if (isset($response[$key][$value['list']]) === false)
                {
                    break;
                }

                $matchedFields[] = [
                    $key  => $action[Constants::ACTION] ?? null
                ];
            }
        }

        return $matchedFields;
    }

    private function checkImpersonationFromRiskScore($riskScores, bool $action = true): array
    {
        if (isset($riskScores['fields']) === false)
        {
            return [false, null];
        }

        $response = [];

        foreach ($riskScores['fields'] as $riskScore)
        {
            $response[$riskScore['field']][$riskScore['list']] = $riskScore['score'];
        }

        foreach (Constants::MERCHANT_RISK_ACTIONS as $action)
        {
            $flag = true;
            foreach ($action['keysToCheck'] as $key => $value)
            {
                if (isset($response[$key][$value['list']]) === false)
                {
                    $flag = false;
                    break;
                }

                $score = $response[$key][$value['list']];

                switch ($value['matchType']) {
                    case Constants::FUZZY_MATCH:
                        if ($score < env(Constants::FUZZY_MATCH_THRESHOLD)) $flag = false;
                        break;
                }
            }

            if ($flag === true)
            {
                $actionToExecute = $action[Constants::ACTION] ?? null;

                return [true, $actionToExecute];
            }
        }

        return [false, null];
    }

    public function getDedupeTagForAction(Entity $merchantDetails, $action)
    {
        if(empty($action) === true)
        {
            return Constants::DEDUPE_TAG;
        }

        switch ($action)
        {
            case Constants::DEACTIVATE:
            case Constants::RAS_SIGNUP_LOCK:
                return Constants::DEDUPE_BLOCKED_TAG;

            case Constants::UNREG_DEACTIVATE:
                if ($merchantDetails->isUnregisteredBusiness() === true)
                {
                    return Constants::DEDUPE_BLOCKED_TAG;
                }
        }

        return Constants::DEDUPE_TAG;
    }

    public function getFieldValue(string $key, $merchant)
    {
        $value = null;

        if ($merchant->merchantDetail->getAttribute($key) != null)
        {
            $value = $merchant->merchantDetail->getAttribute($key);
        }
        else
        {
            if (in_array($key, Constants::MERCHANT_RISK_CONFIG_NOT_IN_MERCHANT_DETAILS_ENTITY) === true)
            {
                if ($this->isDedupeExperimentEnabled($merchant->getId(), RazorxTreatment::DEDUPE_FUNCTIONALITY_FOR_CLIENT_IP_ID) === true)
                {

                    if ($key === UserEntity::CLIENT_ID)
                    {
                        $value = (new UserService)->fetchVisitorIdFromCookie();
                    }
                    if ($key === Constants::CLIENT_IP)
                    {
                        $value = $this->app['request']->getClientIp();
                    }
                }
            }
        }

        return $value;
    }

    public function dedupeMatchWithExistingClientTypeMerchant(string $merchantId, string $clientType, array $fieldList)
    {
        $fields = [];
        foreach ($fieldList as $fieldToCheckDedupe)
        {
            $field          = [
                'field'      => $fieldToCheckDedupe['field'],
                'list'       => $fieldToCheckDedupe['list'],
                'config_key' => Constants::MERCHANT_RISK_FIELD_CONFIG_KEY_MAP[$fieldToCheckDedupe['field']]
            ];
            $field['value'] = $fieldToCheckDedupe['value'];
            if ($field['value'] != null)
            {
                $fields[] = $field;
            }
        }

        $response =  $this->merchantRiskClient->getMerchantRiskScores(
            $clientType, $merchantId, $fields);

        $response = (empty($response) === false) ? $response : [];

        return $response;
    }
}
