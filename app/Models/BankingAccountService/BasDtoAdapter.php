<?php

namespace RZP\Models\BankingAccountService;

use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\BankingAccount\Activation\Detail\Entity as ActivationDetailEntity;
use RZP\Models\BankingAccount\Activation\Detail\Entity as ActivationDetailsEntity;
use RZP\Tests\Functional\Fixtures\Entity\Admin;

class BasDtoAdapter
{
    const APPLICATION_ID          = 'application_id';
    const MERCHANT_ID             = 'merchant_id';
    const APPLICATION_TRACKING_ID = 'application_tracking_id';
    const APPLICATION_STATUS      = 'application_status';
    const SUB_STATUS              = 'sub_status';
    const CREATED_AT              = 'created_at';
    const ASSIGNEE_TEAM           = 'assignee_team';
    const METADATA                = 'metadata';

    // API Top Level fields
    const BANKING_ACCOUNT_ACTIVATION_DETAILS = 'banking_account_activation_details';
    const REVIEWERS                          = 'reviewers';
    const SPOCS                              = 'spocs';
    const OPS_MX_POCS                        = 'ops_mx_pocs';
    const BANKING_ACCOUNT_DETAILS            = 'banking_account_details';

    // BAS Top Level fields
    const BUSINESS                     = 'business';
    const PERSON                       = 'person';
    const BANKING_ACCOUNT              = 'banking_account';
    const BANKING_ACCOUNT_APPLICATION  = 'banking_account_application';
    const PARTNER_BANK_APPLICATION     = 'partner_bank_application';
    const ACCOUNT_MANAGERS             = 'account_managers';
    const CREDENTIALS                  = 'credentials';

