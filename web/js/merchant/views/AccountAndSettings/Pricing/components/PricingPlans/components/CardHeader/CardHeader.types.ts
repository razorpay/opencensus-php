import {
  SubscriptionPlanDataT,
  StatusDataT,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.types';

type CardHeaderPropsT = {
  isMobileResolution: boolean;
  subscriptionPlanData: SubscriptionPlanDataT | undefined;
  statusData: StatusDataT;
};

export { CardHeaderPropsT };
