export const CLICK_READ_MORE = {
  objectName: `Optimizer Know More on Landing`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const CLICK_KNOW_MORE = {
  objectName: `Optimizer Know More on Features`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const CLICK_BACK_ON_FEATURES = {
  objectName: `Optimizer Back on Features`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const CLICK_ACTIVATE_NOW_ON_FEATURES = {
  objectName: `Optimizer Activate Now on features`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const CLICK_BACK_ON_SURVEY = {
  objectName: `Optimizer Back on Survey`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const CLICK_NEXT_ON_SURVEY = {
  objectName: `Optimizer Next on Survey`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const clickBookDemo = (details) => ({
  objectName: `Optimizer Book Demo`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
  properties: { ...details },
});

export const CLICK_BACK_ON_PRICING_PLAN = {
  objectName: `Optimizer Back on Pricing Plan`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const clickActivateNowOnPricingPlan = (isActivated) => ({
  objectName: `Optimizer Activate Now on pricing plan`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
  properties: { isActivated },
});

export const onboardPageVisit = ({ active, ...rest }) => ({
  objectName: 'Optimizer Onboarding',
  actionName: `page ${active + 1} visited`,
  screen: 'Optimizer - Onboarding',
  properties: { ...rest, charge: '0.25% per transaction' },
});
