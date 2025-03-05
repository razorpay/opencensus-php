import { MAX_DISCOUNT } from 'merchant/views/Offers/constants';

import {
  prepareDataForSubmit,
  validatePaymentMethod,
  validateMaxPaymentCount,
  emiDurationString,
  validateDiscountType,
} from '../helpers';

jest.mock('common/utils/rzp-utils', () => ({
  rupeesToPaise: jest.fn((val) => val * 100),
  deepClone: jest.fn((val) => JSON.parse(JSON.stringify(val))),
}));

jest.mock('merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper', () => ({
  filterNoCostTenures: jest.fn((emiDurations, lowCostEmi) =>
    emiDurations.filter((tenure) => !lowCostEmi.includes(tenure)),
  ),
}));

jest.mock('common/utils/rzp-utils', () => ({
  rupeesToPaise: jest.fn((val) => val * 100),
  deepClone: jest.fn((val) => JSON.parse(JSON.stringify(val))),
}));

jest.mock('merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper', () => ({
  filterNoCostTenures: jest.fn((emiDurations, lowCostEmi) =>
    emiDurations.filter((tenure) => !lowCostEmi.includes(tenure)),
  ),
}));

describe('validatePaymentMethod', () => {
  it('should return error message if payment method is empty', () => {
    const result = validatePaymentMethod('');
    expect(result).toBe('Payment method cannot be empty');
  });

  it('should return false if payment method is not empty', () => {
    const result = validatePaymentMethod('card');
    expect(result).toBe(false);
  });

  it('should return error message if payment method is empty', () => {
    const result = validatePaymentMethod([]);
    expect(result).toBe('Payment method cannot be empty');
  });

  it('should return false if payment method is not empty', () => {
    const result = validatePaymentMethod(['card']);
    expect(result).toBe(false);
  });
});

describe('validateMaxPaymentCount', () => {
  it('should return false if value is empty', () => {
    const result = validateMaxPaymentCount('');
    expect(result).toBe(false);
  });

  it('should return error message if value is not a number', () => {
    const result = validateMaxPaymentCount('abc');
    expect(result).toBe('Please enter a number');
  });

  it('should return error message if value exceeds max discount', () => {
    const result = validateMaxPaymentCount('1000000000');
    expect(result).toBe(`Maximum value allowed is ${MAX_DISCOUNT}`);
  });

  it('should return false if value is valid', () => {
    const result = validateMaxPaymentCount('5000');
    expect(result).toBe(false);
  });
});

describe('emiDurationString', () => {
  it('should return formatted string for single duration', () => {
    const result = emiDurationString([3]);
    expect(result).toBe('3 months');
  });

  it('should return formatted string for multiple durations', () => {
    const result = emiDurationString([3, 6, 9]);
    expect(result).toBe('3, 6 and 9 months');
  });
});

describe('prepareDataForSubmit', () => {
  const formData = {
    max_cashback: '500',
    flat_cashback: '200',
    min_amount: '1000',
    percent_rate: '5',
    max_order_amount: '10000',
    starts_at: { unix: jest.fn(() => 1234567890) },
    ends_at: { unix: jest.fn(() => 1234567891) },
    payment_method_type: 'credit_card',
    issuer: 'VISA',
    payment_network: 'VISA',
    max_payment_count: '10',
    iins: '',
    max_offer_usage: '5',
    default_offer: '1',
    block: '0',
    discount_type: 'flat',
    redemption_type: 'type1',
    applicable_on: 'all',
    no_of_cycles: '12',
    product_type: 'subscription',
  };

  it('should process checkbox fields correctly', () => {
    const result = prepareDataForSubmit(formData, false);
    expect(result.default_offer).toBe(1);
    expect(result.block).toBe(0);
  });

  it('should process date fields correctly', () => {
    const result = prepareDataForSubmit(formData, false);
    expect(result.starts_at).toBe(1234567890);
    expect(result.ends_at).toBe(1234567891);
  });

  it('should convert rupees to paise', () => {
    const result = prepareDataForSubmit(formData, false);
    expect(result.flat_cashback).toBe(20000);
    expect(result.min_amount).toBe(100000);
    expect(result.max_order_amount).toBe(1000000);
  });

  it('should delete appropriate fields based on discount_type flat', () => {
    const result = prepareDataForSubmit(formData, false);
    expect(result).not.toHaveProperty('max_cashback');
    expect(result).not.toHaveProperty('percent_rate');
  });

  it('should handle subscription product_type correctly', () => {
    const result = prepareDataForSubmit(formData, false);
    expect(result.subscription).toEqual({
      redemption_type: 'type1',
      applicable_on: 'all',
      no_of_cycles: '12',
    });
  });

  it('should delete fields when data is null', () => {
    const emptyFormData = { ...formData, payment_method_type: null };
    const result = prepareDataForSubmit(emptyFormData, false);
    expect(result).not.toHaveProperty('payment_method_type');
  });

  it('should handle issuer transformation correctly', () => {
    const formDataWithIssuer = { ...formData, issuer: 'AMEX' };
    const result = prepareDataForSubmit(formDataWithIssuer, false);
    expect(result.payment_network).toBe('AMEX');
    expect(result).not.toHaveProperty('issuer');
  });
});

describe('emiDurationString', () => {
  it('should return formatted string for single duration', () => {
    const result = emiDurationString([3]);
    expect(result).toBe('3 months');
  });

  it('should return formatted string for multiple durations', () => {
    const result = emiDurationString([3, 6, 9]);
    expect(result).toBe('3, 6 and 9 months');
  });

  it('should handle empty array', () => {
    const result = emiDurationString([]);
    expect(result).toBe(' months');
  });

  it('should handle array with one item', () => {
    const result = emiDurationString([6]);
    expect(result).toBe('6 months');
  });

  it('should handle array with duplicate items', () => {
    const result = emiDurationString([6, 6, 6]);
    expect(result).toBe('6, 6 and 6 months');
  });
});

describe('validateDiscountType', () => {
  it('should return error message if discount type is empty', () => {
    const hasError = validateDiscountType('');
    expect(typeof hasError).toBe('string');
    expect(hasError.length).not.toBe(0);
  });

  it('should return false if discount type is valid', () => {
    const hasError = validateDiscountType('instant');
    expect(hasError).toBe(false);
  });
});
