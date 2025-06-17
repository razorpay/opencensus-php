import { CURRENCY_FORMATTERS, isCurrencyThreeDecimal } from 'merchant/helpers/currency/helper';
import { isPosExperimentEnabled, isPosTabVisible } from '../pos-helper';
import { MOCK_USER } from './fixtures/mocks/helper';
describe('Tests for currency formatting', () => {
  test('Test for three decimal currency formatting', () => {
    expect(CURRENCY_FORMATTERS.three(Number(1111111).toFixed(2), 2)).toBe('1,111,111.00');
  });

  test('Test for threecommadecimal currency formatting', () => {
    expect(CURRENCY_FORMATTERS.threecommadecimal(Number(1111111).toFixed(2), 2)).toBe(
      '1.111.111,00',
    );
  });

  test('Test for threespaceseparator currency formatting', () => {
    expect(CURRENCY_FORMATTERS.threespaceseparator(Number(1111111).toFixed(2), 2)).toBe(
      '1 111 111.00',
    );
  });

  test('Test for threespacecommadecimal currency formatting', () => {
    expect(CURRENCY_FORMATTERS.threespacecommadecimal(Number(1111111).toFixed(2), 2)).toBe(
      '1 111 111,00',
    );
  });

  test('Test for szl currency formatting', () => {
    expect(CURRENCY_FORMATTERS.szl(Number(1111111).toFixed(2), 2)).toBe('1, 111, 111.00');
  });

  test('Test for chf currency formatting', () => {
    expect(CURRENCY_FORMATTERS.chf(Number(1111111).toFixed(2), 2)).toBe(`1'111'111.00`);
  });

  test('Test for inr currency formatting', () => {
    expect(CURRENCY_FORMATTERS.inr(Number(1111111).toFixed(2), 2)).toBe('11,11,111.00');
  });

  test('Test for myr currency formatting', () => {
    expect(CURRENCY_FORMATTERS.myr(Number(1111111).toFixed(2), 2)).toBe('1,111,111.00');
  });

  test('Test for none currency formatting', () => {
    expect(CURRENCY_FORMATTERS.none(Number(1111111).toFixed(2), 2)).toBe('1111111.00');
  });
});

describe('Tests for isCurrencyThreeDecimal', () => {
  test('Function should return true when 3 decimal currencies are passed', () => {
    expect(isCurrencyThreeDecimal('KWD')).toBe(true);
    expect(isCurrencyThreeDecimal('BHD')).toBe(true);
    expect(isCurrencyThreeDecimal('OMR')).toBe(true);
  });

  test('Function should return false when 2 decimal currencies are passed', () => {
    expect(isCurrencyThreeDecimal('INR')).toBe(false);
    expect(isCurrencyThreeDecimal('USD')).toBe(false);
    expect(isCurrencyThreeDecimal('AUD')).toBe(false);
  });
});

describe('POS sidebar condtions', () => {
  test('isPosExperimentEnabled should return true when experiment is disabled for pgos merchant', () => {
    const newAbExperiments = {
      pos_api_merchant_enablement: {
        experimentId: 'mock-exp-id',
        variables: {
          result: 'off',
        },
      },
    };

    const isExperimentEnabled = isPosExperimentEnabled({
      user: MOCK_USER,
      abExperiments: newAbExperiments,
    });
    expect(isExperimentEnabled).toBe(true);
  });

  test('isPosExperimentEnabled should return false when experiment is disabled for non-pgos merchant', () => {
    const newAbExperiments = {
      pos_api_merchant_enablement: {
        experimentId: 'mock-exp-id',
        variables: {
          result: 'off',
        },
      },
    };

    const user = { ...MOCK_USER, is_pgos_merchant: false };

    const isExperimentEnabled = isPosExperimentEnabled({
      user,
      abExperiments: newAbExperiments,
    });
    expect(isExperimentEnabled).toBe(false);
  });

  test('isPosExperimentEnabled should return true when experiment is enabled for non-pgos merchant and KYC activation status is activated', () => {
    const newAbExperiments = {
      pos_api_merchant_enablement: {
        experimentId: 'mock-exp-id',
        variables: {
          result: 'on',
        },
      },
    };

    const user = { ...MOCK_USER, is_pgos_merchant: false, activation_status: 'activated' };

    const isExperimentEnabled = isPosExperimentEnabled({
      user,
      abExperiments: newAbExperiments,
    });
    expect(isExperimentEnabled).toBe(true);
  });

  test('isPosExperimentEnabled should return false when experiment is enabled for non-pgos merchant and KYC activation status is other than activated', () => {
    const newAbExperiments = {
      pos_api_merchant_enablement: {
        experimentId: 'mock-exp-id',
        variables: {
          result: 'on',
        },
      },
    };

    const user = { ...MOCK_USER, is_pgos_merchant: false, activation_status: 'under_review' };

    const isExperimentEnabled = isPosExperimentEnabled({
      user,
      abExperiments: newAbExperiments,
    });
    expect(isExperimentEnabled).toBe(false);
  });

  test('isPosExperimentEnabled should return false for unregistered merchants', () => {
    const newAbExperiments = {
      pos_api_merchant_enablement: {
        experimentId: 'mock-exp-id',
        variables: {
          result: 'off',
        },
      },
    };

    const isExperimentEnabled = isPosExperimentEnabled({
      user: { ...MOCK_USER, business_type: '11' },
      abExperiments: newAbExperiments,
    });
    expect(isExperimentEnabled).toBe(false);
  });

  test('isPosExperimentEnabled should return true when experiment is enabled and kyc status is activated', () => {
    const newAbExperiments = {
      pos_api_merchant_enablement: {
        experimentId: 'mock-exp-id',
        variables: {
          result: 'off',
        },
      },
    };
    const user = { ...MOCK_USER, activation_status: 'activated' };
    const isVisible = isPosTabVisible(user, newAbExperiments);

    expect(isVisible).toBe(false);
  });

  test('isPosExperimentEnabled should return false when experiment is enabled and kyc status is other than activated', () => {
    const newAbExperiments = {
      pos_api_merchant_enablement: {
        experimentId: 'mock-exp-id',
        variables: {
          result: 'off',
        },
      },
    };
    const user = { ...MOCK_USER, activation_status: 'pending' };
    const isVisible = isPosTabVisible(user, newAbExperiments);

    expect(isVisible).toBe(false);
  });
});
