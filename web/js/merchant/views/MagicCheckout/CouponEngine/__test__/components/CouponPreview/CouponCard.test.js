import { render, screen, waitFor } from 'test-utils';
import React from 'react';
import CouponCard from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponPreview/CouponCard';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

jest.mock('assets/coupons/coupon-icon.svg', () => 'mock-icon');

test('should render Card component with coupon details from Redux state', async () => {
  const mockWidgetsData = {
    couponDetails: {
      code: 'TESTCOUPON',
      description: 'Test coupon description',
    },
  };

  render(
    <ModalContext.Provider value={{ widgetsData: mockWidgetsData }}>
      <CouponCard />
    </ModalContext.Provider>,
  );

  await waitFor(() => {
    // Check for the presence of coupon icon
    expect(screen.getByAltText('coupon icon')).toBeInTheDocument();

    // Check for the coupon description
    expect(screen.getByText('Test coupon description')).toBeInTheDocument();

    // Check for the coupon code
    expect(screen.getByText('TESTCOUPON')).toBeInTheDocument();

    // Check for T&C text
    expect(screen.getByText('T&C Applicable • T&Cs')).toBeInTheDocument();
  });
});

test('should render default values if coupon details are empty in Redux state', async () => {
  const mockWidgetsData = {
    couponDetails: {
      code: '',
      description: '',
    },
  };

  render(
    <ModalContext.Provider value={{ widgetsData: mockWidgetsData }}>
      <CouponCard />
    </ModalContext.Provider>,
  );

  await waitFor(() => {
    // Check for default coupon description
    expect(screen.getByText('Coupon Description')).toBeInTheDocument();

    // Check for default coupon code
    expect(screen.getAllByText('Coupon Code').length).toBe(1);

    // Check for T&C text
    expect(screen.getByText('T&C Applicable • T&Cs')).toBeInTheDocument();
  });
});
