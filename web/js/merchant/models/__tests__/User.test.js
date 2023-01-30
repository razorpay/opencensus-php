import { getDefaultUserObj } from 'merchant/models/__tests__/mocks/fixtures/User';

describe('User model', () => {
  test('should return true when merchant feature flag enable_merchant_expiry_pl is set', () => {
    const user = getDefaultUserObj({
      features: [
        {
          feature: 'enable_merchant_expiry_pl',
          value: true,
          display_name: 'Enables merchant to select no expiry option on payment links.',
        },
      ],
    });
    const isMerchantExpiryPL = user.isMerchantExpiryPL;
    expect(isMerchantExpiryPL).toBe(true);
  });

  test('should return false when merchant feature flag enable_merchant_expiry_pl is not set', () => {
    const user = getDefaultUserObj({
      features: [],
    });
    const isMerchantExpiryPL = user.isMerchantExpiryPL;
    expect(isMerchantExpiryPL).toBe(false);
  });
});
