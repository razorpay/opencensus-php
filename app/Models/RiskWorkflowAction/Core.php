<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Merchant\Action as MerchantAction;
use RZP\Models\Merchant\Validator as MerchantValidator;

class Core extends Base\Core
{
    /**
     * Validates the merchant wrt action
     * @param $action
     * @param $merchantId
     * @return string
     */
    public function validateMerchantForAction($action, $merchant)
    {
        $validator =  new MerchantValidator($merchant);

        switch ($action)
        {
            case MerchantAction::SUSPEND:
                $validator->validateSuspend();
                break;
            case MerchantAction::UNSUSPEND:
                $validator->validateUnsuspend();
                break;
            case MerchantAction::HOLD_FUNDS:
                $validator->validateHoldFunds();
                break;
            case MerchantAction::RELEASE_FUNDS:
                $validator->validateReleaseFunds();
                break;
            case MerchantAction::LIVE_DISABLE:
                $validator->validateLiveDisable();
                break;
            case MerchantAction::LIVE_ENABLE:
                $validator->validateLiveEnable();
                break;
        }
    }

    protected function getParamsForMerchantAction($riskAction, $riskAttributes)
    {
        if (in_array($riskAction, Merchant\Constants::RISK_CONSTRUCTIVE_ACTION_LIST) === false)
        {
            $params = [
                Constants::TRIGGER_COMMUNICATION => $riskAttributes[Constants::TRIGGER_COMMUNICATION],
            ];

            if (isset($riskAttributes[Constants::RISK_TAG]) === true)
            {
                $params[Constants::RISK_TAG] = $riskAttributes[Constants::RISK_TAG];
            }

            return $params;
        }

        return [
            Constants::CLEAR_RISK_TAGS    => $riskAttributes[Constants::CLEAR_RISK_TAGS],
        ];
    }

    public function createRiskWorkflowAction($merchantId, $maker, $input)
    {
        try {
            $riskAction = $input[Constants::ACTION];

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $this->validateMerchantForAction($riskAction, $merchant);

            $riskAttributes = $input[Constants::RISK_ATTRIBUTES];

            $tags = $this->getTagsFromRiskAttributes($riskAttributes);

            $riskAttributesParams = $this->getParamsForMerchantAction($riskAction, $riskAttributes);

            $tags[] = sprintf("%s%s", Constants::BULK_WORKFLOW_GROUP_TAG_PREFIX, $input['entity_id']);

            $routePermission = Permission\Name::$actionMap[$riskAction];

            $input = [
                Constants::ACTION                                   => $riskAction,
                'use_workflows'                                     => false,
                Constants::RISK_ATTRIBUTES                          => $riskAttributesParams,
            ];

            $diffData = [
                'id'                                    => $merchantId,
                Constants::ACTION                       => $riskAction,
                Constants::RISK_ATTRIBUTES              => $riskAttributes,
            ];
            // NOTE: given the use case can generate the diff payload directly,
            // but for consistency reasons calling createDiff
            // No need for redacting fields as no sensitive field is being used

            $diff = (new Differ\Core)->createDiff([], $diffData);

            $workflowAction = $this->app['workflow']
                ->setPermission($routePermission)
                ->setTags($tags)
                ->setMakerFromAuth(false)
                ->setWorkflowMaker($maker)
                ->setWorkflowMakerType(MakerType::ADMIN)
                ->setRouteName(Constants::RISK_ACTION_ROUTE_NAME)
                ->setController(Constants::RISK_ACTION_ROUTE_CONTROLLER)
                ->setRouteParams(['id' => $merchantId])
                ->setEntityAndId($merchant->getEntity(), $merchantId)
                ->setInput($input)
                ->setDiff($diff)
                ->trigger();

            $this->trace->info(TraceCode::CREATE_RISK_ACTION,
               [
                   'merchant_id'    => $merchantId,
                   'wf_action_id'   => $workflowAction['id'],
               ]);

            return $workflowAction['id'];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_RISK_ACTION_CREATE_MERCHANT_WORKFLOW_FAILED,
                [
                    'merchantId' => $merchantId
                ]);

            throw $e;
        }
    }

    public function getTagsFromRiskAttributes($riskAttributes): array
    {
        $tag = [];

        if (isset($riskAttributes[Constants::RISK_TAG]) === true)
        {
            $tag[] = Constants::RISK_TAG_PREFIX . $riskAttributes[Constants::RISK_TAG];
        }

        if (isset($riskAttributes[Constants::RISK_SOURCE]) === true)
        {
            $tag[] = Constants::RISK_SOURCE_PREFIX . $riskAttributes[Constants::RISK_SOURCE];
        }

        if(isset($riskAttributes[Constants::RISK_REASON]) === true)
        {
            $tag[] = Constants::RISK_REASON_PREFIX . $riskAttributes[Constants::RISK_REASON];
        }

        return $tag;
    }
}
