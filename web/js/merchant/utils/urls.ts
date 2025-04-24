import { CountryCodeType } from '@razorpay/i18nify-js';

export const getNCUrlOnEasyOrPhantom = (): string => {
  const EASY_DASHBOARD_NC_LANDING_URL = `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`;
  const PHANTOM_NC_LANDING_URL = `${window.EASY_ONBOARDING_URL}/sub-merchant/onboarding/needs-clarification`;
  return window.rzp_user?.user?.signup_campaign === 'phantom_onboarding'
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
