/**
 * Enum for specifying which sections of merchant onboarding data to request
 */
export enum MerchantOnboardingDataRequestEnum {
  FEATURE_FLAGS = 'FEATURE_FLAGS',
  SELECTED_PLUGINS = 'SELECTED_PLUGINS',
  SUPPORTED_PLUGINS = 'SUPPORTED_PLUGINS',
  SELF_SERVE_WORKFLOW_STATUS = 'SELF_SERVE_WORKFLOW_STATUS',
  WEBSITE_VERIFICATION_STATUS = 'WEBSITE_VERIFICATION_STATUS',
}

export type SelfServeWorkflowStatus = {
  isWorkflowExits?: boolean;
  workflowStatus?: string;
  permission?: string;
  isRequestUnderBvsValidation?: boolean;
  rejectionReason?: string;
  needsClarificationMessage?: string;
  customerActions?: MERCHANT_CUSTOMER_ACTIONS[];
  bankAccountId: string | null;
  createdAt?: string;
  rejectedAt?: string;
};

export enum MerchantWebsiteCheckStatusEnum {
  INITIATED = 'INITIATED',
  PASSED = 'PASSED',
  FAILED = 'FAILED',
  NOT_APPLICABLE = 'NOT_APPLICABLE',
}

/**
 * Type representing the different verification stages for a merchant's website
 * Includes checks for MCC, negative keywords, and BVS
 */
type WebsiteVerificationStage = {
  workflowExist?: boolean;
  mccCheckStatus?: MerchantWebsiteCheckStatusEnum;
  negativeKeywordCheckStatus?: MerchantWebsiteCheckStatusEnum;
  bvsCheckStatus?: MerchantWebsiteCheckStatusEnum;
};

export type WebsitePageKeys = {
  url: string;
  verified: MerchantWebsiteCheckStatusEnum;
};

/**
 * Type representing the verification status of different required pages on a merchant's website
 * Includes terms, privacy, refund, shipping, and contact pages
 */
type WebsiteVerificationPageStatus = {
  terms?: WebsitePageKeys;
  privacy?: WebsitePageKeys;
  refund?: WebsitePageKeys;
  shipping?: WebsitePageKeys;
  contact?: WebsitePageKeys;
};

export enum WebsiteVerificationAutomationStatus {
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

/**
 * Type representing the overall status of a merchant's website verification process
 * Includes verification stages and page statuses
 */
export type WebsiteVerificationUpdateStatus = {
  currentStatus?: WebsiteVerificationAutomationStatus;
  currentStatusUpdatedAt?: string;
  mainPageUrl?: string;
  websiteVerificationStage?: WebsiteVerificationStage;
  websiteVerificationPageStatus?: WebsiteVerificationPageStatus;
};

export type SupportedPlugin = {
  name: string;
  icon: string;
  integrationGuide: string;
  integrationUrl: string;
};

export type SelectedPlugin = { website: string; selectedPlugin: string | null };

/**
 * Enum for possible merchant customer action states in workflows
 */
export enum MERCHANT_CUSTOMER_ACTIONS {
  AWAITING_CUSTOMER_RESPONSE = 'AWAITING_CUSTOMER_RESPONSE',
  CUSTOMER_RESPONDED = 'CUSTOMER_RESPONDED',
}

/**
 * Feature flags for controlling which features are enabled in the FTUX flow
 */
export enum MERCHANT_FEATURE_FLAGS {
  /** Flag to control display of PG V3 onboarding / Shopify intent merchant */
  SHOW_PG_V3 = 'show_pg_v3',
  /** Flag indicating whether PG V3 / Shopify onboarding has been completed */
  PG_V3_ONBOARDING_COMPLETE = 'pg_v3_onboarding_complete',
}

/**
 * Type representing all merchant onboarding data for the FTUX dashboard
 * Includes feature flags, plugin information, and workflow statuses
 */
export type MerchantOnboardingDataType = {
  featureFlags?: {
    [key in MERCHANT_FEATURE_FLAGS]: boolean;
  };
  selectedPlugins?: SelectedPlugin[];
  supportedPlugins?: SupportedPlugin[];
  selfServeWorkflowStatus?: {
    code: number;
    success: boolean;
    message: string;
    selfServeWorkflow?: SelfServeWorkflowStatus;
  };
  websiteVerificationUpdateStatus?: {
    code: number;
    success: boolean;
    message: string;
    verificationStatus?: WebsiteVerificationUpdateStatus;
  };
};

export type MerchantOnboardingDataResponseType = {
  merchantOnboardingData?: MerchantOnboardingDataType;
};

export type AddMerchantSelectedPluginResponse = {
  addMerchantSelectedPlugin: {
    plugins: SelectedPlugin[];
  };
};

/**
 * User-facing verification status values used for displaying status to merchants
 * Consolidates the more granular internal statuses into actionable states
 */
export enum WebsiteVerificationStatusEnum {
  Success = 'success',
  BvsNeedsClarification = 'bvs_needs_clarification',
  BvsInProgress = 'bvs_in_progress',
  WorkflowInReview = 'workflow_in_review',
  WorkflowNeedsClarification = 'workflow_needs_clarification',
  Rejected = 'rejected',
  WebsiteUpdateFailed = 'website_update_failed',
  WebsiteLivenessFailed = 'website_liveness_failed',
  NeedsUpdate = 'needs_update',
  NoWebsite = 'no_website_added',
}
