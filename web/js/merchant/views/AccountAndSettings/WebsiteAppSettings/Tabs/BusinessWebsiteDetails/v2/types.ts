import { TextInput } from '@razorpay/blade/components';

import { Environments, User } from 'common/typings';

export enum Platform {
  WEBSITE = 'website',
  APP = 'app',
}

export enum ValidationState {
  NONE = 'none',
  ERROR = 'error',
}

export enum RequireCredsValues {
  YES = 'yes',
  NO = 'no',
}

export enum PolicyPagesSelection {
  YES = 'yes',
  NO = 'no',
  NA = 'not-applicable',
}

export type FormFieldType = {
  value: string;
  valid: ValidationState;
  verified?: WebsiteVerificationStatus;
};

export enum MainFormFields {
  URL = 'url',
  PLATFORM = 'platform',
  REQUIRE_CREDS = 'requireCreds',
  CREDS_USERNAME = 'credsUsername',
  CREDS_PASSWORD = 'credsPassword',
}
export interface MainPageFormData {
  [MainFormFields.PLATFORM]: FormFieldType;
  [MainFormFields.URL]: FormFieldType;
  [MainFormFields.REQUIRE_CREDS]: FormFieldType;
  [MainFormFields.CREDS_USERNAME]: FormFieldType;
  [MainFormFields.CREDS_PASSWORD]: FormFieldType;
}

export enum WebsitePolicyPages {
  TERMS = 'terms',
  PRIVACY = 'privacy',
  SHIPPING = 'shipping',
  CONTACT = 'contact',
  REFUND = 'refund',
}

export type PartialPolicyPages = Array<Partial<WebsitePolicyPages>>;

export interface MissingPagesFormFieldType extends FormFieldType {
  radioValue: PolicyPagesSelection | undefined;
}

export type PolicyPageFormData = Record<WebsitePolicyPages, MissingPagesFormFieldType>;

export type PolicyPageCreationFormFieldType = Record<WebsitePolicyPagesDetailsKeys, FormFieldType>;

export interface InitialPolicyPagesFormStateData {
  verifiedPages: Record<WebsitePolicyPages, FormFieldType>;
  missingPages: Record<WebsitePolicyPages, MissingPagesFormFieldType>;
  verifiedPagesKeys: WebsitePolicyPages[];
  missingPagesKeys: WebsitePolicyPages[];
}

export enum WebsiteUpdateActionOn {
  MAIN_WEBSITE = 'MAIN_WEBSITE',
  ADDITIONAL_WEBSITE = 'ADDITIONAL_WEBSITE',
}

export type BladeFormInputOnEvent = Parameters<
  NonNullable<React.ComponentPropsWithoutRef<typeof TextInput>['onChange']>
>[0];

export enum WebsiteSubmitModalSteps {
  ADD_MAIN_PAGE = 'ADD_MAIN_PAGE',
  ADD_MISSING_POLICY_PAGES = 'ADD_MISSING_POLICY_PAGES',

  // Loading, success, error state - Main website
  MAIN_PAGE_SUBMIT_IN_PROGRESS = 'MAIN_PAGE_SUBMIT_IN_PROGRESS',
  MAIN_PAGE_SUBMIT_SUCCESS = 'MAIN_PAGE_SUBMIT_SUCCESS',
  MAIN_PAGE_LIVENESS_ERROR = 'MAIN_PAGE_LIVENESS_ERROR',

  // Loading, success, error state - Missing Pages
  POLICY_PAGES_SUBMIT_IN_PROGRESS = 'POLICY_PAGES_SUBMIT_IN_PROGRESS',
  WEBSITE_UPDATE_SUCCESS = 'WEBSITE_UPDATE_SUCCESS',
  WEBSITE_UPDATE_WORKFLOW_RAISED = 'WEBSITE_UPDATE_WORKFLOW_RAISED',

  // Multiple error steps
  POLICY_PAGES_CREATION = 'POLICY_PAGES_CREATION',
  POLICY_PAGES_PREVIEW = 'POLICY_PAGES_PREVIEW',
  POLICY_PAGES_COMPLETE = 'POLICY_PAGES_COMPLETE',
}

export enum SuggestionSteps {
  POLICY_PAGES = 'POLICY_PAGES',
  CREDS_NOT_REQUIRED = 'CREDS_NOT_REQUIRED',
  CREDS_REQUIRED = 'CREDS_REQUIRED',
  MISSING_POLICY_PAGES_terms = 'MISSING_POLICY_PAGES_terms',
  MISSING_POLICY_PAGES_privacy = 'MISSING_POLICY_PAGES_privacy',
  MISSING_POLICY_PAGES_shipping = 'MISSING_POLICY_PAGES_shipping',
  MISSING_POLICY_PAGES_contact = 'MISSING_POLICY_PAGES_contact',
  MISSING_POLICY_PAGES_refund = 'MISSING_POLICY_PAGES_refund',
}

export enum WebsiteUpdateAutomationStatus {
  IN_PROGRESS = 'in_progress',
  COMPLETED = 'completed',

  WORKFLOW_IN_PROGRESS = 'workflow_in_progress',
  WORKFLOW_REJECTED = 'workflow_rejected',
  WORKFLOW_COMPLETED = 'workflow_completed',
  WORKFLOW_EXECUTED = 'workflow_executed',

  WORKFLOW_CREATION_FAILED = 'workflow_creation_failed',
  WEBSITE_UPDATE_FAILED = 'website_update_failed',
  WEBSITE_LIVENESS_FAILED = 'website_update_failed_due_to_liveness_check_failure',
}