    const API_TO_BAS_INPUT_MAPPING = [
        "account_ifsc"                                                                     => "banking_account.ifsc",
        "account_number"                                                                   => "banking_account.account_number",
        "status"                                                                           => "banking_account_application.application_status",
        "sub_status"                                                                       => "banking_account_application.sub_status",
        "bank_internal_status"                                                             => "banking_account_application.bank_status",
        "pincode"                                                                          => "business.registered_address_details.address_pin_code",
        // "balance_id"                                                                       => "banking_account.balance_id", // balance id does not get updated
        "bank_internal_reference_number"                                                   => "banking_account_application.application_number",
        "beneficiary_name"                                                                 => "banking_account.beneficiary_details.name",
        "account_currency"                                                                 => "banking_account.account_currency",
        "beneficiary_pin"                                                                  => "banking_account.beneficiary_details.pincode",
        "beneficiary_email"                                                                => "banking_account.beneficiary_details.email",
        "beneficiary_mobile"                                                               => "banking_account.beneficiary_details.mobile",
        "beneficiary_city"                                                                 => "banking_account.beneficiary_details.city",
        "beneficiary_state"                                                                => "banking_account.beneficiary_details.state",
        "beneficiary_country"                                                              => "banking_account.beneficiary_details.country",
        "beneficiary_address1"                                                             => "banking_account.beneficiary_details.address1",
        "beneficiary_address2"                                                             => "banking_account.beneficiary_details.address2",
        "beneficiary_address3"                                                             => "banking_account.beneficiary_details.address3",
        "bank_reference_number"                                                            => "banking_account_application.application_tracking_id",
        "account_activation_date"                                                          => "banking_account.metadata.bank_account_open_date",
        "username"                                                                         => "credentials.auth_username",
        "password"                                                                         => "credentials.auth_password",
        "reference1"                                                                       => "credentials.corp_id",
        "details.client_id"                                                                => "credentials.client_id",
        "details.client_secret"                                                            => "credentials.client_secret",
        "details.merchant_email"                                                           => "credentials.email",
        "details.merchant_password"                                                        => "credentials.dev_portal_password",
        "activation_detail.merchant_poc_name"                                              => "person.first_name",
        "activation_detail.merchant_poc_email"                                             => "person.email_id",
        "activation_detail.merchant_poc_designation"                                       => "person.role_in_business",
        "activation_detail.merchant_poc_phone_number"                                      => "person.phone_number",
        "activation_detail.merchant_documents_address"                                     => "business.registered_address",
        "activation_detail.merchant_city"                                                  => "business.registered_address_details.address_city",
        "activation_detail.merchant_region"                                                => "business.registered_address_details.address_region",
        "activation_detail.business_category"                                              => "business.constitution",
        "activation_detail.average_monthly_balance"                                        => "banking_account_application.average_monthly_balance",
        "activation_detail.expected_monthly_gmv"                                           => "banking_account_application.expected_monthly_gmv",
        "activation_detail.initial_cheque_value"                                           => "banking_account_application.metadata.initial_cheque_value",
        "activation_detail.declaration_step"                                               => "banking_account_application.metadata.declaration_step",
        "activation_detail.account_type"                                                   => "banking_account_application.bank_account_type",
        "activation_detail.is_documents_walkthrough_complete"                              => "banking_account_application.metadata.additional_details.is_documents_walkthrough_complete",
        "activation_detail.sales_team"                                                     => "banking_account_application.sales_team",
        "activation_detail.sales_poc_phone_number"                                         => "account_managers.sales_poc.phone_number",
        "activation_detail.sales_poc_id"                                                   => "account_managers.sales_poc.rzp_admin_id",
        "activation_detail.comment"                                                        => "banking_account_application.comment",
        // for array comment input
        "activation_detail.comment.comment"                                                => "banking_account_application.comment_input.comment",
        "activation_detail.comment.source_team"                                            => "banking_account_application.comment_input.source_team",
        "activation_detail.comment.source_team_type"                                       => "banking_account_application.comment_input.source_team_type",
        "activation_detail.comment.type"                                                   => "banking_account_application.comment_input.type",
        "activation_detail.comment.added_at"                                               => "banking_account_application.comment_input.added_at",
        // "activation_detail.ops_poc_id"                                                     => "account_managers.ops_poc.rzp_admin_id", // Not used to set ops poc
        "activation_detail.ops_mx_poc_id"                                                  => "account_managers.ops_mx_poc.rzp_admin_id",
        "activation_detail.assignee_team"                                                  => "banking_account_application.assignee_team",
        "activation_detail.rm_name"                                                        => "partner_bank_application.rm_details.rm_name",
        "activation_detail.rm_phone_number"                                                => "partner_bank_application.rm_details.rm_phone_number",
        "activation_detail.business_name"                                                  => "business.name",
        "activation_detail.business_type"                                                  => "business.industry_type",
        "activation_detail.business_pan"                                                   => "business.pan_number",
        "activation_detail.merchant_state"                                                 => "business.registered_address_details.address_state",
        // "activation_detail.application_type"                                               => "banking_account_application.application_type", // not used anymore
        "activation_detail.bank_poc_user_id"                                               => "account_managers.bank_poc.rzp_admin_id", // bank_poc.user_id also stored as rzp_admin_id
        "activation_detail.branch_code"                                                    => "partner_bank_application.branch_code",
        "activation_detail.customer_appointment_date"                                      => "partner_bank_application.doc_collection_details.customer_appointment_date",
        "activation_detail.rm_employee_code"                                               => "partner_bank_application.rm_details.rm_employee_code",
        "activation_detail.rm_assignment_type"                                             => "partner_bank_application.rm_details.rm_assignment_type",
        "activation_detail.doc_collection_date"                                            => "partner_bank_application.doc_collection_details.doc_collection_date",
        "activation_detail.account_login_date"                                             => "banking_account_application.metadata.account_login_date",
        "activation_detail.account_open_date"                                              => "partner_bank_application.account_opening_details.account_open_date",
        "activation_detail.account_opening_ir_close_date"                                  => "partner_bank_application.account_opening_details.account_opening_ir_close_date",
        "activation_detail.account_opening_ftnr"                                           => "partner_bank_application.account_opening_details.account_opening_ftnr",
        "activation_detail.account_opening_ftnr_reasons"                                   => "partner_bank_application.account_opening_details.account_opening_ftnr_reasons",
        "activation_detail.api_ir_closed_date"                                             => "partner_bank_application.api_onboarding_details.api_ir_closed_date",
        "activation_detail.ldap_id_mail_date"                                              => "partner_bank_application.api_onboarding_details.ldap_id_mail_date",
        "activation_detail.api_onboarding_ftnr"                                            => "partner_bank_application.api_onboarding_details.api_onboarding_ftnr",
        "activation_detail.api_onboarding_ftnr_reasons"                                    => "partner_bank_application.api_onboarding_details.api_onboarding_ftnr_reasons",
        "activation_detail.upi_credential_received_date"                                   => "partner_bank_application.account_activation_details.upi_credential_received_date",
        "activation_detail.rzp_ca_activated_date"                                          => "partner_bank_application.account_activation_details.rzp_ca_activated_date",
        // "activation_detail.drop_off_date"                                                  => "partner_bank_application.account_activation_details.drop_off_date", // will be set by the update logic

        "activation_detail.booking_date_and_time"                                          => "banking_account_application.metadata.additional_details.booking_date_and_time",
        "activation_detail.additional_details.sales_pitch_completed"                       => "banking_account_application.metadata.additional_details.sales_pitch_completed",
        "activation_detail.additional_details.calendly_slot_booking_completed"             => "banking_account_application.metadata.additional_details.calendly_slot_booking_completed",
        "activation_detail.additional_details.entity_mismatch_status"                      => "banking_account_application.metadata.additional_details.entity_mismatch_status",
        "activation_detail.additional_details.green_channel"                               => "banking_account_application.metadata.additional_details.green_channel",
        "activation_detail.additional_details.is_documents_walkthrough_complete"           => "banking_account_application.metadata.additional_details.is_documents_walkthrough_complete",
        "activation_detail.additional_details.booking_id"                                  => "banking_account_application.metadata.additional_details.booking_id",
        "activation_detail.additional_details.cin"                                         => "banking_account_application.metadata.additional_details.cin",
        "activation_detail.additional_details.gstin"                                       => "banking_account_application.metadata.additional_details.gstin",
        "activation_detail.additional_details.llpin"                                       => "banking_account_application.metadata.additional_details.llpin",
        "activation_detail.additional_details.skip_dwt"                                    => "banking_account_application.metadata.additional_details.skip_dwt",
        "activation_detail.additional_details.dwt_completed_timestamp"                     => "banking_account_application.metadata.additional_details.dwt_completed_timestamp",
        "activation_detail.additional_details.dwt_scheduled_timestamp"                     => "banking_account_application.metadata.additional_details.dwt_scheduled_timestamp",
        "activation_detail.additional_details.feet_on_street"                              => "banking_account_application.metadata.additional_details.feet_on_street",
        "activation_detail.additional_details.api_onboarded_date"                          => "banking_account_application.metadata.additional_details.api_onboarded_date",
        "activation_detail.additional_details.mid_office_poc_name"                         => "banking_account_application.metadata.additional_details.mid_office_poc_name",
        "activation_detail.additional_details.docket_delivered_date"                       => "banking_account_application.metadata.additional_details.docket_delivered_date",
        "activation_detail.additional_details.docket_estimated_delivery_date"              => "banking_account_application.metadata.additional_details.docket_estimated_delivery_date",
        "activation_detail.additional_details.docket_requested_date"                       => "banking_account_application.metadata.additional_details.docket_requested_date",
        "activation_detail.additional_details.courier_tracking_id"                         => "banking_account_application.metadata.additional_details.courier_tracking_id",
        "activation_detail.additional_details.courier_service_name"                        => "banking_account_application.metadata.additional_details.courier_service_name",
        "activation_detail.additional_details.gstin_prefilled_address"                     => "banking_account_application.metadata.additional_details.gstin_prefilled_address",
        "activation_detail.additional_details.api_onboarding_login_date"                   => "banking_account_application.metadata.additional_details.api_onboarding_login_date",
        "activation_detail.additional_details.application_initiated_from"                  => "banking_account_application.metadata.additional_details.application_initiated_from",
        "activation_detail.additional_details.account_opening_webhook_date"                => "banking_account_application.metadata.additional_details.account_opening_webhook_date",
        "activation_detail.additional_details.agree_to_allocated_bank_and_amb"             => "banking_account_application.metadata.additional_details.agree_to_allocated_bank_and_amb",
        "activation_detail.additional_details.rbl_new_onboarding_flow_declarations"        => "banking_account_application.metadata.additional_details.rbl_new_onboarding_flow_declarations",
        // below fields are not sent from FE, added only for documentation
        // "activation_detail.additional_details.skip_mid_office_call"                        => "banking_account_application.metadata.additional_details.skip_mid_office_call",
        // "activation_detail.additional_details.appointment_source"                          => "banking_account_application.metadata.additional_details.appointment_source",
        // "activation_detail.additional_details.sent_docket_automatically"                   => "banking_account_application.metadata.additional_details.sent_docket_automatically",
        // "activation_detail.additional_details.reasons_to_not_send_docket"                  => "banking_account_application.metadata.additional_details.reasons_to_not_send_docket",
        "activation_detail.additional_details.docket_not_delivered_reason"                 => "banking_account_application.metadata.additional_details.docket_not_delivered_reason",
        "activation_detail.additional_details.dwt_response"                                => "banking_account_application.metadata.additional_details.dwt_response",
        "activation_detail.additional_details.business_details"                            => "banking_account_application.metadata.additional_details.business_details",
        "activation_detail.additional_details.proof_of_entity"                             => "banking_account_application.metadata.additional_details.proof_of_entity",
        "activation_detail.additional_details.proof_of_address"                            => "banking_account_application.metadata.additional_details.proof_of_address",
        "activation_detail.additional_details.verified_addresses"                          => "banking_account_application.metadata.additional_details.verified_addresses",
        "activation_detail.additional_details.verified_constitutions"                      => "banking_account_application.metadata.additional_details.verified_constitutions",
        "activation_detail.additional_details.ops_follow_up_date"                          => "banking_account_application.metadata.ops_follow_up_date",
        "activation_detail.additional_details.ops_revived_lead"                            => "banking_account_application.metadata.ops_revived_lead",
        "activation_detail.verification_date"                                              => "banking_account_application.metadata.verification_date",
        "activation_detail.additional_details.entity_proof_documents"                      => "banking_account_application.application_specific_fields.entity_proof_documents",

        "activation_detail.rbl_activation_details.revised_declaration"                     => "partner_bank_application.auxiliary_details.revised_declaration",
        "activation_detail.rbl_activation_details.office_different_locations"              => "partner_bank_application.lead_details.office_different_locations",
        "activation_detail.rbl_activation_details.bank_due_date"                           => "partner_bank_application.lead_details.bank_due_date",
        "activation_detail.rbl_activation_details.lead_ir_number"                          => "partner_bank_application.lead_details.lead_ir_number",
        "activation_detail.rbl_activation_details.bank_poc_assigned_date"                  => "partner_bank_application.lead_details.bank_poc_assigned_date",
        "activation_detail.rbl_activation_details.ip_cheque_value"                         => "partner_bank_application.doc_collection_details.ip_cheque_value",
        "activation_detail.rbl_activation_details.api_docs_delay_reason"                   => "partner_bank_application.doc_collection_details.api_docs_delay_reason",
        "activation_detail.rbl_activation_details.api_docs_received_with_ca_docs"          => "partner_bank_application.doc_collection_details.api_docs_received_with_ca_docs",
        // "activation_detail.rbl_activation_details.customer_appointment_booking_date"       => "partner_bank_application.doc_collection_details.customer_appointment_booking_date", // set automatically when customer_appointment_date changes
        "activation_detail.rbl_activation_details.sr_number"                               => "partner_bank_application.account_opening_details.sr_number",
        "activation_detail.rbl_activation_details.account_opening_ir_number"               => "partner_bank_application.account_opening_details.account_opening_ir_number",
        "activation_detail.rbl_activation_details.case_login_different_locations"          => "partner_bank_application.lead_details.case_login_different_locations",
        "activation_detail.rbl_activation_details.account_opening_tat_exception_reason"    => "partner_bank_application.account_opening_details.account_opening_tat_exception_reason",
        "activation_detail.rbl_activation_details.api_ir_number"                           => "partner_bank_application.api_onboarding_details.api_ir_number",
        "activation_detail.rbl_activation_details.api_onboarding_tat_exception_reason"     => "partner_bank_application.api_onboarding_details.api_onboarding_tat_exception_reason",
        "activation_detail.rbl_activation_details.upi_credential_not_done_remarks"         => "partner_bank_application.account_activation_details.upi_credential_not_done_remarks",
        "activation_detail.rbl_activation_details.promo_code"                              => "partner_bank_application.auxiliary_details.promo_code",
        "activation_detail.rbl_activation_details.aof_shared_with_mo"                      => "partner_bank_application.auxiliary_details.aof_shared_with_mo",
        "activation_detail.rbl_activation_details.wa_message_sent_date"                    => "partner_bank_application.auxiliary_details.wa_message_sent_date",
        "activation_detail.rbl_activation_details.first_calling_time"                      => "partner_bank_application.auxiliary_details.first_calling_time",
        "activation_detail.rbl_activation_details.pcarm_manager_name"                      => "partner_bank_application.rm_details.pcarm_manager_name",
        "activation_detail.rbl_activation_details.wa_message_response_date"                => "partner_bank_application.auxiliary_details.wa_message_response_date",
        "activation_detail.rbl_activation_details.aof_not_shared_reason"                   => "partner_bank_application.auxiliary_details.aof_not_shared_reason",
        "activation_detail.rbl_activation_details.ca_beyond_tat"                           => "partner_bank_application.auxiliary_details.ca_beyond_tat",
        "activation_detail.rbl_activation_details.ca_beyond_tat_dependency"                => "partner_bank_application.auxiliary_details.ca_beyond_tat_dependency",
        "activation_detail.rbl_activation_details.lead_referred_by_rbl_staff"              => "partner_bank_application.auxiliary_details.lead_referred_by_rbl_staff",
        "activation_detail.rbl_activation_details.api_docket_related_issue"                => "partner_bank_application.api_onboarding_details.api_docket_related_issue",
        "activation_detail.rbl_activation_details.api_onboarding_tat_exception"            => "partner_bank_application.api_onboarding_details.api_onboarding_tat_exception",
        "activation_detail.rbl_activation_details.account_opening_tat_exception"           => "partner_bank_application.account_opening_details.account_opening_tat_exception",
        "activation_detail.rbl_activation_details.aof_shared_discrepancy"                  => "partner_bank_application.auxiliary_details.aof_shared_discrepancy",
        "activation_detail.rbl_activation_details.ca_service_first_query"                  => "partner_bank_application.auxiliary_details.ca_service_first_query",
        "activation_detail.rbl_activation_details.second_calling_time"                     => "partner_bank_application.auxiliary_details.second_calling_time",
        "activation_detail.rbl_activation_details.lead_ir_status"                          => "partner_bank_application.auxiliary_details.lead_ir_status",
        "activation_detail.rbl_activation_details.api_service_first_query"                 => "partner_bank_application.auxiliary_details.api_service_first_query",
        "activation_detail.rbl_activation_details.api_beyond_tat_dependency"               => "partner_bank_application.auxiliary_details.api_beyond_tat_dependency",
        "activation_detail.rbl_activation_details.api_beyond_tat"                          => "partner_bank_application.auxiliary_details.api_beyond_tat",
    ];

