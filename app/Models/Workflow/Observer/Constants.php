<?php

namespace RZP\Models\Workflow\Observer;

use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\State\Name as StateName;
use RZP\Models\Workflow\Action\Differ\Entity;

class Constants
{
    const SCHEDULED_SETTLEMENT                  = 'schedule_assign';
    const MERCHANT_ACTION                       = 'merchant_actions';
    const EDIT_PAYMENT_METHOD                   = 'merchant_put_payment_methods';
    const EMAIL_CHANGE                          = 'merchant_edit_email';
    const MERCHANT_ACTIVATION_STATUS            = 'merchant_activation_status';
    const INTERNAL_MERCHANT_ACTIVATION_STATUS   = 'internal_merchant_activation_status';

    const INTERNAL_CREATE_RISK_ACTION           = 'internal_create_risk_action';

    const MERCHANT_SAVE_BUSINESS_WEBSITE        = 'merchant_save_business_website';

    const MERCHANT_ACTIVATION_UPDATE_WEBSITE    = 'merchant_activation_update_website';

    const INCREASE_TRANSACTION_LIMIT_SELF_SERVE = 'increase_transaction_limit_self_serve';

    const MERCHANT_GSTIN_SELF_SERVE_UPDATE      = 'merchant_gstin_self_serve_update';

    const MERCHANT_BANK_ACCOUNT_CREATE          = 'merchant_bank_account_create';

    const MERCHANT_ACTIVATION_SAVE              = 'merchant_activation_save';

    const PARTNER_ACTIVATION_STATUS             = 'partner_activation_status';

    const MERCHANT_BANK_ACCOUNT_UPDATE          = 'merchant_bank_account_update';

    const ADD_ADDITIONAL_WEBSITE_SELF_SERVE     = 'add_additional_website_self_serve';

    const APPROVED_TRANSACTION_LIMIT            = 'approved_transaction_limit';
    
    const MERCHANT_INTERNATIONAL_ENABLEMENT_SUBMIT = 'merchant_international_enablement_submit';

    const APPROVE                               = 'approve';

    const BANK_ACCOUNT                          = 'Bank Account';

    const GSTIN                                 = 'GSTIN';

    const TRANSACTION_LIMIT                     = 'Transaction Limit';

    const WEBSITE                               = 'Website';

    const ADDITIONAL_WEBSITE                    = 'Additional Website';

    const NEEDS_CLARIFICATION_TRIGERRED         = 'Needs Clarification Trigerred';

    const WORKFLOW_VS_OBSERVER= [

        self::SCHEDULED_SETTLEMENT                      => ScheduleSettlementObserver::class,

        self::MERCHANT_ACTION                           => MerchantActionObserver::class,

        self::INTERNAL_CREATE_RISK_ACTION               => MerchantActionObserver::class,

        self::EDIT_PAYMENT_METHOD                       => PaymentMethodChangeObserver::class,

        self::EMAIL_CHANGE                              => EmailChangeObserver::class,

        self::MERCHANT_ACTIVATION_STATUS                => MerchantActivationStatusObserver::class,

        self::INTERNAL_MERCHANT_ACTIVATION_STATUS       => MerchantActivationStatusObserver::class,

        self::MERCHANT_SAVE_BUSINESS_WEBSITE            => MerchantSelfServeObserver::class,

        self::INCREASE_TRANSACTION_LIMIT_SELF_SERVE     => MerchantSelfServeObserver::class,

        self::MERCHANT_ACTIVATION_UPDATE_WEBSITE        => MerchantSelfServeObserver::class,

        self::MERCHANT_GSTIN_SELF_SERVE_UPDATE          => MerchantSelfServeObserver::class,

        self::MERCHANT_BANK_ACCOUNT_UPDATE              => MerchantSelfServeObserver::class,

        self::ADD_ADDITIONAL_WEBSITE_SELF_SERVE         => MerchantSelfServeObserver::class,
  
        self::MERCHANT_INTERNATIONAL_ENABLEMENT_SUBMIT  => MerchantSelfServeObserver::class,
    ];

    const ROUTE_VS_RAZORX_EXPERIMENT = [

        self::SCHEDULED_SETTLEMENT           => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::MERCHANT_ACTION                => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::EDIT_PAYMENT_METHOD            => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::EMAIL_CHANGE                   => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::MERCHANT_ACTIVATION_STATUS     => RazorxTreatment::PERFORM_ACTION_ON_INTERNAL_ACTIVATION_STATUS_CHANGE,
    ];

    const REJECTION_REASON                   = 'rejection_reason';

    const MESSAGE_SUBJECT                    = 'subject';

    const MESSAGE_BODY                       = 'body';

    const WORKFLOW_EXISTS                    = 'workflow_exists';

    const WORKFLOW_STATUS                    = 'workflow_status';

    const WORKFLOW_CREATED_AT                = 'workflow_created_at';

    const WORKFLOW_REJECTED_AT               = 'workflow_rejected_at';

    const REJECTION_REASON_MESSAGE           = 'rejection_reason_message';

    const SHOW_REJECTION_REASON_ON_DASHBOARD = 'show_on_dashboard';

    const CMMA_WORKFLOW_METRO_TOPIC = 'WORKFLOW-STATUS-CHANGE';

    const CMMA_CASE_EVENTS_KAFKA_TOPIC_ENV_VARIBLE_KEY = 'CMMA_CASE_EVENTS_TOPIC_NAME';
    const CMMA_METRO_MIGRATE_OUT_EXPERIMENT_ID_KEY     = 'app.cmma_metro_migrate_out_experiment_id';

    const ENABLE                                       = 'enable';

    const WORKFLOW_ACTION_ID = 'workflow_action_id';

    const STATUS = 'status';

    const OPEN = 'open';

    const EXECUTED = 'executed';

    CONST PERMISSION_NAME = 'permission_name';

    CONST OLD_DATA = 'old_data';

    CONST NEW_DATA = 'new_data';

    CONST ACTIVATION_STATUS = 'activation_status';

    CONST MERCHANT = 'merchant';

    const AGENT_Id = 'agent_id';
    const UNDEFINED_AGENT = 'undefined_agent';
    const AGENT_NAME     = 'agent_name';

    const EVENT_TYPE                = 'event_type';
    const CMMA_CASE_TYPE            = 'case_type';
    const CMMA_ACTIVATION_CASE_TYPE = 'activation';

    const CMMA_EVENT_WORKFLOW_STATUS_CHANGE = 'workflow_status_change';

    const MERCHANT_ACTION_METRO_BODY = [
        self::WORKFLOW_ACTION_ID => "",
        self::STATUS => StateName::APPROVED,
        Entity::ENTITY_NAME => self::MERCHANT,
        Entity::ENTITY_ID => "",
        self::PERMISSION_NAME => "",
        self::OLD_DATA => [
            self::ACTIVATION_STATUS => ""
        ],
        self::NEW_DATA => [
            self::ACTIVATION_STATUS => ""
        ]
    ];

}
