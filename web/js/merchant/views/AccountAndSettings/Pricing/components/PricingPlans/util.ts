import { PAYMENT_TYPE } from 'common/ui/PricingSubscription/constants';
import {
  SubscriptionPlanDataT,
  StatusDataT,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.types';
import {
  STATUS_DATA,
  PRICING_PLAN_STATUS,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';

import type { PaymentType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

const getStatusData = (
  subscriptionPlanData: SubscriptionPlanDataT | undefined,
  type: PaymentType | undefined,
): StatusDataT | null => {
  if (type === PAYMENT_TYPE.PG) {
    if (
      [PRICING_PLAN_STATUS.processing, PRICING_PLAN_STATUS.created].includes(
        subscriptionPlanData?.status as string,
      ) &&
      subscriptionPlanData?.payment_subscription?.status === PRICING_PLAN_STATUS.active
    )
      return STATUS_DATA.IN_PROGRESS;

    if (
      subscriptionPlanData?.status === PRICING_PLAN_STATUS.approved &&
      subscriptionPlanData?.payment_subscription?.status === PRICING_PLAN_STATUS.active
    )
      return STATUS_DATA.LIVE;
  } else if (type === PAYMENT_TYPE.INTERNAL) {
    if (subscriptionPlanData?.internal_subscription?.status === PRICING_PLAN_STATUS.pending)
      return STATUS_DATA.PAYMENT_PROCESSING;
    else if (
      subscriptionPlanData?.status === PRICING_PLAN_STATUS.created &&
      subscriptionPlanData?.internal_subscription?.status === PRICING_PLAN_STATUS.processed
    )
      return STATUS_DATA.IN_PROGRESS;
    else if (
      subscriptionPlanData?.status === PRICING_PLAN_STATUS.approved &&
      subscriptionPlanData?.internal_subscription?.status === PRICING_PLAN_STATUS.processed
    )
      return STATUS_DATA.LIVE;
  }

  return null;
};

export { getStatusData };
