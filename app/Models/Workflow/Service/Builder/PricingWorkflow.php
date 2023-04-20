<?php

namespace RZP\Models\Workflow\Service\Builder;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Workflow\Service\Builder\Constants as Constants;
use RZP\Models\Merchant;
use RZP\Models\Pricing\Plan;
use RZP\Trace\TraceCode;

class PricingWorkflow
{
    /** @var $ba BasicAuth */
    protected $ba;

    /** @var Trace */
    protected $trace;

    protected $config;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace    = app('trace');
        $this->ba       = app('basicauth');
        $this->config   = app('config');
    }

    public function buildCreatePricingWorkflowPayload(array $payload , Merchant\Entity $merchant, Plan $plan) {
        $input = isset($payload[Constants::WORKFLOW]) ? $payload[Constants::WORKFLOW] : [];

        $input[Constants::WORKFLOW][Constants::CONFIG_ID] = $this->config->get('applications.workflows.spr_config_id');
        $input[Constants::WORKFLOW][Constants::CONFIG_VERSION] = Constants::CONFIG_VERSION_VALUE;
        $input[Constants::WORKFLOW][Constants::ORG_ID] = str_replace("org_", "", $this->ba->getOrgId());
        $input[Constants::WORKFLOW][Constants::ENTITY_ID] = $merchant->getId();
        $input[Constants::WORKFLOW][Constants::ENTITY_TYPE] = Constants::PRICINGPLAN;
        $input[Constants::WORKFLOW][Constants::OWNER_ID] = Constants::OWNER_ID_VALUE;
        $input[Constants::WORKFLOW][Constants::OWNER_TYPE] = Constants::MERCHANT;
        $input[Constants::WORKFLOW][Constants::SERVICE] = Constants::SERVICE_RX . $this->ba->getMode();

        $input[Constants::WORKFLOW][Constants::TITLE] = Constants::TITLE_VALUE.$merchant->getId();
        $input[Constants::WORKFLOW][Constants::DESCRIPTION] = Constants::DESCRIPTION_VALUE.$merchant->getName();

        $input[Constants::WORKFLOW][Constants::CREATOR_ID] =  $this->ba->getAdmin()->getId();
        $input[Constants::WORKFLOW][Constants::CREATOR_TYPE] = Constants::USER;

        $input = $this->getPricingWorkflowDiff($input, $merchant, $plan, $payload);

        $input[Constants::WORKFLOW][Constants::CALLBACK_DETAILS] = $this->getCallbackDetails($input, $merchant->getId(), $plan->getId());

        return $input;
    }

    private function getPricingWorkflowDiff(array $input, Merchant\Entity $merchant, Plan $plan, array $payload) {

        $input = $this->getMerchantDetails($input, $merchant);

        $input = $this->getLeadDetails($input, $merchant);

        $input = $this->getAccountDetails($input, $merchant);

        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::CREATED_BY] = $this->ba->getAdmin()->getName();
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::CREATED_BY_EMAIL] = $this->ba->getAdmin()->getEmail();

        if (empty($merchant->pricing) === false) {
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::OLD][Constants::PRICING_PLAN_ID] = $merchant->getPricingPlanId();
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::OLD][Constants::PRICING_PLAN] = $merchant->pricing->getPlanName();
        } else {
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::OLD][Constants::PRICING_PLAN_ID] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::OLD][Constants::PRICING_PLAN] = "";
        }

        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::PRICING_PLAN_ID] = $plan->getId();
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::PRICING_PLAN] = $plan->getPlanName();

        if (isset($payload[Constants::APPROVAL_DOC]) === true) {
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::OLD][Constants::APPROVAL_DOC] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::APPROVAL_DOC] = $payload[Constants::APPROVAL_DOC];
        }

        return $input;

    }

    private function getCallbackDetails(array $input, string $merchantID,string $pricingPlanID) {
        return [
            Constants::WORKFLOW_CALLBACKS => [
                Constants::PROCESSED => [
                    Constants::DOMAIN_STATUS => [
                        Constants::APPROVED => [
                            Constants::TYPE => Constants::BASIC,
                            Constants::METHOD => Constants::POST,
                            Constants::SERVICE => Constants::SERVICE_RX . $this->ba->getMode(),
                            Constants::URL_PATH => sprintf(Constants::ASSIGN_PRICING_PLAN_ROUTE, $merchantID),
                            Constants::HEADERS => json_decode ("{}"),
                            Constants::PAYLOAD => [
                                Constants::PRICING_PLAN_ID => $pricingPlanID,
                                Constants::SPR_APPROVED => true
                            ],
                            Constants::RESPONSE_HANDLER => [
                                Constants::TYPE => Constants::SUCCESS_STATUS_CODES,
                                Constants::SUCCESS_STATUS_CODES => [200]
                            ]
                        ],
                        Constants::REJECTED => [
                            Constants::TYPE => Constants::BASIC,
                            Constants::METHOD => Constants::POST,
                            Constants::SERVICE => Constants::SERVICE_RX . $this->ba->getMode(),
                            Constants::URL_PATH => '/',
                            Constants::HEADERS => json_decode ("{}"),
                            Constants::PAYLOAD => json_decode ("{}"),
                            Constants::RESPONSE_HANDLER => [
                                Constants::TYPE => Constants::SUCCESS_STATUS_CODES,
                                Constants::SUCCESS_STATUS_CODES => [200,400,401,404,403]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getMerchantDetails(array $input,Merchant\Entity $merchant) {
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::MERCHANT_ID] = $merchant->getId();
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::MCC] = $merchant->getCategory();
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::CATEGORY2] = $merchant->getCategory2();
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::BUSINESS_CATEGORY]
            = $merchant->merchantDetail->getBusinessCategory();
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::BUSINESS_SUB_CATEGORY]
            = $merchant->merchantDetail->getBusinessSubcategory();
        $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACTIVATION_STATUS]
            = $merchant->merchantDetail->getActivationStatus();

        return $input;
    }

    private function getLeadDetails(array $input, Merchant\Entity $merchant) {
        $dataLakeQueryLead = "select owner.name, owner.email, owner.manager_name, owner.manager_email, lead.owner_role__c
                                from salesforce.leads as lead left join salesforce.owner_details owner
                                on owner.id = lead.ownerid where lead.merchant_id__c = %s";

        $dataLakeQueryLead = sprintf($dataLakeQueryLead, $merchant->getId());

        $lakeData = [];
        try {
            $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQueryLead);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_DATALAKE_FETCH_FAILED,
                [
                    'input'       => $input,
                    "error"       => $e
                ]);
        }

        if(empty($lakeData) === false) {
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_NAME] = $lakeData[Constants::NAME];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_EMAIL] = $lakeData[Constants::EMAIL];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_SEGMENT] = $lakeData[Constants::OWNER_ROLE__C];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_MANAGER] = $lakeData[Constants::MANAGER_NAME];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_MANAGER_EMAIL] = $lakeData[Constants::MANAGER_EMAIL];
        } else {
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_NAME] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_EMAIL] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_SEGMENT] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_MANAGER] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::LEAD_MANAGER_EMAIL] = "";
        }

        return $input;
    }

    private function getAccountDetails(array $input, Merchant\Entity $merchant) {
        $dataLakeQueryAccount = "select owner.name, owner.email, owner.manager_name, owner.manager_email, account.owner_role__c
                                    from salesforce.accounts_owner as account left join salesforce.owner_details owner
                                        on owner.id = account.ownerid where account.merchant_id__c = %s";

        $dataLakeQueryAccount = sprintf($dataLakeQueryAccount, $merchant->getId());

        $lakeData = [];
        try {
            $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQueryAccount);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_DATALAKE_FETCH_FAILED,
                [
                    'input'       => $input,
                    "error"       => $e
                ]);
        }

        if(empty($lakeData) === false) {
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_NAME] = $lakeData[Constants::NAME];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_EMAIL] = $lakeData[Constants::EMAIL];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_SEGMENT] = $lakeData[Constants::OWNER_ROLE__C];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_MANAGER] = $lakeData[Constants::MANAGER_NAME];
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_MANAGER_EMAIL] = $lakeData[Constants::MANAGER_EMAIL];
        } else {
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_NAME] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_EMAIL] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_SEGMENT] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_MANAGER] = "";
            $input[Constants::WORKFLOW][Constants::DIFF][Constants::NEW][Constants::ACCOUNT_MANAGER_EMAIL] = "";
        }

        return $input;
    }

}
