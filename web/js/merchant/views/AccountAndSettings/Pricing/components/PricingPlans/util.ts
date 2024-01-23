import { PAYMENT_TYPE } from 'common/ui/PricingSubscription/constants';
import {
  StatusDataT,
  SubscriptionPlanDataT,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.types';
import {
  PRICING_PLAN_STATUS,
  STATUS_DATA,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';

import type { PaymentType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

const getStatusData = (
  subscriptionPlanData: SubscriptionPlanDataT | undefined,
  type: PaymentType | undefined,
): StatusDataT | null => {
  const subscriptionStatus = subscriptionPlanData?.status;

  if (type === PAYMENT_TYPE.PG) {
    const paymentSubscriptionStatus = subscriptionPlanData?.payment_subscription?.status;
    if (
      [PRICING_PLAN_STATUS.processing, PRICING_PLAN_STATUS.created].includes(
        subscriptionStatus as string,
      ) &&
      paymentSubscriptionStatus === PRICING_PLAN_STATUS.active
    )
      return STATUS_DATA.IN_PROGRESS;

    const isPlanApproved = subscriptionStatus === PRICING_PLAN_STATUS.approved;
    const isSubscriptionActive = paymentSubscriptionStatus === PRICING_PLAN_STATUS.active;
    const isSubscriptionCancelled = paymentSubscriptionStatus === PRICING_PLAN_STATUS.cancelled;
    const isCurrentEndValid =
      Number(subscriptionPlanData?.current_end) >= Math.floor(new Date().getTime() / 1000);

    if (
      isPlanApproved &&
      (isSubscriptionActive || (isSubscriptionCancelled && isCurrentEndValid))
    ) {
      return STATUS_DATA.LIVE;
    }
  } else if (type === PAYMENT_TYPE.INTERNAL) {
    const internalSubscriptionStatus = subscriptionPlanData?.internal_subscription?.status;
    if (internalSubscriptionStatus === PRICING_PLAN_STATUS.pending)
      return STATUS_DATA.PAYMENT_PROCESSING;
    else if (
      subscriptionStatus === PRICING_PLAN_STATUS.created &&
      internalSubscriptionStatus === PRICING_PLAN_STATUS.processed
    )
      return STATUS_DATA.IN_PROGRESS;
    else if (
      subscriptionStatus === PRICING_PLAN_STATUS.approved &&
      internalSubscriptionStatus === PRICING_PLAN_STATUS.processed
    )
      return STATUS_DATA.LIVE;
  }

  return null;
};

export { getStatusData };