    const DATE_FIELDS_TO_TRANSFORM_INTO_SECONDS = [

        // BAS Fields
        'banking_account_application.created_at',
        'banking_account_application.updated_at',
    ];

    const DATE_FIELDS_TO_TRANSFORM_INTO_MS = [
        'banking_account_application.comment_input.added_at'
    ];

    const STRING_FIELD_TO_TRANSFORM_INTO_INT = [

        'activation_detail.average_monthly_balance',
        'activation_detail.expected_monthly_gmv',
        'activation_detail.initial_cheque_value',
        'activation_detail.customer_appointment_date',
        'activation_detail.ldap_id_mail_date',
        'activation_detail.rzp_ca_activated_date',
        'activation_detail.upi_credential_received_date',
        'activation_detail.rbl_activation_details.wa_message_response_date',
        'activation_detail.rbl_activation_details.wa_message_sent_date',
        'activation_detail.doc_collection_date',
        'activation_detail.account_login_date',
        'activation_detail.account_open_date',
        'activation_detail.api_ir_closed_date',
        'activation_detail.comment.added_at',
        'account_activation_date',
    ];

    /**
     * apiInputPreprocessors holds the handler for preprocessing API input before transforming into BAS input
     *
     * @var array
     */
    protected $apiInputPreprocessors;

    /**
     * basResponsePreprocessors holds the handler for preprocessing BAS response before transforming into API response
     *
     * @var array
     */
    protected $basResponsePreprocessors;

