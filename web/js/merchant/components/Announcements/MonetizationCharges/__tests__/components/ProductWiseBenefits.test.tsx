import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import ProductWiseBenefits from '../../components/ProductWiseBenefits';

describe('ProductWiseBenefits', () => {
  const defaultProps = {
    setShowProductWiseBenefits: jest.fn(),
    closeModal: jest.fn(),
    setShowCustomPricing: jest.fn(),
    screen: 'paymentLinks',
    bannerKey: 'monetizationCharges',
  };
  afterEach(() => {
    jest.clearAllMocks();
  });

  it('renders the correct benefits and heading', () => {
    render(<ProductWiseBenefits {...defaultProps} />);
    expect(screen.getByText('Product-wise Benefits')).toBeInTheDocument();
    expect(screen.getByText('Payment Links')).toBeInTheDocument();
    expect(screen.getByText('Create links to sell anywhere')).toBeInTheDocument();
  });

  it('calls setShowProductWiseBenefits and setShowCustomPricing on contactSales', async () => {
    jest.spyOn(require('@libs/shared-utils'), 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 's',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    render(<ProductWiseBenefits {...defaultProps} />);
    await userEvent.click(screen.getByText('Get Customised Pricing'));
    expect(defaultProps.setShowProductWiseBenefits).toHaveBeenCalledWith(false);
    expect(defaultProps.setShowCustomPricing).toHaveBeenCalledWith(true);
  });

  it('does not render contact sales text on non-mobile devices', () => {
    jest.spyOn(require('@libs/shared-utils'), 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 'l',
      isMobile: false,
      isDesktop: true,
      isLargeScreen: true,
    });
    render(<ProductWiseBenefits {...defaultProps} />);
    expect(screen.queryByText('Get Customised Pricing')).toBeNull();
  });
});
