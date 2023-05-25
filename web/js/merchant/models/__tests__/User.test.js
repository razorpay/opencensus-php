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

  test('should return true when merchant feature flag "file_upload_pp" is set', () => {
    const user = getDefaultUserObj({
      features: [
        {
          feature: 'file_upload_pp',
          value: true,
          display_name: 'Feature to enable file upload functionality on payment pages',
        },
      ],
    });
    const isPaymentPageFileUploadEnabled = user.isPaymentPageFileUploadEnabled;
    expect(isPaymentPageFileUploadEnabled).toBe(true);
  });

  test('should return false when merchant feature flag "file_upload_pp" is not set', () => {
    const user = getDefaultUserObj({
      features: [],
    });
    const isPaymentPageFileUploadEnabled = user.isPaymentPageFileUploadEnabled;
    expect(isPaymentPageFileUploadEnabled).toBe(false);
  });
  test('should return true when merchant feature flag hide_instrument_request is set', () => {
    const user = getDefaultUserObj();

    jest.spyOn(user, 'isInstrumentRequestHidden', 'get').mockReturnValue(true);

    const isInstrumentRequestHidden = user.isInstrumentRequestHidden;
    expect(isInstrumentRequestHidden).toBe(true);
  });

  test('should return false when merchant feature flag hide_instrument_request is not set', () => {
    const user = getDefaultUserObj();

    jest.spyOn(user, 'isInstrumentRequestHidden', 'get').mockReturnValue(false);

    const isInstrumentRequestHidden = user.isInstrumentRequestHidden;
    expect(isInstrumentRequestHidden).toBe(false);
  });

  test('get isIssuingBulkUploadEnabled: exp disabled', () => {
    const user = getDefaultUserObj();

    jest.spyOn(user, 'getExpStatus').mockReturnValue(false);
    jest.spyOn(user, 'userRole', 'get').mockReturnValue('manager');

    const isIssuingBulkUploadEnabled = user.isIssuingBulkUploadEnabled;
    expect(isIssuingBulkUploadEnabled).toBe(false);
  });

  test('get isIssuingBulkUploadEnabled: when exp enabeld, but role criteria not met', () => {
    const user = getDefaultUserObj();

    jest.spyOn(user, 'getExpStatus').mockReturnValue(true);
    jest.spyOn(user, 'userRole', 'get').mockReturnValue('support');

    const isIssuingBulkUploadEnabled = user.isIssuingBulkUploadEnabled;
    expect(isIssuingBulkUploadEnabled).toBe(false);
  });

  test('get isIssuingBulkUploadEnabled: exp enabled, role criteria met', () => {
    const user = getDefaultUserObj();

    jest.spyOn(user, 'getExpStatus').mockReturnValue(true);
    jest.spyOn(user, 'userRole', 'get').mockReturnValue('manager');

    const isIssuingBulkUploadEnabled = user.isIssuingBulkUploadEnabled;
    expect(isIssuingBulkUploadEnabled).toBe(true);
  });

  describe('isOptimizerEnabled', () => {
    const user = getDefaultUserObj();
    test('should return false when both "raas" and "optimizer_razorpay_vas" features are enabled', () => {
      user.isFeatureEnabled = jest
        .fn()
        .mockReturnValueOnce(true) // raas feature is enabled
        .mockReturnValueOnce(true); // optimizer_razorpay_vas feature is enabled

      expect(user.isOptimizerEnabled).toBe(false);
    });

    test('should return true when "raas" feature is enabled but "optimizer_razorpay_vas" feature is disabled', () => {
      user.isFeatureEnabled = jest
        .fn()
        .mockReturnValueOnce(true) // raas feature is enabled
        .mockReturnValueOnce(false); // optimizer_razorpay_vas feature is disabled

      expect(user.isOptimizerEnabled).toBe(true);
    });

    test('should return false when "raas" feature is disabled', () => {
      user.isFeatureEnabled = jest.fn().mockReturnValueOnce(false); // raas feature is disabled

      expect(user.isOptimizerEnabled).toBe(false);
    });
  });

  describe('isOptimizerRZPVASEnabled', () => {
    const user = getDefaultUserObj();
    test('should return true when "optimizer_razorpay_vas" feature is enabled', () => {
      user.isFeatureEnabled = jest.fn().mockReturnValue(true);

      expect(user.isOptimizerRZPVASEnabled).toBe(true);
    });

    test('should return false when "optimizer_razorpay_vas" feature is disabled', () => {
      user.isFeatureEnabled = jest.fn().mockReturnValue(false);

      expect(user.isOptimizerRZPVASEnabled).toBe(false);
    });
  });

  describe('isCustomReportExtensionsEnabled', () => {
    test('should return true if when org level feature flag - custom_report_extensions is enabled', () => {
      const user = getDefaultUserObj();

      user.isOrgFeatureEnabled = jest.fn().mockReturnValueOnce(true);

      expect(user.isCustomReportExtensionsEnabled).toBe(true);
    });

    test('should return true if when org level feature flag - custom_report_extensions is not enabled', () => {
      const user = getDefaultUserObj();

      expect(user.isCustomReportExtensionsEnabled).toBe(false);
    });
  });
});