    const BAS_TO_API_RESPONSE_MAPPING = [
        'id'                                                                            => 'banking_account_application.id',
        'merchant_id'                                                                   => 'business.merchant_id',
        'created_at'                                                                    => 'banking_account_application.created_at',
        'updated_at'                                                                    => 'banking_account_application.updated_at',
        'status'                                                                        => 'banking_account_application.application_status',
        'pincode'                                                                       => 'business.registered_address_details.address_pin_code',
        'account_currency'                                                              => 'banking_account.account_currency',
        'bank_reference_number'                                                         => 'banking_account_application.application_tracking_id',
        'sub_status'                                                                    => 'banking_account_application.sub_status',
        'fts_fund_account_id'                                                           => 'banking_account.fts_fund_account_id',
        'account_ifsc'                                                                  => 'banking_account.ifsc',
        'account_number'                                                                => 'banking_account.account_number',
        'beneficiary_pin'                                                               => 'banking_account.beneficiary_details.pincode',
        'bank_internal_status'                                                          => 'banking_account_application.bank_status',
        'bank_internal_reference_number'                                                => 'banking_account_application.application_number',
        'beneficiary_name'                                                              => 'banking_account.beneficiary_details.name',
        'beneficiary_email'                                                             => 'banking_account.beneficiary_details.email',
        'beneficiary_mobile'                                                            => 'banking_account.beneficiary_details.mobile',
        'beneficiary_city'                                                              => 'banking_account.beneficiary_details.city',
        'beneficiary_state'                                                             => 'banking_account.beneficiary_details.state',
        'beneficiary_country'                                                           => 'banking_account.beneficiary_details.country',
        'beneficiary_address1'                                                          => 'banking_account.beneficiary_details.address1',
        'beneficiary_address2'                                                          => 'banking_account.beneficiary_details.address2',
        'beneficiary_address3'                                                          => 'banking_account.beneficiary_details.address3',
        'account_activation_date'                                                       => 'banking_account.metadata.bank_account_open_date',
        'balance_id'                                                                    => 'banking_account.balance_id',
        'username'                                                                      => 'credentials.auth_username',
        'password'                                                                      => 'credentials.auth_password',
        'reference1'                                                                    => 'credentials.corp_id',
        'sent_to_bank_date'                                                             => 'banking_account_application.metadata.lead_sent_to_bank_date',
        'merchant_name'                                                                 => 'business.name',
        'completed_stages'                                                              => 'completed_stages',
        'lead_follow_up_date'                                                           => 'lead_follow_up_date',

        'banking_account_activation_details.id'                                         => 'banking_account_application.id',
        'banking_account_activation_details.banking_account_id'                         => 'banking_account_application.id',
        'banking_account_activation_details.created_at'                                 => 'banking_account_application.created_at',
        'banking_account_activation_details.updated_at'                                 => 'banking_account_application.updated_at',

        'banking_account_activation_details.merchant_city'                                                => 'business.registered_address_details.address_city',
        'banking_account_activation_details.merchant_region'                                              => 'business.registered_address_details.address_region',
        'banking_account_activation_details.business_category'                                            => 'business.constitution',
        'banking_account_activation_details.is_documents_walkthrough_complete'                            => 'banking_account_application.metadata.additional_details.is_documents_walkthrough_complete',
        'banking_account_activation_details.sales_team'                                                   => 'banking_account_application.sales_team',
        'banking_account_activation_details.assignee_team'                                                => 'banking_account_application.assignee_team',
        'banking_account_activation_details.business_type'                                                => 'business.industry_type',
        'banking_account_activation_details.merchant_state'                                               => 'business.registered_address_details.address_state',
        'banking_account_activation_details.merchant_poc_email'                                           => 'person.email_id',
        'banking_account_activation_details.merchant_poc_phone_number'                                    => 'person.phone_number',
        'banking_account_activation_details.merchant_documents_address'                                   => 'business.registered_address',
        'banking_account_activation_details.business_name'                                                => 'business.name',
        'banking_account_activation_details.merchant_poc_name'                                            => 'person.first_name',
        'banking_account_activation_details.merchant_poc_designation'                                     => 'person.role_in_business',
        'banking_account_activation_details.average_monthly_balance'                                      => 'banking_account_application.average_monthly_balance',
        'banking_account_activation_details.expected_monthly_gmv'                                         => 'banking_account_application.expected_monthly_gmv',
        'banking_account_activation_details.initial_cheque_value'                                         => 'banking_account_application.metadata.initial_cheque_value',
        'banking_account_activation_details.declaration_step'                                             => 'banking_account_application.metadata.declaration_step',
        'banking_account_activation_details.account_type'                                                 => 'banking_account_application.bank_account_type',
        'banking_account_activation_details.sales_poc_phone_number'                                       => 'account_managers.sales_poc.phone_number',
        'banking_account_activation_details.comment'                                                      => 'banking_account_application.metadata.comment',
        'banking_account_activation_details.customer_appointment_date'                                    => 'partner_bank_application.doc_collection_details.customer_appointment_date',
        'banking_account_activation_details.bank_poc_user_id'                                             => 'account_managers.bank_poc.rzp_admin_id',
        'banking_account_activation_details.bank_poc_name'                                                => 'account_managers.bank_poc.name',
        'banking_account_activation_details.rm_assignment_type'                                           => 'partner_bank_application.rm_details.rm_assignment_type',
        'banking_account_activation_details.rm_name'                                                      => 'partner_bank_application.rm_details.rm_name',
        'banking_account_activation_details.rm_phone_number'                                              => 'partner_bank_application.rm_details.rm_phone_number',
        'banking_account_activation_details.branch_code'                                                  => 'partner_bank_application.branch_code',
        'banking_account_activation_details.rm_employee_code'                                             => 'partner_bank_application.rm_details.rm_employee_code',
        'banking_account_activation_details.doc_collection_date'                                          => 'partner_bank_application.doc_collection_details.doc_collection_date',
        'banking_account_activation_details.account_login_date'                                           => 'banking_account_application.metadata.account_login_date',
        'banking_account_activation_details.account_open_date'                                            => 'partner_bank_application.account_opening_details.account_open_date',
        'banking_account_activation_details.account_opening_ftnr_reasons'                                 => 'partner_bank_application.account_opening_details.account_opening_ftnr_reasons',
        'banking_account_activation_details.account_opening_ftnr'                                         => 'partner_bank_application.account_opening_details.account_opening_ftnr',
        'banking_account_activation_details.api_onboarding_ftnr_reasons'                                  => 'partner_bank_application.api_onboarding_details.api_onboarding_ftnr_reasons',
        'banking_account_activation_details.api_onboarding_ftnr'                                          => 'partner_bank_application.api_onboarding_details.api_onboarding_ftnr',
        'banking_account_activation_details.ldap_id_mail_date'                                            => 'partner_bank_application.api_onboarding_details.ldap_id_mail_date',
        'banking_account_activation_details.drop_off_date'                                                => 'partner_bank_application.account_activation_details.drop_off_date',
        'banking_account_activation_details.rzp_ca_activated_date'                                        => 'partner_bank_application.account_activation_details.rzp_ca_activated_date',
        'banking_account_activation_details.upi_credential_received_date'                                 => 'partner_bank_application.account_activation_details.upi_credential_received_date',
        'banking_account_activation_details.api_ir_closed_date'                                           => 'partner_bank_application.api_onboarding_details.api_ir_closed_date',
        'banking_account_activation_details.additional_details.cin'                                       => 'banking_account_application.metadata.additional_details.cin',
        'banking_account_activation_details.additional_details.llpin'                                     => 'banking_account_application.metadata.additional_details.llpin',
        'banking_account_activation_details.additional_details.green_channel'                             => 'banking_account_application.metadata.additional_details.green_channel',
        'banking_account_activation_details.additional_details.revived_lead'                              => 'partner_bank_application.lead_details.revived_lead',
        'banking_account_activation_details.additional_details.gstin_prefilled_address'                   => 'banking_account_application.metadata.additional_details.gstin_prefilled_address',
        'banking_account_activation_details.additional_details.application_initiated_from'                => 'banking_account_application.metadata.additional_details.application_initiated_from',
        'banking_account_activation_details.additional_details.gstin'                                     => 'banking_account_application.metadata.additional_details.gstin',
        'banking_account_activation_details.additional_details.skip_dwt'                                  => 'banking_account_application.metadata.additional_details.skip_dwt',
        'banking_account_activation_details.additional_details.proof_of_entity'                           => 'banking_account_application.metadata.additional_details.proof_of_entity',
        'banking_account_activation_details.additional_details.proof_of_address'                          => 'banking_account_application.metadata.additional_details.proof_of_address',
        'banking_account_activation_details.additional_details.appointment_source'                        => 'banking_account_application.metadata.additional_details.appointment_source',
        'banking_account_activation_details.additional_details.verified_addresses'                        => 'banking_account_application.metadata.additional_details.verified_addresses',
        'banking_account_activation_details.additional_details.skip_mid_office_call'                      => 'banking_account_application.metadata.additional_details.skip_mid_office_call',
        'banking_account_activation_details.additional_details.verified_constitutions'                    => 'banking_account_application.metadata.additional_details.verified_constitutions',
        'banking_account_activation_details.additional_details.agree_to_allocated_bank_and_amb'           => 'banking_account_application.metadata.additional_details.agree_to_allocated_bank_and_amb',
        'banking_account_activation_details.additional_details.rbl_new_onboarding_flow_declarations'      => 'banking_account_application.metadata.additional_details.rbl_new_onboarding_flow_declarations',
        'banking_account_activation_details.additional_details.entity_mismatch_status'                    => 'banking_account_application.metadata.additional_details.entity_mismatch_status',
        'banking_account_activation_details.additional_details.business_details'                          => 'banking_account_application.metadata.additional_details.business_details',
        'banking_account_activation_details.additional_details.sales_pitch_completed'                     => 'banking_account_application.metadata.additional_details.sales_pitch_completed',
        'banking_account_activation_details.additional_details.entity_proof_documents'                    => 'banking_account_application.application_specific_fields.entity_proof_documents',
        'banking_account_activation_details.additional_details.sent_docket_automatically'                 => 'banking_account_application.metadata.additional_details.sent_docket_automatically',
        'banking_account_activation_details.additional_details.reasons_to_not_send_docket'                => 'banking_account_application.metadata.additional_details.reasons_to_not_send_docket',
        'banking_account_activation_details.additional_details.feet_on_street'                            => 'banking_account_application.metadata.additional_details.feet_on_street',
        'banking_account_activation_details.additional_details.dwt_response'                              => 'banking_account_application.metadata.additional_details.dwt_response',
        'banking_account_activation_details.additional_details.dwt_completed_timestamp'                   => 'banking_account_application.metadata.additional_details.dwt_completed_timestamp',
        'banking_account_activation_details.additional_details.dwt_scheduled_timestamp'                   => 'banking_account_application.metadata.additional_details.dwt_scheduled_timestamp',
        'banking_account_activation_details.additional_details.docket_requested_date'                     => 'banking_account_application.metadata.additional_details.docket_requested_date',
        'banking_account_activation_details.additional_details.courier_service_name'                      => 'banking_account_application.metadata.additional_details.courier_service_name',
        'banking_account_activation_details.additional_details.courier_tracking_id'                       => 'banking_account_application.metadata.additional_details.courier_tracking_id',
        'banking_account_activation_details.additional_details.docket_estimated_delivery_date'            => 'banking_account_application.metadata.additional_details.docket_estimated_delivery_date',
        'banking_account_activation_details.additional_details.docket_delivered_date'                     => 'banking_account_application.metadata.additional_details.docket_delivered_date',
        'banking_account_activation_details.additional_details.ops_follow_up_date'                        => 'banking_account_application.metadata.ops_follow_up_date',
        'banking_account_activation_details.additional_details.ops_revived_lead'                          => 'banking_account_application.metadata.ops_revived_lead',
        'banking_account_activation_details.additional_details.docket_not_delivered_reason'               => 'banking_account_application.metadata.additional_details.docket_not_delivered_reason',
        'banking_account_activation_details.additional_details.api_onboarding_login_date'                 => 'banking_account_application.metadata.additional_details.api_onboarding_login_date',
        'banking_account_activation_details.additional_details.account_opening_webhook_date'              => 'banking_account_application.metadata.additional_details.account_opening_webhook_date',
        'banking_account_activation_details.additional_details.mid_office_poc_name'                       => 'banking_account_application.metadata.additional_details.mid_office_poc_name',
        'banking_account_activation_details.additional_details.api_onboarded_date'                        => 'banking_account_application.metadata.additional_details.api_onboarded_date',
        'banking_account_activation_details.rbl_activation_details.customer_appointment_booking_date'     => 'partner_bank_application.doc_collection_details.customer_appointment_booking_date',
        'banking_account_activation_details.rbl_activation_details.first_calling_time'                    => 'partner_bank_application.auxiliary_details.first_calling_time',
        'banking_account_activation_details.rbl_activation_details.wa_message_sent_date'                  => 'partner_bank_application.auxiliary_details.wa_message_sent_date',
        'banking_account_activation_details.rbl_activation_details.pcarm_manager_name'                    => 'partner_bank_application.rm_details.pcarm_manager_name',
        'banking_account_activation_details.rbl_activation_details.bank_due_date'                         => 'partner_bank_application.lead_details.bank_due_date',
        'banking_account_activation_details.rbl_activation_details.bank_poc_assigned_date'                => 'partner_bank_application.lead_details.bank_poc_assigned_date',
        'banking_account_activation_details.rbl_activation_details.office_different_locations'            => 'partner_bank_application.lead_details.office_different_locations',
        'banking_account_activation_details.rbl_activation_details.lead_ir_number'                        => 'partner_bank_application.lead_details.lead_ir_number',
        'banking_account_activation_details.rbl_activation_details.api_docs_delay_reason'                 => 'partner_bank_application.doc_collection_details.api_docs_delay_reason',
        'banking_account_activation_details.rbl_activation_details.api_docs_received_with_ca_docs'        => 'partner_bank_application.doc_collection_details.api_docs_received_with_ca_docs',
        'banking_account_activation_details.rbl_activation_details.ip_cheque_value'                       => 'partner_bank_application.doc_collection_details.ip_cheque_value',
        'banking_account_activation_details.rbl_activation_details.promo_code'                            => 'partner_bank_application.auxiliary_details.promo_code',
        'banking_account_activation_details.rbl_activation_details.aof_shared_with_mo'                    => 'partner_bank_application.auxiliary_details.aof_shared_with_mo',
        'banking_account_activation_details.rbl_activation_details.revised_declaration'                   => 'partner_bank_application.auxiliary_details.revised_declaration',
        'banking_account_activation_details.rbl_activation_details.aof_not_shared_reason'                 => 'partner_bank_application.auxiliary_details.aof_not_shared_reason',
        'banking_account_activation_details.rbl_activation_details.aof_shared_discrepancy'                => 'partner_bank_application.auxiliary_details.aof_shared_discrepancy',
        'banking_account_activation_details.rbl_activation_details.ca_beyond_tat_dependency'              => 'partner_bank_application.auxiliary_details.ca_beyond_tat_dependency',
        'banking_account_activation_details.rbl_activation_details.account_opening_ir_number'             => 'partner_bank_application.account_opening_details.account_opening_ir_number',
        'banking_account_activation_details.rbl_activation_details.lead_referred_by_rbl_staff'            => 'partner_bank_application.auxiliary_details.lead_referred_by_rbl_staff',
        'banking_account_activation_details.rbl_activation_details.case_login_different_locations'        => 'partner_bank_application.lead_details.case_login_different_locations',
        'banking_account_activation_details.rbl_activation_details.ca_beyond_tat'                         => 'partner_bank_application.auxiliary_details.ca_beyond_tat',
        'banking_account_activation_details.rbl_activation_details.account_opening_tat_exception'         => 'partner_bank_application.account_opening_details.account_opening_tat_exception',
        'banking_account_activation_details.rbl_activation_details.account_opening_tat_exception_reason'  => 'partner_bank_application.account_opening_details.account_opening_tat_exception_reason',
        'banking_account_activation_details.rbl_activation_details.api_docket_related_issue'              => 'partner_bank_application.api_onboarding_details.api_docket_related_issue',
        'banking_account_activation_details.rbl_activation_details.api_onboarding_tat_exception'          => 'partner_bank_application.api_onboarding_details.api_onboarding_tat_exception',
        'banking_account_activation_details.rbl_activation_details.api_onboarding_tat_exception_reason'   => 'partner_bank_application.api_onboarding_details.api_onboarding_tat_exception_reason',
        'banking_account_activation_details.rbl_activation_details.ca_service_first_query'                => 'partner_bank_application.auxiliary_details.ca_service_first_query',
        'banking_account_activation_details.rbl_activation_details.api_ir_number'                         => 'partner_bank_application.api_onboarding_details.api_ir_number',
        'banking_account_activation_details.rbl_activation_details.sr_number'                             => 'partner_bank_application.account_opening_details.sr_number',
        'banking_account_activation_details.rbl_activation_details.upi_credential_not_done_remarks'       => 'partner_bank_application.account_activation_details.upi_credential_not_done_remarks',
        'banking_account_activation_details.rbl_activation_details.second_calling_time'                   => 'partner_bank_application.auxiliary_details.second_calling_time',
        'banking_account_activation_details.rbl_activation_details.lead_ir_status'                        => 'partner_bank_application.auxiliary_details.lead_ir_status',
        'banking_account_activation_details.rbl_activation_details.api_service_first_query'               => 'partner_bank_application.auxiliary_details.api_service_first_query',
        "banking_account_activation_details.rbl_activation_details.wa_message_response_date"              => "partner_bank_application.auxiliary_details.wa_message_response_date",
        'banking_account_activation_details.rbl_activation_details.api_beyond_tat_dependency'             => 'partner_bank_application.auxiliary_details.api_beyond_tat_dependency',
        'banking_account_activation_details.rbl_activation_details.api_beyond_tat'                        => 'partner_bank_application.auxiliary_details.api_beyond_tat',

        'banking_account_activation_details.verification_completion_date'                                 => 'tat_details.verification_completion_date',
        'banking_account_activation_details.doc_collection_completion_date'                               => 'tat_details.doc_collection_completion_date',
        'banking_account_activation_details.account_opening_completion_date'                              => 'tat_details.account_opening_completion_date',
        'banking_account_activation_details.api_onboarding_completion_date'                               => 'tat_details.api_onboarding_completion_date',
        'banking_account_activation_details.account_activation_completion_date'                           => 'tat_details.account_activation_completion_date',
        'banking_account_activation_details.verification_tat'                                             => 'tat_details.verification_tat',
        'banking_account_activation_details.doc_collection_tat'                                           => 'tat_details.doc_collection_tat',
        'banking_account_activation_details.account_opening_tat'                                          => 'tat_details.account_opening_tat',
        'banking_account_activation_details.api_onboarding_tat'                                           => 'tat_details.api_onboarding_tat',
        'banking_account_activation_details.account_activation_tat'                                       => 'tat_details.account_activation_tat',
        'banking_account_activation_details.upi_activation_tat'                                           => 'tat_details.upi_activation_tat',
        'banking_account_activation_details.customer_onboarding_tat'                                      => 'tat_details.customer_onboarding_tat',
        'banking_account_activation_details.latest_comment'                                               => 'latest_comment',
    ];

