import { CountryCodeType } from '@razorpay/i18nify-js';
import {
  PaymentAcceptanceChannelsType,
  PAYMENT_CHANNEL_OPTIONS,
  MerchantActivationStatusEnum,
  MerchantBddVerificationStatusEnum,
  MerchantActivationDataType,
} from '@OnboardingExperienceCommons/types/merchant';
import { PG_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/constants/merchant';

// Checks if any of the specified payment channel options are accepted by the merchant
export const hasAcceptedAnyPaymentChannel = (
  paymentChannels?: PaymentAcceptanceChannelsType,
  options?: PAYMENT_CHANNEL_OPTIONS[],
): boolean => {
  return options?.some((option) => !!paymentChannels?.[option]?.accept) ?? false;
};

// Checks if the merchant has added any website or android or app store URL
export const hasAddedWebsite = (
  paymentChannels?: PaymentAcceptanceChannelsType,
  options = PG_CHANNEL_OPTIONS,
): boolean => {
  return options.some((option) => !!paymentChannels?.[option]?.urls?.[0]?.value);
};

export const PLAY_STORE_APP_URL_PATTERN =
  /^https:\/\/play\.google\.com\/store\/apps\/details\?id=.*/;

export const APP_STORE_APP_URL_PATTERN = /^https:\/\/apps\.apple\.com\/.*/;

export const getSpecificPlatformType = (url?: string): string => {
  if (url && APP_STORE_APP_URL_PATTERN.test(url)) {
    return 'ios';
  } else if (url && PLAY_STORE_APP_URL_PATTERN.test(url)) {
    return 'android';
  } else {
    return 'website';
  }
};

// Checks if the merchant is on NC for both activation status and BDD verification status
export const checkIfNCEnabled = (activation?: MerchantActivationDataType['activation']) =>
  activation?.status === MerchantActivationStatusEnum.NEEDS_CLARIFICATION ||
  activation?.bddVerificationStatus === MerchantBddVerificationStatusEnum.NEEDS_CLARIFICATION;

// Checks if the merchant is in the under review status
export const checkIfUnderReview = (activation?: MerchantActivationDataType['activation']) =>
  [
    MerchantActivationStatusEnum.ACTIVATED_MCC_PENDING,
    MerchantActivationStatusEnum.UNDER_REVIEW,
    MerchantActivationStatusEnum.KYC_QUALIFIED_UNACTIVATED,
  ].includes(activation?.status as MerchantActivationStatusEnum);

export const getNCUrlOnEasyOrPhantom = (signup_campaign: string): string => {
  const EASY_DASHBOARD_NC_LANDING_URL = `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`;
  const PHANTOM_NC_LANDING_URL = `${window.EASY_ONBOARDING_URL}/sub-merchant/onboarding/needs-clarification`;
  return signup_campaign === 'phantom_onboarding'
    ? PHANTOM_NC_LANDING_URL
    : EASY_DASHBOARD_NC_LANDING_URL;
};

export const getCountryOnboardingUrl = (countryCode: CountryCodeType): string => {
  const countryOnboardingUrls: Partial<Record<CountryCodeType, string>> = {
    IN: window.EASY_ONBOARDING_URL,
    MY: window.EASY_DASHBOARD_CURLEC_URL,
    SG: window.EASY_DASHBOARD_SG_URL,
  };

  return countryOnboardingUrls[countryCode] || window.EASY_ONBOARDING_URL;
};

// Checks if any of the specified payment channel options are accepted by the merchant
export const isAppIntentMerchant = (paymentChannels?: PaymentAcceptanceChannelsType): boolean => {
  return hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Android,
    PAYMENT_CHANNEL_OPTIONS.IOS,
  ]);
};
