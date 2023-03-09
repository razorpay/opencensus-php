import {
  SubscriptionPlanDataT,
  StatusDataT,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.types';
import { STATUS_DATA } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';

const getStatusData = (
  subscriptionPlanData: SubscriptionPlanDataT | undefined,
): StatusDataT | null => {
  if (
    ['processing', 'created'].includes(subscriptionPlanData?.status as string) &&
    subscriptionPlanData?.payment_subscription?.status === 'active'
  )
    return STATUS_DATA.IN_PROGRESS;

  if (
    subscriptionPlanData?.status === 'approved' &&
    subscriptionPlanData?.payment_subscription?.status === 'active'
  )
    return STATUS_DATA.LIVE;

  return null;
};

export { getStatusData };
