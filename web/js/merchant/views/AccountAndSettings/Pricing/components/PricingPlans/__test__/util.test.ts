import { SubscriptionPlanDataT } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.types';
import { getSubscriptionDataRes } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/__test__/mocks/response';
import { STATUS_DATA } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';
import { getStatusData } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/util';

describe('Tests for `getStatusData` function', () => {
  test('Should return `In Progress` status when such as data is passed', () => {
    const subscriptionPlanData = getSubscriptionDataRes({
      subscriptionStatus: 'processing',
    }).subscription;
    const result = getStatusData(subscriptionPlanData as unknown as SubscriptionPlanDataT, 'PG');

    expect(result).toMatchObject(STATUS_DATA.IN_PROGRESS);
  });

  test('Should return `Live` status when such as data is passed', () => {
    const subscriptionPlanData = getSubscriptionDataRes().subscription;
    const result = getStatusData(subscriptionPlanData as unknown as SubscriptionPlanDataT, 'PG');

    expect(result).toMatchObject(STATUS_DATA.LIVE);
  });

  test('Should return `null` status when status cannot be determined', () => {
    const subscriptionPlanData = getSubscriptionDataRes({
      subscriptionStatus: 'anything',
    }).subscription;
    const result = getStatusData(subscriptionPlanData as unknown as SubscriptionPlanDataT, 'PG');

    expect(result).toBe(null);
  });

  test('Should return `null` status when undefined value is passed', () => {
    const result = getStatusData(undefined, undefined);

    expect(result).toBe(null);
  });
});
