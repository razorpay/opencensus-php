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
  createdAt: string;
  activation?: {
    status: string;
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
