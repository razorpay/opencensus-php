import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import DiscountType, {
  validateDiscountType,
  validateFlatCashback,
  validateMaxCashback,
} from '../DiscountTypes';
import { validateDecimalPointValue } from '../NoCostEMI/helpers/helper';
const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('DiscountTypes form', () => {
  it('should render instant discount text', async () => {
    const values = {
      discount_type: '',
      min_amount: 0,
      max_order_amount: 0,
      flat_cashback: 0,
      percent_rate: 0,
      max_cashback: 0,
      redemption_type: '',
      no_of_cycles: 0,
    };

    render(
      <DiscountType
        offerType="Instant"
        values={values}
        currencySymbol="₹"
        isFormLocked={false}
        hideDiscountType={false}
        showSubscriptionOfferFields={false}
        errors={{}}
        touched={{}}
      />,
    );
    await waitFor(() => {
      expect(screen.getByText('Discount Type')).toBeInTheDocument();
    });
  });

  it('should render redemption type field when showSubscriptionOfferFields is true', async () => {
    const values = {
      discount_type: '',
      min_amount: 0,
      max_order_amount: 0,
      flat_cashback: 0,
      percent_rate: 0,
      max_cashback: 0,
      redemption_type: '',
      no_of_cycles: 0,
    };

    render(
      <DiscountType
        offerType={undefined}
        values={values}
        currencySymbol="₹"
        isFormLocked={false}
        hideDiscountType={false}
        showSubscriptionOfferFields={true}
        errors={{}}
        touched={{}}
      />,
    );

    await waitFor(() => {
      expect(screen.getByText('Redemption Type')).toBeInTheDocument();
    });
  });

  it('should render discount worth fields based on discount type', async () => {
    const values = {
      discount_type: 'FLAT',
      min_amount: 1000,
      max_order_amount: 5000,
      flat_cashback: 100,
      percent_rate: 0,
      max_cashback: 0,
      redemption_type: '',
      no_of_cycles: 0,
    };

    render(
      <DiscountType
        offerType={undefined}
        values={values}
        currencySymbol="₹"
        isFormLocked={false}
        hideDiscountType={false}
        showSubscriptionOfferFields={false}
        errors={{}}
        touched={{}}
      />,
    );

    await waitFor(() => {
      expect(screen.getByText('Minimum Order amount')).toBeInTheDocument();
    });
  });

  it('should validate flat cashback properly', () => {
    expect(validateFlatCashback('', 100)).toBe('Please fill out this field');
    expect(validateFlatCashback('abc', 100)).toBe('Please enter number upto 2 decimal points');
    expect(validateFlatCashback('10001', 100)).toBe(
      'Discount value cannot be greater than minimum amount',
    );
  });

  it('should validate max cashback properly', () => {
    expect(validateMaxCashback('')).toBe('Please fill out this field');
    expect(validateMaxCashback('abc')).toBe('Please enter number upto 2 decimal points');
    expect(validateMaxCashback(1000001)).toBe(false);
  });

  it('should validate decimal point value properly', () => {
    expect(validateDecimalPointValue('')).toBe('Please enter number upto 2 decimal points');
    expect(validateDecimalPointValue('abc')).toBe('Please enter number upto 2 decimal points');
    expect(validateDecimalPointValue('100.123')).toBe(`Please enter number upto 2 decimal points`);
  });

  it('should return error message if value is empty', () => {
    expect(validateDiscountType('')).toBe('Please select a discount type');
  });

  it('should return false if value is not empty', () => {
    expect(validateDiscountType('Flat')).toBe(false);
  });

  it('should return error message if value contains more than 2 decimal points', () => {
    expect(validateFlatCashback('12.345', 100)).toBe('Please enter number upto 2 decimal points');
  });

  it('should return error message if value is greater than max discount', () => {
    expect(validateFlatCashback('200', 100)).toBe(
      'Discount value cannot be greater than minimum amount',
    );
  });

  it('should return error message if value is greater than minimum amount', () => {
    expect(validateFlatCashback('150', 100)).toBe(
      'Discount value cannot be greater than minimum amount',
    );
  });

  it('should return false if value is valid', () => {
    expect(validateFlatCashback('50', 100)).toBe(false);
  });
});