    const BAS_CREDENTIALS_TO_BANKING_ACCOUNT_DETAILS = [
        Constants::CORP_ID_CRED,
        Constants::CLIENT_ID,
        Constants::CLIENT_SECRET,
        Constants::EMAIL,
        Constants::DEV_PORTAL_PASSWORD,
    ];

    public function __construct()
    {
        $this->initializePreprocessors();
    }

    private function initializePreprocessors()
    {

        $this->apiInputPreprocessors = [

            'activation_detail.sales_poc_id' => function(string|null $value, $flattenedInput) {
                // remove admin_ prefix
                if ($value && str_starts_with($value, 'admin_')) {
                    return str_replace('admin_', '', $value);
                }

                return $value;
            },

            'activation_detail.ops_mx_poc_id' => function(string|null $value, $flattenedInput) {
                // remove admin_ prefix
                if ($value && str_starts_with($value, 'admin_')) {
                    return str_replace('admin_', '', $value);
                }

                return $value;
            },

            'activation_detail.business_category' => function(string|null $value, $flattenedInput) {
                // convert to uppercase
                return empty($value) == false ? strtoupper($value) : $value;
            },

            'activation_detail.sales_team' => function(string|null $value, $flattenedInput) {
                // convert to uppercase
                return empty($value) == false ? strtoupper($value) : $value;
            },
        ];

        $this->basResponsePreprocessors = [

            'banking_account_application.id' => function(string|null $value, $flattenedInput) {
                // add bacc_ prefix
                return 'bacc_' . ($value ?? '');
            },

            'business.constitution' => function(string|null $value, $flattenedInput) {
                // convert to lowercase
                return empty($value) == false ? strtolower($value) : $value;
            },

            'banking_account_application.sales_team' => function(string|null $value, $flattenedInput) {
                // convert to lowercase
                return empty($value) == false ? strtolower($value) : $value;
            },
        ];

    }

