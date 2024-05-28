import { PAYMENT_METHODS } from 'merchant/views/Subscriptions/constants';

import {
  getPaymentMethodOptions,
  checkIfAmount,
  checkIfAmountForFirstCharge,
} from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/PaymentDetails/utils';

describe('Payment methods data structure', () => {
  it('should return the correct payment methods based on the esign flag', () => {
    const optionsWithEsign = getPaymentMethodOptions(true);
    const optionsWithoutEsign = getPaymentMethodOptions(false);

    expect(optionsWithEsign[PAYMENT_METHODS.EMANDATE].desc).toContain('Aadhaar');
    expect(optionsWithoutEsign[PAYMENT_METHODS.EMANDATE].desc).not.toContain('Aadhaar');
  });
});

describe('checkIfAmount', () => {
  it('returns "Invalid Amount" for non-numeric values', () => {
    expect(checkIfAmount('abc')).toBe('Invalid Amount');
  });

  it('returns undefined for valid amounts', () => {
    expect(checkIfAmount('100')).toBeFalsy();
  });
});

describe('checkIfAmountForFirstCharge', () => {
  it('returns null for zero amount', () => {
    expect(checkIfAmountForFirstCharge(1000, '0')).toBeNull();
  });

  it('returns error if the amount exceeds the max amount', () => {
    expect(checkIfAmountForFirstCharge(100, '200')).toBe(
      'Amount should be less than or equal to Token Max Amount',
    );
  });

  it('returns "Invalid Amount" for non-numeric values', () => {
    expect(checkIfAmountForFirstCharge(1000, 'abc')).toBe('Invalid Amount');
  });
});
