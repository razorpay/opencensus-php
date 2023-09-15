import { deleteAccountConfigUtil } from 'merchant/views/MagicCheckout/AnalyticsSettings/utils';
import { ANALYTICS_PLATFORM } from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

describe('testing delete account utility function', () => {
  test('should be able to delete the config', () => {
    const id = '123';
    const accountConfig = [
      {
        id: '123',
      },
    ];

    const newConfig = deleteAccountConfigUtil(
      id,
      accountConfig,
      ANALYTICS_PLATFORM.googleAnalytics.key,
    );
    expect(newConfig[0].integrationMethod).toBe('frontend');
  });
});
