import React from 'react';
import CustomPricing from '../../components/CustomPricing';
import { userEvent, render, screen } from 'test-utils';
import * as posHooks from 'merchant/views/POS/hooks';
import User from 'merchant/models/User';

const closeModal = jest.fn();
const setShowCustomPricing = jest.fn();

const mockUser = new User({
  merchants: {
    pricing_plan_id: 'pricingPlanId1',
    org: {},
  },
  current: 'test',
  contact_name: 'john Doe',
  email: 'jane.doe@razorpay.com',
});

describe('CustomPricing', () => {
  beforeAll(() => {
    jest.clearAllMocks();
  });

  it('should render CustomPricing in desktop', async () => {
    jest.spyOn(posHooks, 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 'l',
      isMobile: false,
      isDesktop: true,
      isLargeScreen: true,
    });
    render(
      <CustomPricing
        setShowCustomPricing={setShowCustomPricing}
        closeModal={closeModal}
        screen={'paymentLinks'}
        bannerKey={'monetizationCharges'}
      />,
    );
    const pricingTitle = screen.getByText('Get Customised Pricing');
    expect(pricingTitle).toBeInTheDocument();
    const customPricingSubtitle = screen.getByText(
      'Monthly revenue over ₹5 lakh? Submit your details and we’ll contact you',
    );
    expect(customPricingSubtitle).toBeInTheDocument();
  });

  it('should render CustomPricing in mobile', async () => {
    jest.spyOn(posHooks, 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 's',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    render(
      <CustomPricing
        setShowCustomPricing={setShowCustomPricing}
        closeModal={closeModal}
        screen={'paymentLinks'}
        bannerKey={'monetizationCharges'}
      />,
    );
    const pricingTitle = screen.getByText('Get Customised Pricing');
    expect(pricingTitle).toBeInTheDocument();
    const customPricingSubtitle = screen.getByText(
      'Monthly revenue over ₹5 lakh? Submit your details and we’ll contact you',
    );
    expect(customPricingSubtitle).toBeInTheDocument();
  });

  it('calls setShowCustomPricing with false when the back icon is clicked', async () => {
    jest.spyOn(posHooks, 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 'l',
      isMobile: false,
      isDesktop: true,
      isLargeScreen: true,
    });
    render(
      <CustomPricing
        setShowCustomPricing={setShowCustomPricing}
        closeModal={closeModal}
        screen={'paymentLinks'}
        bannerKey={'monetizationCharges'}
      />,
    );
    const backButton = screen.getByTestId('custom-pricing-back-button');
    await userEvent.click(backButton);
    expect(setShowCustomPricing).toHaveBeenCalledWith(false);
  });
});
