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

export type PolicyPageFormData = Record<WebsitePolicyPages, FormFieldType>;

export interface InitialPolicyPagesFormStateData {
  verifiedPages: Record<WebsitePolicyPages, FormFieldType>;
  missingPages: Record<WebsitePolicyPages, FormFieldType>;
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

  MAIN_PAGE_SUBMIT_IN_PROGRESS = 'MAIN_PAGE_SUBMIT_IN_PROGRESS',
  POLICY_PAGES_SUBMIT_IN_PROGRESS = 'POLICY_PAGES_SUBMIT_IN_PROGRESS',
  MAIN_PAGE_SUBMIT_SUCCESS = 'MAIN_PAGE_SUBMIT_SUCCESS',
  MAIN_PAGE_ERROR = 'MAIN_PAGE_ERROR',

  MANUAL_WF_RAISED = 'MANUAL_WF_RAISED',
  WEBSITE_UPDATE_SUCCESS = 'WEBSITE_UPDATE_SUCCESS',
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
}

export enum WebsiteVerificationStatus {
  INITIATED = 'INITIATED',
  PASSED = 'PASSED',
  FAILED = 'FAILED',
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
  };
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
