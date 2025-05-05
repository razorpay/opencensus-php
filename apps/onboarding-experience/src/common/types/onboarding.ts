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

type SelfServeWorkflowStatus = {
  code: number;
  success: boolean;
  message: string;
  selfServeWorkflow?: {
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
};

/**
 * Type representing the different verification stages for a merchant's website
 * Includes checks for MCC, negative keywords, and BVS
 */
type WebsiteVerificationStage = {
  workflowExist?: boolean;
  mccCheckStatus?: string;
  negativeKeywordCheckStatus?: string;
  bvsCheckStatus?: string;
};

/**
 * Type representing the verification status of different required pages on a merchant's website
 * Includes terms, privacy, refund, shipping, and contact pages
 */
type WebsiteVerificationPageStatus = {
  terms?: { url: string; verified: string };
  privacy?: { url: string; verified: string };
  refund?: { url: string; verified: string };
  shipping?: { url: string; verified: string };
  contact?: { url: string; verified: string };
};

/**
 * Type representing the overall status of a merchant's website verification process
 * Includes verification stages and page statuses
 */
type WebsiteVerificationUpdateStatus = {
  code: number;
  success: boolean;
  message: string;
  verificationStatus?: {
    currentStatus?: string;
    currentStatusUpdatedAt?: string;
    mainPageUrl?: string;
    websiteVerificationStage?: WebsiteVerificationStage;
    websiteVerificationPageStatus?: WebsiteVerificationPageStatus;
  };
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
enum MERCHANT_CUSTOMER_ACTIONS {
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
  selfServeWorkflowStatus?: SelfServeWorkflowStatus;
  websiteVerificationUpdateStatus?: WebsiteVerificationUpdateStatus;
};

export type MerchantOnboardingDataResponseType = {
  merchantOnboardingData?: MerchantOnboardingDataType;
};

export type AddMerchantSelectedPluginResponse = {
  addMerchantSelectedPlugin: {
    plugins: SelectedPlugin[];
  };
};
