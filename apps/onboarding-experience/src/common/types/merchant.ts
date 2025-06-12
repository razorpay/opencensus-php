/**
 * Enum defining the types of workflows available in the onboarding experience
 */
export enum WORKFLOW_TYPES {
  /** Workflow for adding and verifying the business website */
  BUSINESS_WEBSITE = 'ADD_BUSINESS_WEBSITE',
}

export enum PAYMENT_CHANNEL_OPTIONS {
  Websites = 'websites',
  IOS = 'ios',
  Android = 'android',
  SocialMedia = 'socialMedia',
  WhatsappSmsEmail = 'whatsappSmsEmail',
  Others = 'others',
}

export type PaymentChannelData = {
  accept: boolean;
  urls?: { value: string }[];
  value?: string;
  socialMediaUrls?: string[];
};

export type PaymentAcceptanceChannelsType = Record<
  Partial<PAYMENT_CHANNEL_OPTIONS>,
  PaymentChannelData
>;

export type ApiKeys = {
  id: string;
  createdAt: string;
  updatedAt: string;
  expiredAt: string | null;
};

/**
 * Type definition for a merchant's activation data
 * Contains comprehensive information about the merchant used throughout the onboarding experience
 */
export type MerchantActivationDataType = {
  id: string;
  name: {
    billing: string;
    registered: string;
    display: string;
  };
  contactPerson: {
    name: {
      value: string;
    };
  };
  createdAt: string;
  activation?: {
    bddVerificationStatus: MerchantBddVerificationStatusEnum;
    status: MerchantActivationStatusEnum;
    milestone?: MerchantActivationMilestoneEnum;
    isActivated: boolean;
    isTransacted: boolean;
  };
  hasApiKeyAccess?: boolean;
  apiKeys?: ApiKeys[];
  business?: {
    paymentAcceptanceChannels?: PaymentAcceptanceChannelsType;
  };
};

export type MerchantResponseType = {
  merchantById?: MerchantActivationDataType;
};

/**
 * Enum defining the possible statuses for merchant activation
 */
export enum MerchantActivationStatusEnum {
  INSTANTLY_ACTIVATED = 'INSTANTLY_ACTIVATED',
  UNDER_REVIEW = 'UNDER_REVIEW',
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  ACTIVATED = 'ACTIVATED',
  REJECTED = 'REJECTED',
  ACTIVATED_MCC_PENDING = 'ACTIVATED_MCC_PENDING',
  ACTIVATED_KYC_PENDING = 'ACTIVATED_KYC_PENDING',
  KYC_QUALIFIED_UNACTIVATED = 'KYC_QUALIFIED_UNACTIVATED',
  EDD_PENDING = 'EDD_PENDING',
}
/**
 * Enum defining the possible statuses for BDD verification
 */
export enum MerchantBddVerificationStatusEnum {
  VERIFIED = 'VERIFIED',
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  REJECTED = 'REJECTED',
  UNDER_REVIEW = 'UNDER_REVIEW',
}

export enum MerchantActivationMilestoneEnum {
  ACTIVATION_FLOW_COMPLETED = 'ACTIVATION_FLOW_COMPLETED',
  L1_COMPLETED = 'L1_COMPLETED',
  L2_COMPLETED = 'L2_COMPLETED',
  HARD_LIMIT_LEVEL_1 = 'HARD_LIMIT_LEVEL_1',
  HARD_LIMIT_LEVEL_2 = 'HARD_LIMIT_LEVEL_2',
  HARD_LIMIT_LEVEL_4 = 'HARD_LIMIT_LEVEL_4',
  SOFT_LIMIT = 'SOFT_LIMIT',
  SOFT_LIMIT_LEVEL_1 = 'SOFT_LIMIT_LEVEL_1',
  FUNDS_ON_HOLD_REMINDER = 'FUNDS_ON_HOLD_REMINDER',
}
