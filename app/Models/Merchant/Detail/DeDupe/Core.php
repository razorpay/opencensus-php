<?php


namespace RZP\Models\Merchant\Detail\DeDupe;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Partner;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant;
use RZP\Services\MerchantRiskClient;

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

    private function isDedupeExperimentEnabled(string $merchantId): bool
    {
        $mode = $this->mode ?? Mode::LIVE;

        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            RazorxTreatment::DEDUPE_FUNCTIONALITY, $mode);

        return ($variant === 'on');
    }

    public function isDedupeRequired(Merchant\Entity $merchant)
    {
        if ($merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        if ($merchant->isLinkedAccount() === true)
        {
            return false;
        }

        $partnerCore = (new Partner\Core);

        if($partnerCore->isFullyManagedSubMerchant($merchant) === true)
        {
            return false;
        }

        if($this->isDedupeExperimentEnabled($merchant->getId()) === false)
        {
            return false;
        }

        return true;
    }

    public function isMerchantImpersonated(Merchant\Entity $merchant): bool
    {
        if($this->isDedupeRequired($merchant) === false)
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
        if($this->isDedupeRequired($merchant) === false)
        {
            return false;
        }

        $riskScores = $this->merchantRiskClient->getMerchantImpersonatedDetails(
            Constants::MERCHANT_RISK_CLIENT_TYPE_ONBOARDING, $merchant->getId());

        [$isImpersonated, $action] = $this->checkImpersonationFromRiskScore($riskScores, false);

        if($isImpersonated === false or empty($action) === true)
        {
            return false;
        }

        switch ($action)
        {
            case Constants::DEACTIVATE:
                return true;
            case Constants::UNREG_DEACTIVATE:
                if($merchant->merchantDetail->isUnregisteredBusiness() === true)
                {
                    return true;
                }
        }

        return false;
    }

    public function match(Merchant\Entity $merchant): array
    {
        if($this->isDedupeRequired($merchant) === false)
        {
            return [false, null];
        }

        $fields = [];

        foreach (Constants::MERCHANT_RISK_CONFIG as $key => $value)
        {
            foreach ($value['lists'] as $list)
            {
                if ($merchant->merchantDetail->getAttribute($key) != null)
                {
                    $fields[] = [
                        'field'     => $key,
                        'value'     => $merchant->merchantDetail->getAttribute($key),
                        'list'      => $list,
                        'config_key'=> $value['config_key']
                    ];
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

        if (isset($riskScores['fields']) === false)
        {
            return $matchedMerchantIds;
        }

        foreach ($riskScores['fields'] as $riskScore)
        {
            foreach ($riskScore['matched_entity'] as $matchedEntity)
            {
                if ($matchedEntity['key'] === 'id')
                {
                    array_push($matchedMerchantIds, $matchedEntity['value']);
                    break;
                }
            }
        }

        return $matchedMerchantIds;
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

            if($flag === true)
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
                return Constants::DEDUPE_BLOCKED_TAG;
            case Constants::UNREG_DEACTIVATE:
                if($merchantDetails->isUnregisteredBusiness() === true)
                {
                    return Constants::DEDUPE_BLOCKED_TAG;
                }
        }

        return Constants::DEDUPE_TAG;
    }
}
