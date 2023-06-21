<?php

namespace RZP\Models\Workflow\Service\Builder;

class Constants{
    const WORKFLOW = 'workflow';
    const CONFIG_ID = 'config_id';
    const CONFIG_VERSION = 'config_version';
    const ORG_ID = 'org_id';
    const SERVICE = 'service';
    const ENTITY_ID = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const OWNER_ID = 'owner_id';
    const OWNER_TYPE = 'owner_type';
    const TITLE = 'title';
    const DESCRIPTION = 'description';
    const CREATOR_ID = 'creator_id';
    const CREATOR_TYPE = 'creator_type';

    const CONFIG_VERSION_VALUE = "1";
    const OWNER_ID_VALUE = '10000000000000';

    const USER = 'user';
    const MERCHANT = 'merchant';
    const PRICINGPLAN = 'pricingplan';
    const Payment = 'payment';

    const DIFF = 'diff';
    const OLD = 'old';
    const NEW = 'new';
    const LEAD_NAME = 'lead_name';
    const LEAD_EMAIL = 'lead_email';
    const LEAD_MANAGER = 'lead_manager';
    const LEAD_MANAGER_EMAIL = 'lead_manager_email';
    const ACCOUNT_NAME = 'account_name';
    const ACCOUNT_EMAIL = 'account_email';
    const ACCOUNT_MANAGER = 'account_manager';
    const ACCOUNT_MANAGER_EMAIL = 'account_manager_email';
    const LEAD_SEGMENT = 'segment';
    const ACCOUNT_SEGMENT = 'segment';

    const CALLBACK_DETAILS = 'callback_details';
    const WORKFLOW_CALLBACKS = 'workflow_callbacks';

    const STATE_CALLBACKS = 'state_callbacks';
    const CREATED = 'created';
    const PROCESSED = 'processed';
    const DOMAIN_STATUS = 'domain_status';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const TYPE = 'type';
    const METHOD = 'method';
    const URL_PATH = 'url_path';
    const HEADERS= 'headers';
    const PAYLOAD = 'payload';
    const PRICING_PLAN_ID = 'pricing_plan_id';
    const PRICING_PLAN = 'pricing_plan';
    const SPR_APPROVED = 'spr_approved';
    const RESPONSE_HANDLER = 'response_handler';
    const SUCCESS_STATUS_CODES = 'success_status_codes';
    const POST = 'post';
    const BASIC = 'basic';
    const SERVICE_RX                    = 'rx_';

    const PRICING_WORKFLOW_CONFIG_ID = 'KT8uUeljSM7lUp';

    const TITLE_VALUE = 'Pricing Plan approval for MID: ';

    const CB_INVOICE_WORKFLOW_TITLE_VALUE = 'Invoice verification for Payment Id: ';
    const DESCRIPTION_VALUE = 'Merchant Name: ';
    const CB_INVOICE_WORKFLOW_DESCRIPTION_VALUE = 'Invoice Verification: ';

    const ASSIGN_PRICING_PLAN_ROUTE = '/internal/merchants/%s/pricing';
    const REJECT_WORKFLOW_CALLBACK_DUMMY_ROUTE = 'wf-service/workflow/callback';

    const NAME = 'name';
    const EMAIL = 'email';
    const OWNER_ROLE__C = 'owner_role__c';
    const MANAGER_NAME = 'manager_name';
    const MANAGER_EMAIL = 'manager_email';

    const MERCHANT_ID = 'merchant_id';
    const MCC = 'mcc';
    const CATEGORY2 = 'category2';
    const BUSINESS_CATEGORY = 'business_category';
    const BUSINESS_SUB_CATEGORY = 'business_sub_category';

    const CREATED_BY = 'created_by';
    const CREATED_BY_EMAIL = 'created_by_email';

    const APPROVAL_DOC = 'approval_doc';

    const ACTIVATION_STATUS = 'activation_status';

    const SELECTED_ENTITIES = 'select_entities';
    const STATES = 'states';
    const ASSIGNEE = 'assignee';

    // CB Invoice Workflow
    const INVOICE_ID = 'invoice_id';
    const PAYMENT_ID = 'payment_id';
    const B2B_INTERNATIONAL_BANK_TRANSFERS = 'b2b_international_bank_transfers';

    const PRIORITY = 'priority';
    const PRIORITY_P0 = 'P0';
    const PRIORITY_P1 = 'P1';
    const TAGS = 'tags';
    const CB_WORKFLOW_CALLBACK_ROUTE = '/internal/cb-invoice-workflow/callback';
    const WORKFLOW_STATUS = 'workflow_status';


}
