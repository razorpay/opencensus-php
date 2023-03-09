import BundlePricing from 'merchant/models/BundlePricing';
import { fetchEnrollmentStatusHandler } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/__test__/mocks/handlers';
import { server } from 'test-utils';

describe('Tests for `BundlePricing` class', () => {
  const bundlePricing = new BundlePricing();

  test('Returns `hasEnrolled` as `true` when user has enrolled in bundle pricing', async () => {
    server.use(fetchEnrollmentStatusHandler({ exists: 'true', delay: 0 }));
    const result = await bundlePricing.fetchEnrollmentStatus();

    expect(result).toEqual(
      expect.objectContaining({
        hasEnrolled: true,
      }),
    );
  });

  test('Returns `hasEnrolled` as `false` when user has enrolled in bundle pricing', async () => {
    server.use(fetchEnrollmentStatusHandler({ exists: 'false', delay: 0 }));
    const result = await bundlePricing.fetchEnrollmentStatus();

    expect(result).toEqual(
      expect.objectContaining({
        hasEnrolled: false,
      }),
    );
  });

  test('Returns correct message', async () => {
    const message = 'Subscription not active';
    server.use(fetchEnrollmentStatusHandler({ message, delay: 0 }));
    const result = await bundlePricing.fetchEnrollmentStatus();

    expect(result).toEqual(
      expect.objectContaining({
        message,
      }),
    );
  });
});
