import { getDefaultUserObj } from 'merchant/models/__tests__/mocks/fixtures/User';

import * as store from 'merchant/store';

const getModeSpy = jest.spyOn(store, 'getMode');

describe('User model', () => {
  afterEach(() => {
    // cleanup splitz experiments
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {},
    };
  });

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

  test('Should return true when `bundle_pricing` experiment is enabled', () => {
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {
        LEgIE3J0zaDwz1: {
          variables: {
            result: 'on',
          },
        },
      },
    };

    const user = getDefaultUserObj();

    expect(user.isBundlePricingEnabled).toBe(true);
  });

  test('Should return false when `bundle_pricing` experiment is enabled', () => {
    const user = getDefaultUserObj();

    expect(user.isBundlePricingEnabled).toBe(false);
  });

  test('should return true when show_international_payments_button_ab experiment is enabled', () => {
    // mock splitz experiment
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {
        LGGuDDOTgodMkG: {
          variables: {
            enable: 'true',
          },
        },
      },
    };

    getModeSpy.mockImplementation(() => 'live');

    const user = getDefaultUserObj();

    expect(user.isShowInternationalPaymentBtnExpEnabled).toBe(true);
  });

  test('should return false when show_international_payments_button_ab experiment is disabled', () => {
    getModeSpy.mockImplementation(() => 'live');

    const user = getDefaultUserObj();

    expect(user.isShowInternationalPaymentBtnExpEnabled).toBe(false);
  });

  test('should return false when show_international_payments_button_ab experiment is enabled, but mode is test', () => {
    // mock splitz experiment
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {
        LGGuDDOTgodMkG: {
          variables: {
            enable: 'true',
          },
        },
      },
    };
    getModeSpy.mockImplementation(() => 'test');

    const user = getDefaultUserObj();

    expect(user.isShowInternationalPaymentBtnExpEnabled).toBe(false);
  });

  test('should return false when show_international_payments_button_ab experiment is enabled, but international is true', () => {
    // mock splitz experiment
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {
        LGGuDDOTgodMkG: {
          variables: {
            enable: 'true',
          },
        },
      },
    };
    getModeSpy.mockImplementation(() => 'live');

    const user = getDefaultUserObj({
      international: true,
    });

    expect(user.isShowInternationalPaymentBtnExpEnabled).toBe(false);
  });

  test('should return false when show_international_payments_button_ab experiment is enabled, but IAF is blacklist', () => {
    // mock splitz experiment
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {
        LGGuDDOTgodMkG: {
          variables: {
            enable: 'true',
          },
        },
      },
    };
    getModeSpy.mockImplementation(() => 'live');

    const user = getDefaultUserObj({
      international_activation_flow: 'blacklist',
    });

    expect(user.isShowInternationalPaymentBtnExpEnabled).toBe(false);
  });

  test('should return true when show_international_payments_button_ab experiment is enabled, but IAF is whiltelist or greylist', () => {
    // mock splitz experiment
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {
        LGGuDDOTgodMkG: {
          variables: {
            enable: 'true',
          },
        },
      },
    };
    getModeSpy.mockImplementation(() => 'live');

    let user = getDefaultUserObj({
      international_activation_flow: 'whitelist',
    });

    expect(user.isShowInternationalPaymentBtnExpEnabled).toBe(true);

    user = getDefaultUserObj({
      international_activation_flow: 'greylist',
    });

    expect(user.isShowInternationalPaymentBtnExpEnabled).toBe(true);
  });
});
