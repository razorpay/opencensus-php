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

export const CLICK_NEXT_ON_FEATURES = {
  objectName: `Optimizer Next on features`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const CLICK_BACK_ON_BRANDS = {
  objectName: `Optimizer Back on Brands`,
  actionName: 'clicked',
  screen: 'Optimizer - Onboarding',
};

export const CLICK_NEXT_ON_BRANDS = {
  objectName: `Optimizer Next on Brands`,
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

export const onboardPageVisit = ({ active, ...rest }) => ({
  objectName: 'Optimizer Onboarding',
  actionName: `page ${active + 1} visited`,
  screen: 'Optimizer - Onboarding',
  properties: { ...rest, charge: '0.25% per transaction' },
});
