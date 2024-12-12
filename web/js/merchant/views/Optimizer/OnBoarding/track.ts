const CLICKED_ACTION = 'clicked';
const VISITED_ACTION = 'visited';

export const PRICING_PLAN_VISIT = {
  objectName: 'Optimizer Pricing Page',
  actionName: VISITED_ACTION,
  screen: 'Optimizer - Onboarding',
};

export const PRICING_PLAN_LEARN_MORE_CLICK = {
  objectName: 'Optimizer Pricing Page Learn More',
  actionName: CLICKED_ACTION,
  screen: 'Optimizer - Onboarding',
};

export const PRICING_PLAN_GET_STARTED_CLICK = {
  objectName: 'Optimizer Pricing Page Get Started',
  actionName: CLICKED_ACTION,
  screen: 'Optimizer - Onboarding',
};

export const SAVE_GATEWAY_PAGE_VISIT = {
  objectName: 'Optimizer Save Gateway Page',
  actionName: VISITED_ACTION,
  screen: 'Optimizer - Onboarding',
};

export const SAVE_GATEWAY_BACK_CLICK = {
  objectName: 'Optimizer Save Gateway Page Back',
  actionName: CLICKED_ACTION,
  screen: 'Optimizer - Onboarding',
};

export const saveGatewayCredsClick = (properties) => {
  return {
    objectName: 'Optimizer Save Credentials',
    actionName: CLICKED_ACTION,
    screen: 'Optimizer - Onboarding',
    properties,
  };
};

export const submitDetailsClick = (properties) => {
  return {
    objectName: 'Optimizer Submit Details',
    actionName: CLICKED_ACTION,
    screen: 'Optimizer - Onboarding',
    properties,
  };
};

export const OPTIMIZER_BLOG_CLICK = {
  objectName: 'Optimizer Onboarding Success Page Go to Blog',
  actionName: CLICKED_ACTION,
  screen: 'Optimizer - Onboarding',
};
