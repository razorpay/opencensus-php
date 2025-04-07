import React from 'react';
import { useParams } from 'react-router-dom';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';
import { render, screen } from 'test-utils';
import CouponPreview from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponPreview/CouponPreview';
import { mockWidgetsData } from 'merchant/views/MagicCheckout/CouponEngine/__test__/components/CouponPreview/constants';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: jest.fn(),
}));

describe('CouponPreview Component for amount_off_order coupon type', () => {
  beforeEach(() => {
    useParams.mockReturnValue({ couponName: 'amount_off_order' });
  });

  test('should render the coupon details', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            couponDetails: {
              display: true,
              couponDiscoveryEnabled: true,
              autoapply: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    expect(screen.getByText('Displayed to eligible users')).toBeInTheDocument();
    expect(
      screen.getByText('Displayed when conditions are not met as unavailable'),
    ).toBeInTheDocument();
    expect(screen.getByText('Auto-applied on checkout')).toBeInTheDocument();
  });

  test('should render the conditions details for min order value', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            discountDetails: {
              minimumValue: 500,
              minimumType: 'min_order_value',
              discountType: 'fixedAmount',
              discountValue: 100,
              maxDiscountValue: '',
              hasLimitedUseagePerOrder: true,
            },
            couponDetails: {
              description: '',
              code: '',
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that sections are rendered
    expect(screen.getByText('Minimum purchase of Rs. 500')).toBeInTheDocument();
  });

  test('should render the conditions details for min order qty', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            couponDetails: {
              description: '',
              code: '',
            },
            ...mockWidgetsData,
            discountDetails: {
              minimumValue: 10,
              minimumType: 'min_qty',
              discountType: 'fixedAmount',
              discountValue: 100,
              maxDiscountValue: '',
              hasLimitedUseagePerOrder: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that sections are rendered
    expect(screen.getByText('Mimimum purchase of 10 products')).toBeInTheDocument();
  });

  test('should render the discount details for fix amount ', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            discountDetails: {
              minimumValue: 500,
              minimumType: 'min_order_value',
              discountType: 'fixedAmount',
              discountValue: 100,
              maxDiscountValue: '',
              hasLimitedUseagePerOrder: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    expect(screen.queryByText('Rs. 100 off')).toBeInTheDocument();
  });

  test('should render the discount details for percentage discount ', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            discountDetails: {
              minimumValue: 500,
              minimumType: 'min_order_value',
              discountType: 'percentageDiscount',
              discountValue: 100,
              maxDiscountValue: '',
              hasLimitedUseagePerOrder: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Rs. 100% off')).not.toBeInTheDocument();
  });

  test('should render the discount details for fix percentage discount for max budgets ', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            discountDetails: {
              minimumValue: 500,
              minimumType: 'min_order_value',
              discountType: 'fixedAmount',
              discountValue: 400,
              maxDiscountValue: 300,
              hasLimitedUseagePerOrder: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('400 off upto Rs. 300')).not.toBeInTheDocument();
  });

  test('should render the eligibility details for all customers ', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            couponEligibility: {
              customerGroup: 'allCustomers',
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Applies for all customers')).toBeInTheDocument();
  });

  test('should render the eligibility details for specific customers ', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            couponEligibility: {
              customerGroup: 'specificCustomers',
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Applies for specific customers')).toBeInTheDocument();
  });

  test('should render the restrictions details for product coupon', () => {
    useParams.mockReturnValue({ couponName: 'amount_off_products' });

    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            discountDetails: {
              minimumValue: 500,
              minimumType: 'min_order_value',
              discountType: 'fixedAmount',
              discountValue: 100,
              maxDiscountValue: '',
              hasLimitedUseagePerOrder: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Applies once per order')).toBeInTheDocument();
  });

  test('should render the restrictions details per customer', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            usageRestriction: {
              isLimitedUsage: true,
              isRestrictedTotalUsage: false,
              maxUsage: 1,
              limitBy: 'phone',
              total: 1,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Applies 1 time per customer')).toBeInTheDocument();
  });

  test('should render the restrictions details in total', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            usageRestriction: {
              isLimitedUsage: false,
              isRestrictedTotalUsage: true,
              maxUsage: 1,
              limitBy: 'phone',
              total: 1,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Applies 1 time in total')).toBeInTheDocument();
  });

  test('should render the combinations details', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            combineCoupons: {
              shouldCombineFreeShippingCoupon: true,
              shouldCombineOtherAmountOffProductCoupons: true,
              shouldCombineAmountOffOrderCoupon: true,
              shouldCombineBulkDiscountCoupon: true,
              shouldCombineBxGyDiscountCoupon: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Combines with Free Shipping coupons')).toBeInTheDocument();
    expect(screen.queryByText('Combines with bulk coupons')).toBeInTheDocument();

    expect(screen.queryByText('Combines with BXGY coupons')).toBeInTheDocument();

    expect(screen.queryByText('Combines with other order off coupons')).toBeInTheDocument();
  });

  test('should render the validity details for coupons', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Starts 2025-01-28 4:46 am')).toBeInTheDocument();
    expect(screen.queryByText('Expires after Rs. 20 budget is reached')).toBeInTheDocument();
  });

  test('should render the validity details with expire', () => {
    render(
      <ModalContext.Provider
        value={{
          widgetsData: {
            ...mockWidgetsData,
            couponValidity: {
              startDate: '2025-01-28',
              endDate: '2025-01-29',
              startTime: '4:46 am',
              endTime: '11:59 pm',
              maxBudget: '20',
              isLimitedUsage: true,
            },
          },
        }}
      >
        <CouponPreview />
      </ModalContext.Provider>,
    );

    // Check that no sections are rendered
    expect(screen.queryByText('Starts 2025-01-28 4:46 am')).toBeInTheDocument();
    expect(screen.queryByText('Expires 2025-01-29 11:59 pm')).toBeInTheDocument();

    expect(screen.queryByText(`Doesn't Expire`)).not.toBeInTheDocument();

    expect(screen.queryByText('Expires after Rs. 20 budget is reached')).toBeInTheDocument();
  });
});