    /**
     * Convert timestamp from seconds to MS
     * Used to convert api input to BAS input
     */
    public function convertEpochSecondsToMillis($value)
    {
        if (empty($value))
        {
            return $value;
        }

        // convert to integer if not already
        $value = (int)$value;

        return $value * 1000;
    }

    /**
     * Convert string to integer
     */
    public function convertToInteger($value)
    {
        if (empty($value))
        {
            return $value;
        }

        return (int)$value;
    }

    /**
     * Convert timestamp from MS to seconds
     * Used to convert BAS response to API response
     */
    public function convertEpochMillisToSeconds($value)
    {
        if (empty($value))
        {
            return $value;
        }

        // convert to integer if not already
        $value = (int)$value;

        return intdiv($value, 1000);
    }

    /**
     * Convert search leads response from BAS to API structure
     *
     * @param array $basDto DTO received from BAS
     *
     * @return array API DTO
     */
    public function toApiSearchLeadsResponse(array $basDto): array
    {
        // For mapping account managers
        $apiSearchLeadsResponse = $this->fromBasResponseToApiResponse($basDto);

        // Other fields are returning a different structure, hence handling manually
        // banking account
        $apiSearchLeadsResponse[BankingAccountEntity::ID]                    = $this->getPrefixedIdOrEmpty(BankingAccountEntity::getIdPrefix(), $basDto[self::APPLICATION_ID] ?? '');
        $apiSearchLeadsResponse[BankingAccountEntity::MERCHANT_ID]           = $basDto[self::MERCHANT_ID];
        $apiSearchLeadsResponse[BankingAccountEntity::BANK_REFERENCE_NUMBER] = $basDto[self::APPLICATION_TRACKING_ID];
        $apiSearchLeadsResponse[BankingAccountEntity::STATUS]                = $basDto[self::APPLICATION_STATUS];
        $apiSearchLeadsResponse[BankingAccountEntity::SUB_STATUS]            = $basDto[self::SUB_STATUS];
        $apiSearchLeadsResponse[BankingAccountEntity::CREATED_AT]            = $this->convertEpochMillisToSeconds($basDto[self::CREATED_AT]);

        // activation details
        $apiSearchLeadsResponse[BankingAccountEntity::BANKING_ACCOUNT_ACTIVATION_DETAILS] = [
            ActivationDetailsEntity::ASSIGNEE_TEAM => $basDto[self::ASSIGNEE_TEAM],
        ];

        return $apiSearchLeadsResponse;
    }

    public function transformApiInputToBasInput(array $apiInput): array
    {
        $basDto = [];

        // 2 levels because any nested fields like rbl_new_onboarding_flow_declarations
        // under activation_detail.additional_details should not be flattened
        $flattenedInput = array_assoc_flatten_nth_level($apiInput, "%s.%s", null, false, 2);

        // preprocess input
        foreach($this->apiInputPreprocessors as $path => $transformer)
        {
            if (array_key_exists($path, $flattenedInput))
            {
                $value = $flattenedInput[$path];

                $flattenedInput[$path] = $transformer($value, $flattenedInput);
            }
        }

        foreach(self::API_TO_BAS_INPUT_MAPPING as $path => $targetPath)
        {
            if (array_key_exists($path, $flattenedInput))
            {
                $value = $flattenedInput[$path];

                if (in_array($path, self::STRING_FIELD_TO_TRANSFORM_INTO_INT))
                {
                    $value = $this->convertToInteger($value);
                }

                if (in_array($targetPath, self::DATE_FIELDS_TO_TRANSFORM_INTO_MS))
                {
                    $value = $this->convertEpochSecondsToMillis($value);
                }

                assign_array_by_flattened_path($basDto, $targetPath, $value);
            }
        }

        return $basDto;
    }

    /**
     * Convert api input to bas input for composite update API
     *
     * @param array $apiInput DTO received from FE
     *
     * @return array BAS DTO
     */
    public function fromApiInputToBasInput(array $apiInput): array
    {
        $basDto = $this->transformApiInputToBasInput($apiInput);

        $basDto = $this->populateAdminAccountManagerInfo($basDto, Constants::SALES_POC);
        $basDto = $this->populateAdminAccountManagerInfo($basDto, Constants::OPS_MX_POC);

        return $basDto;
    }

