<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\Action;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Merchant\Action as MerchantAction;
use RZP\Models\Merchant\Validator as MerchantValidator;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;

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
            case MerchantAction::ENABLE_INTERNATIONAL:
                $validator->validateEnableInternational();
                break;
            case MerchantAction::DISABLE_INTERNATIONAL:
                $validator->validateDisableInternational();
                break;
        }
    }

    protected function getParamsForMerchantAction($riskAction, $riskAttributes)
    {
        if (in_array($riskAction, Merchant\Constants::RISK_CONSTRUCTIVE_ACTION_LIST) === true)
        {
            return [
                Constants::CLEAR_RISK_TAGS => $riskAttributes[Constants::CLEAR_RISK_TAGS],
            ];
        }

        if ($riskAction == Action::ENABLE_INTERNATIONAL)
        {
            return [
                ProductInternationalMapper::INTERNATIONAL_PRODUCTS => $riskAttributes[ProductInternationalMapper::INTERNATIONAL_PRODUCTS],
            ];
        }

        $params = [
            Constants::TRIGGER_COMMUNICATION => $riskAttributes[Constants::TRIGGER_COMMUNICATION],
        ];

        if (isset($riskAttributes[Constants::RISK_TAG]) === true)
        {
            $params[Constants::RISK_TAG] = $riskAttributes[Constants::RISK_TAG];
        }

        return $params;

    }

    public function validateRiskAttributes(array $input)
    {
        if(isset($input[Constants::RISK_ATTRIBUTES]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Risk Attributes are not provided', null, $input);
        }

        $riskAttributes = $input[Constants::RISK_ATTRIBUTES];

        if(is_array($riskAttributes) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Risk Attributes provided is malformed', null, $input);
        }

        // assuming that this is already validated at the bulk merchant action layer
        $riskAction = $input['action'];

        if (in_array($riskAction, Merchant\Constants::RISK_CONSTRUCTIVE_ACTION_LIST) === true)
        {
            (new Validator())->validateInput(
                Constants::CREATE_CONSTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR,
                $riskAttributes);
        }
        else
        {
            if ($riskAction == Action::ENABLE_INTERNATIONAL)
            {
                (new Validator())->validateInput(
                    Constants::CREATE_ENABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR,
                    $riskAttributes);
            }
            else
            {
                if ($riskAction == Action::DISABLE_INTERNATIONAL)
                {
                    (new Validator())->validateInput(
                        Constants::CREATE_DISABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR,
                        $riskAttributes);
                }
                else
                {
                    (new Validator())->validateInput(
                        Constants::CREATE_DESTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR,
                        $riskAttributes);
                }
            }
        }
    }

    public function createRiskWorkflowAction($input, $maker = null)
    {
        try {
            $riskAction = $input[Constants::ACTION];

            $merchantId= $input[Constants::MERCHANT_ID];

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $this->validateMerchantForAction($riskAction, $merchant);

            $riskAttributes = $input[Constants::RISK_ATTRIBUTES];

            $bulkActionId = $input[Constants::BULK_WORKFLOW_ACTION_ID] ?? null;

            $tags = $this->getTagsFromRiskAttributes($riskAttributes);

            $riskAttributesParams = $this->getParamsForMerchantAction($riskAction, $riskAttributes);

            if (isset($input['entity_id']) === true)
            {
                $tags[] = sprintf("%s%s", Constants::BULK_WORKFLOW_GROUP_TAG_PREFIX, $input['entity_id']);
            }

            $routePermission = Permission\Name::$actionMap[$riskAction];

            $internationalProducts = null;
            if (isset($riskAttributes[ProductInternationalMapper::INTERNATIONAL_PRODUCTS]) === true)
            {
                $internationalProducts = $riskAttributes[ProductInternationalMapper::INTERNATIONAL_PRODUCTS];
                unset($riskAttributes[ProductInternationalMapper::INTERNATIONAL_PRODUCTS]);
            }

            $input = [
                Constants::ACTION          => $riskAction,
                'use_workflows'            => false,
                Constants::RISK_ATTRIBUTES => $riskAttributesParams,
            ];

            if ($riskAction === Action::ENABLE_INTERNATIONAL)
            {
                $input[ProductInternationalMapper::INTERNATIONAL_PRODUCTS] = $internationalProducts;
            }

            if (isset($bulkActionId))
            {
                $input[Constants::BULK_WORKFLOW_ACTION_ID] = $bulkActionId;
            }

            $diffData = [
                'id'                       => $merchantId,
                Constants::ACTION          => $riskAction,
                Constants::RISK_ATTRIBUTES => $riskAttributes,
            ];
            // NOTE: given the use case can generate the diff payload directly,
            // but for consistency reasons calling createDiff
            // No need for redacting fields as no sensitive field is being used

            $diff = (new Differ\Core)->createDiff([], $diffData);

            $workflowAction = $this->app['workflow'];

            if (isset($maker) === true)
            {
                $workflowAction = $workflowAction
                    ->setMakerFromAuth(false)
                    ->setWorkflowMaker($maker);
            }

            $workflowAction = $workflowAction
                ->setPermission($routePermission)
                ->setTags($tags)
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

            return $workflowAction;
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
