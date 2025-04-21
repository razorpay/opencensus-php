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

export type PaymentAcceptanceChannelsType = {
  [PAYMENT_CHANNEL_OPTIONS.Websites]?: {
    urls: { value: string }[];
    accept: boolean;
  };
  [PAYMENT_CHANNEL_OPTIONS.IOS]?: {
    urls: { value: string }[];
    accept: boolean;
  };
  [PAYMENT_CHANNEL_OPTIONS.Android]?: {
    urls: { value: string }[];
    accept: boolean;
  };
  [PAYMENT_CHANNEL_OPTIONS.SocialMedia]?: {
    accept: boolean;
    socialMediaUrls: string[];
  };
  [PAYMENT_CHANNEL_OPTIONS.WhatsappSmsEmail]?: {
    accept: boolean;
  };
  [PAYMENT_CHANNEL_OPTIONS.Others]?: {
    accept: boolean;
    value: string;
  };
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
  apiKeys?: {
    id: string;
    createdAt: string;
    updatedAt: string;
    expiredAt: string | null;
  }[];
  business?: {
    paymentAcceptanceChannels?: PaymentAcceptanceChannelsType;
  };
};

export type MerchantResponseType = {
  merchantById?: MerchantActivationDataType;
};