    // populateAdminAccountManagerInfo populates sales_poc details in account managers
    // We need to add sales poc details - name, email etc to account managers since these details may not be present in BAS
    private function populateAdminAccountManagerInfo(array $basDto, string $type)
    {
        if (isset($basDto[Constants::ACCOUNT_MANAGERS]) && isset($basDto[Constants::ACCOUNT_MANAGERS][$type]))
        {
            // We need to add sales poc details - name, email etc to account managers

            $adminId = $basDto[Constants::ACCOUNT_MANAGERS][$type][Constants::RZP_ADMIN_ID];

            $core = new BankingAccount\Core();

            $admin = $core->getAdminDetails($adminId);

            $basDto[Constants::ACCOUNT_MANAGERS][$type][Constants::NAME] = $admin->getName();
            $basDto[Constants::ACCOUNT_MANAGERS][$type][Constants::EMAIL] = $admin->getEmail();
        }

        return $basDto;
    }

    /**
     * Get POC details from BAS Account Manangers
     */
    private function fromBasAccountManagersToSpecifiedPoc($basResponse, $pocType) {

        // Not checking if present or empty intentionally,
        // BAS guarantees to return this - with empty values if not present
        $poc = $basResponse[Constants::ACCOUNT_MANAGERS][$pocType];

        $items = [];

        if (empty($poc[Constants::RZP_ADMIN_ID]) == false)
        {
            $items[] = [
                'id' => 'admin_' . $poc[Constants::RZP_ADMIN_ID],
                'email' => $poc[Constants::EMAIL],
                'name' => $poc[Constants::NAME],
                'admin' => true,
            ];
        }

        return $this->arrayAsPublicCollection($items);
    }

    /**
     * Get Banking Account Details
     */
    private function getBankingAccountDetailsFromBasResponse($basResponse) {

        $bankingAccountDetails = [];
        $bankingAccountId      = $basResponse[Constants::BANKING_ACCOUNT_APPLICATION]['id'];
        $merchantId            = $basResponse[Constants::BUSINESS][Constants::MERCHANT_ID];

        if (isset($basResponse[Constants::CREDENTIALS]))
        {
            // Not checking if present or empty intentionally,
            // BAS guarantees to return this - with empty values if not present
            $credentials = $basResponse[Constants::CREDENTIALS];

            foreach ($credentials as $key => $value) {
                if (in_array($key, self::BAS_CREDENTIALS_TO_BANKING_ACCOUNT_DETAILS) && !empty($value)) {
                    $bankingAccountDetails[] = [
                        BankingAccount\Detail\Entity::BANKING_ACCOUNT_ID => $bankingAccountId,
                        BankingAccount\Detail\Entity::MERCHANT_ID        => $merchantId,
                        BankingAccount\Detail\Entity::GATEWAY_KEY        => $key,
                        BankingAccount\Detail\Entity::GATEWAY_VALUE      => $value,
                        BankingAccount\Detail\Entity::ID                 => '',
                        BankingAccount\Detail\Entity::ENTITY             => 'banking_account_detail',
                        BankingAccount\Detail\Entity::ADMIN              => true
                    ];
                }
            }
        }

        return $this->arrayAsPublicCollection($bankingAccountDetails);
    }

    /**
     * Convert api input to bas input for composite update API
     *
     * @param array $apiInput DTO received from FE
     *
     * @return array BAS DTO
     */
    public function fromBasResponseToApiResponse(array $basResponseDto): array
    {
        $apiResponseDto = [];

        // 3 levels because any nested fields like rbl_new_onboarding_flow_declarations
        // under banking_account_application.metadata.additional_details should not be flattened
        $flattenedInput = array_assoc_flatten_nth_level($basResponseDto, "%s.%s", null, false, 3);

        // preprocess response
        foreach($this->basResponsePreprocessors as $path => $transformer)
        {
            if (array_key_exists($path, $flattenedInput))
            {
                $value = $flattenedInput[$path];

                $flattenedInput[$path] = $transformer($value, $flattenedInput);
            }
        }

        foreach(self::BAS_TO_API_RESPONSE_MAPPING as $apiPath => $basPath)
        {
            if (array_key_exists($basPath, $flattenedInput))
            {
                $value = $flattenedInput[$basPath];

                if (in_array($basPath, self::DATE_FIELDS_TO_TRANSFORM_INTO_SECONDS))
                {
                    $value = $this->convertEpochMillisToSeconds($value);
                }

                assign_array_by_flattened_path($apiResponseDto, $apiPath, $value);
            }
            else
            {
                assign_array_by_flattened_path($apiResponseDto, $apiPath, null);
            }
        }

        $apiResponseDto[BankingAccountEntity::CHANNEL] = 'rbl';
        $apiResponseDto[BankingAccountEntity::ACCOUNT_TYPE] = 'current'; // this will always be current account
        $apiResponseDto[BankingAccountEntity::USING_NEW_STATES] = true; // this is always evaluated as true

        // Not required
        $apiResponseDto[BankingAccountEntity::GATEWAY_BALANCE] = '';
        $apiResponseDto[BankingAccountEntity::BALANCE_LAST_FETCHED_AT] = '';
        $apiResponseDto[BankingAccountEntity::LAST_STATEMENT_ATTEMPT_AT] = '';

        $apiResponseDto[BankingAccountEntity::FASTER_DOC_COLLECTION_ENABLED] = true;

        // Handle - banking_account_details, after credentials change
        $bankingAccountDetails = $this->getBankingAccountDetailsFromBasResponse($basResponseDto);

        $apiResponseDto[BankingAccountEntity::BANKING_ACCOUNT_DETAILS] = $bankingAccountDetails;

        if (isset($basResponseDto[Constants::ACCOUNT_MANAGERS]))
        {
            // reviewers, spocs, ops_mx_pocs
            $apiResponseDto[BankingAccountEntity::SPOCS] = $this->fromBasAccountManagersToSpecifiedPoc($basResponseDto, Constants::SALES_POC);
            $apiResponseDto[BankingAccountEntity::REVIEWERS] = $this->fromBasAccountManagersToSpecifiedPoc($basResponseDto, Constants::OPS_POC);
            $apiResponseDto[BankingAccountEntity::OPS_MX_POCS] = $this->fromBasAccountManagersToSpecifiedPoc($basResponseDto, Constants::OPS_MX_POC);

            // bank_poc details are handled through adapter
        }


        // Additional Details is passed as string for non-admin requests
        if (app('basicauth')->isAdminAuth() === false and
            isset($apiResponseDto[BankingAccountEntity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetailEntity::ADDITIONAL_DETAILS]) === true)
        {
            $apiResponseDto[BankingAccountEntity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetailEntity::ADDITIONAL_DETAILS] =
                json_encode($apiResponseDto[BankingAccountEntity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetailEntity::ADDITIONAL_DETAILS]);
        }

        // TODO: need to check if balance is required
        /**
            "balance": {
                "id": "LYDDt86fDZUrME",
                "currency": "INR",
                "balance": 358800,
                "locked_balance": 0,
                "last_fetched_at": 1682066193
            },
         */

        return $apiResponseDto;
    }

    public function fromBasResponseToApiResponseForInternalFetch(array $basResponse)
    {
        $apiReponse = $this->fromBasResponseToApiResponse($basResponse);

        $apiReponse = $this->removeFields($apiReponse, [
            self::BANKING_ACCOUNT_DETAILS,
            self::REVIEWERS,
            self::SPOCS,
            self::OPS_MX_POCS,
            self::BANKING_ACCOUNT_DETAILS,
        ]);

        return $apiReponse;
    }

    public function removeFields(array $input, array $fieldsToRemove): array
    {
        foreach ($fieldsToRemove as $field) {
            if (isset($input[$field]))
            {
                unset($input[$field]);
            }
        }

        return $input;
    }

    /**
     * Convert search leads response from BAS to API structure for a list of objects
     *
     * @param array $basDtoList list of DTOs received from BAS
     *
     * @return array list of API DTOs
     */
    public function toApiSearchLeadsResponseBulk(array $basDtoList): array
    {
        if (empty($basDtoList))
        {
            return [];
        }

        return array_map(function($basDto) {
            return $this->toApiSearchLeadsResponse($basDto);
        }, $basDtoList);
    }