export enum WebsiteVerificationStatus {
  INITIATED = 'INITIATED',
  PASSED = 'PASSED',
  FAILED = 'FAILED',
  NOT_APPLICABLE = 'NOT_APPLICABLE',
}

export interface WebsitePolicyPageVerificationStatus {
  url: string;
  verified: WebsiteVerificationStatus;
}

export interface WebsiteVerificationStage {
  worklfow_exist?: boolean;
  mcc_check_status?: WebsiteVerificationStatus;
  dedupe_check_status?: WebsiteVerificationStatus;
  negative_keyword_check_status?: WebsiteVerificationStatus;
  bvs_check_status?: WebsiteVerificationStatus;
  bvs_single_page_check_status?: WebsiteVerificationStatus;
}

export enum WebsitePolicyPagesDetailsKeys {
  SUPPORT_CONTACT_NUMBER = 'support_contact_number',
  SUPPORT_EMAIL = 'support_email',
  SHIPPING_PERIOD = 'shipping_period',
  REFUND_REQUEST_PERIOD = 'refund_request_period',
  REFUND_PROCESS_PERIOD = 'refund_process_period',
}

export interface WebsiteVerificationPageStatus {
  [WebsitePolicyPages.TERMS]?: WebsitePolicyPageVerificationStatus;
  [WebsitePolicyPages.PRIVACY]?: WebsitePolicyPageVerificationStatus;
  [WebsitePolicyPages.REFUND]?: WebsitePolicyPageVerificationStatus;
  [WebsitePolicyPages.SHIPPING]?: WebsitePolicyPageVerificationStatus;
  [WebsitePolicyPages.CONTACT]?: WebsitePolicyPageVerificationStatus;
}

export interface WebsiteUpdateApiData {
  current_status?: WebsiteUpdateAutomationStatus;
  current_status_updated_at?: string;
  main_page_url?: string;
  website_verification_stage?: WebsiteVerificationStage;
  website_verification_page_status?: WebsiteVerificationPageStatus;
}

export interface PolicyPagesDetails {
  additional_data: {
    [WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER]: string;
    [WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL]: string;
  };
  [WebsitePolicyPagesDetailsKeys.SHIPPING_PERIOD]: string;
  [WebsitePolicyPagesDetailsKeys.REFUND_REQUEST_PERIOD]: string;
  [WebsitePolicyPagesDetailsKeys.REFUND_PROCESS_PERIOD]: string;
}

export interface BusinessWebsiteWorkflow {
  workflow_status?: any;
  needs_clarification?: any;
  request_under_validation?: any;
  tags?: any;
  rejection_reason_message?: any;
  ocr_automated_check_enable?: any;
}

export interface WebsiteUpdateApiPayload {
  mode: Environments;
  main_page?: {
    url: string;
    main_page_credential?: {
      username: string;
      password: string;
    };
  };
  policy_pages?: {
    [WebsitePolicyPages.TERMS]?: {
      url: string;
    };
    [WebsitePolicyPages.PRIVACY]?: {
      url: string;
    };
    [WebsitePolicyPages.REFUND]?: {
      url: string;
    };
    [WebsitePolicyPages.SHIPPING]?: {
      url: string;
    };
    [WebsitePolicyPages.CONTACT]?: {
      url: string;
    };
    is_shipping_page_required?: boolean;
  };
}

export type MerchantWebsiteDetails = Record<WebsitePolicyPages, { section_status: 3 }>;

interface WebsitePolicyPagesDataPayload extends PolicyPagesDetails {
  merchant_website_details: MerchantWebsiteDetails;
}

export interface WebsitePolicyPagesCreationPayload {
  mode: Environments;
  data: WebsitePolicyPagesDataPayload;
}

export interface PolicyPagesConsentPayload {
  mode: Environments;
  consents: Array<{
    type: 'Policy Creation Terms';
    is_provided: boolean;
  }>;
  event: 'WebsitePolicyWizard';
}

export interface PolicyPagesConsentResponse {
  success: boolean;
}

export interface PolicyPagesPreviewResponse {
  data: Array<{
    section: WebsitePolicyPages;
    html_content: string;
  }>;
}
export interface PolicyPagesPublishPayload {
  mode: Environments;
  sections: Array<Partial<WebsitePolicyPages>>;
}

export enum BusinessWebsiteCardBadgeStatus {
  ACTIVE = 'Active',
  UNDER_REVIEW = 'Under Review',
  ACTION_REQUIRED = 'Action Required',
}

export interface AddWebsiteClickArgs {
  actionOn: WebsiteUpdateActionOn;
  isEdit?: boolean;
}

export type BusinessWebsiteCardData = {
  url: string;
  status: string;
  platform: string;
  isPrimary: boolean;
};

export type GetCtaConditionArgs = {
  isWebsiteDetailsFetching: boolean;
  isWebsiteDetailsFetchError: boolean;
  websiteUpdateData: WebsiteUpdateApiData | undefined;
  businessWebsiteWorkflow: Record<string, any>;
  additionalWebsiteWorkflow: Record<string, any>;
  user: User;
};

export type GetCtaConditionData = {
  isMainWebsiteEditActionAllowed: boolean;
  isAdditionalWebsiteActionAllowed: boolean;
  isAddActionAllowed: boolean;
  isAddFirstWebsiteAllowed: boolean;
  ctaText: string;
  ctaDisabledReason: string;
};
