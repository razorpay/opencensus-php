import { getDefaultUserObj } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/User';
import store from 'merchant/store';
import { showNoExpiryPL, showPayerNamePL } from 'merchant/views/PaymentLinks/utils';
describe('Payment Page Helper', () => {
  const stateSpy = jest.spyOn(store, 'getState');

  test('should return true when the org feature flag "hide_no_expiry_for_pl" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [],
        }),
        org: {
          features: [],
        },
      },
    });
    expect(showNoExpiryPL()).toBe(true);
  });

  test('should return false when the org feature flag "hide_no_expiry_for_pl" is enabled & the merchant feature flag "enable_merchant_expiry_pp" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [],
        }),
        org: {
          features: ['hide_no_expiry_for_pl'],
        },
      },
    });
    expect(showNoExpiryPL()).toBe(false);
  });

  test('should return true when the org feature flag "hide_no_expiry_for_pl" & the merchant feature flag "enable_merchant_expiry_pl" are enabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [
            {
              feature: 'enable_merchant_expiry_pl',
              value: true,
              display_name: 'Enables merchant to select no expiry while creating payment link.',
            },
          ],
        }),
        org: {
          features: ['hide_no_expiry_for_pl'],
        },
      },
    });
    expect(showNoExpiryPL()).toBe(true);
  });

  test('should return true when the org feature flag "enable_payer_name_for_pl" is enabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: {},
        org: {
          features: ['enable_payer_name_for_pl'],
        },
      },
    });
    expect(showPayerNamePL()).toBe(true);
  });

  test('should return false when the org feature flag "enable_payer_name_for_pl" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [],
        }),
        org: {},
      },
    });
    expect(showPayerNamePL()).toBe(false);
  });
});