    public function toApiStatusChangeLogsResponse(array $basStatusLog): array
    {
        $apiStatusLog = [
            'banking_account_id' => $this->getPrefixedIdOrEmpty(BankingAccountEntity::getIdPrefix(), $basStatusLog['banking_account_application_id'] ?? ''),
            'status'             => $basStatusLog['application_status'],
            'sub_status'         => $basStatusLog['sub_status'],
            'bank_status'        => $basStatusLog['bank_status'],
            'assignee_team'      => $basStatusLog['assignee_team'],
            'admin_id'           => $this->getPrefixedIdOrEmpty(AdminEntity::getIdPrefix(), $basStatusLog['created_by'] ?? ''),
            'created_at'         => $this->convertEpochMillisToSeconds($basStatusLog['created_at']),
        ];

        if (!empty($basStatusLog['created_by_team']))
        {
            if ($basStatusLog['created_by_team'] === 'BANK_RBL')
            {
                $apiStatusLog['user_id'] = $basStatusLog['created_by'];
                $apiStatusLog['user']    = [
                    'id'   => $basStatusLog['created_by'],
                    'name' => $basStatusLog['created_by_name'],
                ];
            }
            else
            {
                $apiStatusLog['admin_id'] = $this->getPrefixedIdOrEmpty(AdminEntity::getIdPrefix(), $basStatusLog['created_by']);
                $apiStatusLog['admin']    = [
                    'id'   => $basStatusLog['created_by'],
                    'name' => $basStatusLog['created_by_name'],
                ];
            }
        }

        return $apiStatusLog;
    }

    public function toApiStatusChangeLogsResponseBulk(array $basStatusLogs): array
    {
        if (empty($basStatusLogs))
        {
            return $this->arrayAsPublicCollection([]);
        }

        $statusLogs = array_map(function($basStatusLog) {
            return $this->toApiStatusChangeLogsResponse($basStatusLog);
        }, $basStatusLogs);

        return $this->arrayAsPublicCollection($statusLogs);
    }

    public function toApiCommentResponse(array $basCommentResponse) : array
    {
        $apiResponse = [
            'id'                 => $basCommentResponse['id'],
            'banking_account_id' => $this->getPrefixedIdOrEmpty(BankingAccountEntity::getIdPrefix(),$basCommentResponse['banking_account_application_id'] ?? ''),
            'comment'            => $basCommentResponse['comment'],
            'source_team'        => $basCommentResponse['on_behalf_of'] ?? '',
            'source_team_type'   => ($basCommentResponse['on_behalf_of'] ?? '') === 'bank' ? 'external' : 'internal',
            'type'               => $basCommentResponse['type'],
            'notes'              => $basCommentResponse['notes'] ?? [],
            'created_at'         => $this->convertEpochMillisToSeconds($basCommentResponse['created_at']),
            'added_at'           => $this->convertEpochMillisToSeconds($basCommentResponse['added_at']),
        ];

        if ($basCommentResponse['commented_by_team'] === 'BANK_RBL')
        {
            $apiResponse['user_id'] = $basCommentResponse['commented_by'];
            $apiResponse['user']    = [
                'id'   => $basCommentResponse['commented_by'],
                'name' => $basCommentResponse['commented_by_name'],
            ];
        }
        else
        {
            $apiResponse['admin_id'] = $this->getPrefixedIdOrEmpty(AdminEntity::getIdPrefix(), $basCommentResponse['commented_by']);
            $apiResponse['admin']    = [
                'id'   => $basCommentResponse['commented_by'],
                'name' => $basCommentResponse['commented_by_name'],
            ];
        }

        return $apiResponse;
    }

    public function toApiCommentResponseBulk(array $basCommentsResponse) : array
    {
        if (empty($basCommentsResponse))
        {
            return $this->arrayAsPublicCollection([]);
        }

        $comments = array_map(function($basDto) {
            return $this->toApiCommentResponse($basDto);
        }, $basCommentsResponse);

        return $this->arrayAsPublicCollection($comments);
    }

    public function toBasCommentCreateRequest(array $apiCommentRequest) : array
    {
        return [
            'comment'      => $apiCommentRequest['comment'],
            'on_behalf_of' => $apiCommentRequest['source_team'],
            'type'         => $apiCommentRequest['type'],
            'added_at'     => $this->convertEpochSecondsToMillis($apiCommentRequest['added_at']),
        ];
    }
    public function toBasPartnerLmsCommentCreateRequest(array $apiCommentRequest) : array
    {
        $basCommentRequest = $this->toBasCommentCreateRequest($apiCommentRequest);
        $basCommentRequest['on_behalf_of'] = 'bank';
        return $basCommentRequest;
    }

    public function toBasCommentUpdateRequest(array $apiCommentRequest): array
    {
        return [
            'type' => $apiCommentRequest['type'],
        ];
    }

    public function toBasBulkAssignAccountManagerRequest(array $apiBulkAssignRequest) : array
    {
        $bankingAccountCore = new BankingAccount\Core();

        $admin = $bankingAccountCore->getAdminDetails($apiBulkAssignRequest['reviewer_id']);

        return [
            'banking_account_ids' => $apiBulkAssignRequest['banking_account_ids'],
            'relationship_type'   => 'OPS_POC', // Currently this is only being used for assigning OPS POC in API, but BAS endpoint supports other account managers as well
            'team'                => 'RAZORPAY_OPS',
            'rzp_admin_id'        => $admin->getId(),
            'name'                => $admin->getName(),
            'email'               => $admin->getEmail(),
        ];
    }

    public function toApiPartnerLmsLeadsResponse(array $response) : array
    {
        if (empty($response)) {
            return [];
        }

        return array_map(function($basDto) {
            return $this->fromBasResponseToApiResponse($basDto);
        }, $response);
    }

    public function toBasAssignBankPocRequest(string $bankPocUserId) : array
    {
        $bankingAccountCore = new BankingAccount\Core();

        $bankPoc = $bankingAccountCore->getUserDetails($bankPocUserId);

        return [
            'bank_poc_user_id'      => $bankPocUserId,
            'bank_poc_name'         => $bankPoc->getName(),
            'bank_poc_phone_number' => $bankPoc->getContactMobile(),
            'bank_poc_email'        => $bankPoc->getEmail(),
        ];
    }

    public function toApiPartnerLmsActivityResponse(array $basActivityResponse, array $apiRequest): array
    {
        $apiActivityResponse = [];

        $basComments   = $basActivityResponse['comments'] ?? [];
        $basStatusLogs = $basActivityResponse['status_logs'] ?? [];

        foreach ($basComments as $basComment)
        {
            $apiComment                  = $this->toApiCommentResponse($basComment);
            $apiComment['activity_type'] = 'comment';
            $apiActivityResponse[]       = $apiComment;
        }

        foreach ($basStatusLogs as $basStatusLog)
        {
            $apiStatusLog                  = $this->toApiStatusChangeLogsResponse($basStatusLog);
            $apiStatusLog['activity_type'] = 'state_change';
            $apiActivityResponse[]         = $apiStatusLog;
        }

        $bankLmsService = new BankingAccount\BankLms\Service();

        $bankLmsService->sortActivity($apiActivityResponse, $apiRequest['sort'] ?? 'asc');

        return $this->arrayAsPublicCollection($apiActivityResponse);
    }

    public function getPrefixedIdOrEmpty(string $prefix, ?string $id) : string {
        if (empty($id)) {
            return '';
        }
        return $prefix . $id;
    }

    public function arrayAsPublicCollection(array $input): array
    {
        return [
            'entity' => 'collection',
            'count'  => count($input),
            'items'  => $input,
        ];
    }


}
