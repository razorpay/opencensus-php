<?php

namespace RZP\Models\Workflow\Service\Builder;

use App;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Workflow\Service\Builder\Constants as Constants;

class CBInvoiceWorkflow
{
    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace    = app('trace');
        $this->ba       = app('basicauth');
        $this->config   = app('config');
    }

    public function buildInvoiceWorkflowPayload(Payment\Entity $payment, string $workflowPriority, string $paymentType) {
        $input = [];
        $input[Constants::WORKFLOW][Constants::CONFIG_ID] =  $this->config->get('applications.workflows.cross_border.invoice_verification_config_id');
        $input[Constants::WORKFLOW][Constants::CONFIG_VERSION] = Constants::CONFIG_VERSION_VALUE;
        $input[Constants::WORKFLOW][Constants::ORG_ID] = str_replace("org_", "", $this->ba->getOrgId());
        $input[Constants::WORKFLOW][Constants::ENTITY_ID] = $payment->getId();
        $input[Constants::WORKFLOW][Constants::ENTITY_TYPE] = Constants::Payment;
        $input[Constants::WORKFLOW][Constants::OWNER_ID] = Constants::OWNER_ID_VALUE;
        $input[Constants::WORKFLOW][Constants::OWNER_TYPE] = Constants::MERCHANT;
        $input[Constants::WORKFLOW][Constants::SERVICE] = Constants::SERVICE_RX . $this->ba->getMode();

        $input[Constants::WORKFLOW][Constants::TITLE] = Constants::CB_INVOICE_WORKFLOW_TITLE_VALUE.$payment->getId();
        $input[Constants::WORKFLOW][Constants::DESCRIPTION] = Constants::CB_INVOICE_WORKFLOW_DESCRIPTION_VALUE;

        $input[Constants::WORKFLOW][Constants::DIFF] = $this->getInvoiceWorkflowDiff($payment, $workflowPriority, $paymentType);
        $input[Constants::WORKFLOW][Constants::CREATOR_ID] =  $payment->getMerchantId();
        $input[Constants::WORKFLOW][Constants::CREATOR_TYPE] = Constants::USER;
        $input[Constants::WORKFLOW][Constants::CALLBACK_DETAILS] = $this->getCallbackDetails($payment, $workflowPriority);
        return $input;
    }

    private function getInvoiceWorkflowDiff(Payment\Entity $payment, string $priority, string $paymentType) {
        $input = [];
        $input[Constants::NEW][Constants::INVOICE_ID] = $payment->getReference2();
        $input[Constants::NEW][Constants::PAYMENT_ID] = $payment->getId();
        $input[Constants::NEW][Constants::MERCHANT_ID] = $payment->getMerchantId();
        $input[Constants::NEW][Constants::TAGS] = [
            $priority,
            $paymentType,
        ];
        return $input;
    }

    private function getCallbackDetails(Payment\Entity $payment, string $priority) {
        $input = [
            Constants::WORKFLOW_CALLBACKS => [
                Constants::PROCESSED => [
                    Constants::DOMAIN_STATUS => [
                        Constants::APPROVED => [
                            Constants::TYPE => Constants::BASIC,
                            Constants::METHOD => Constants::POST,
                            Constants::SERVICE => Constants::SERVICE_RX . $this->ba->getMode(),
                            Constants::URL_PATH => Constants::CB_WORKFLOW_CALLBACK_ROUTE,
                            Constants::HEADERS => json_decode ("{}"),
                            Constants::PAYLOAD => [
                                Constants::PAYMENT_ID => $payment->getId(),
                                Constants::WORKFLOW_STATUS => Constants::APPROVED,
                                Constants::MERCHANT_ID => $payment->getMerchantId(),
                                Constants::PRIORITY => $priority,
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
                            Constants::URL_PATH => Constants::CB_WORKFLOW_CALLBACK_ROUTE,
                            Constants::HEADERS => json_decode ("{}"),
                            Constants::PAYLOAD => [
                                Constants::PAYMENT_ID => $payment->getId(),
                                Constants::WORKFLOW_STATUS => Constants::REJECTED,
                                Constants::MERCHANT_ID => $payment->getMerchantId(),
                                Constants::PRIORITY => $priority,
                            ],
                            Constants::RESPONSE_HANDLER => [
                                Constants::TYPE => Constants::SUCCESS_STATUS_CODES,
                                Constants::SUCCESS_STATUS_CODES => [200]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $input;
    }
}
