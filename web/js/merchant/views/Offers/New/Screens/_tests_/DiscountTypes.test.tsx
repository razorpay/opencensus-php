import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import DiscountType from '../DiscountTypes';
const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('DiscountTypes form', () => {
  it('should render instant discount text', async () => {
    const defaultFormData = {
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
        formData={defaultFormData}
        currencySymbol="₹"
        isFormLocked={false}
        hideDiscountType={false}
        showSubscriptionOfferFields={false}
      />,
    );
    await waitFor(() => {
      expect(screen.getByText('Discount Type')).toBeInTheDocument();
    });
  });

  it('should render redemption type field when showSubscriptionOfferFields is true', async () => {
    const defaultFormData = {
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
        formData={defaultFormData}
        currencySymbol="₹"
        isFormLocked={false}
        hideDiscountType={false}
        showSubscriptionOfferFields={true}
      />,
    );

    await waitFor(() => {
      expect(screen.getByText('Redemption Type')).toBeInTheDocument();
    });
  });

  it('should render discount worth fields based on discount type', async () => {
    const flatDiscountFormData = {
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
        formData={flatDiscountFormData}
        currencySymbol="₹"
        isFormLocked={false}
        hideDiscountType={false}
        showSubscriptionOfferFields={false}
      />,
    );

    await waitFor(() => {
      expect(screen.getByText('Minimum Order amount')).toBeInTheDocument();
    });
  });
});
