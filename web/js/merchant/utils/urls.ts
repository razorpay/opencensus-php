export const getNCUrlOnEasyOrPhantom = (): string => {
  const EASY_DASHBOARD_NC_LANDING_URL = `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`;
  const PHANTOM_NC_LANDING_URL = `${window.EASY_ONBOARDING_URL}/sub-merchant/onboarding/needs-clarification`;
  return window.rzp_user?.user?.signup_campaign === 'phantom_onboarding'
    ? PHANTOM_NC_LANDING_URL
    : EASY_DASHBOARD_NC_LANDING_URL;
};
