import { useSplitzService } from 'common/splitz';

export const getIsBankingEnabled = (currentUser = {}) => {
  return currentUser.isShowRazorpayXWidgetEnabled && currentUser.isOrgRZP;
};

export const getIsPayrollWidgetEnabled = (currentUser = {}) => {
  return currentUser.isShowPayrollWidgetEnabled && currentUser.isOrgRZP;
};

export const getIsShowAffordabilityWidget = (currentUser = {}) => {
  return currentUser.isShowAffordabilityWidget && currentUser.isOrgRZP;
};

export const getIsCheckoutPaymentMetricsEnabled = (currentUser = {}) =>
  currentUser.isCheckoutAnalyticsEnabled && currentUser.isOrgRZP;

export const usePosOnboardingExperiment = () => {
  const {
    abExperiments: { pos_onboarding },
  } = useSplitzService();

  return {
    isPosOnboardingEnabled: pos_onboarding?.variables?.result === 'on',
  };
};
