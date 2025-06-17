import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import MonetizationChargesDetails from '../../components/MonetizationChargesDetails';

let mockClosemodal = jest.fn();
let mockSetShowCustomPricing = jest.fn();
let mockSetShowProductWiseBenefits = jest.fn();

describe('MonetizationChargesDetails', () => {
  jest.spyOn(require('@libs/shared-utils'), 'useBladeBreakpoints').mockReturnValue({
    matchedBreakpoint: 'l',
    isMobile: false,
    isDesktop: true,
    isLargeScreen: true,
  });
  const defaultProps = {
    setShowCustomPricing: mockSetShowCustomPricing,
    setShowProductWiseBenefits: mockSetShowProductWiseBenefits,
    closeModal: mockClosemodal,
    screen: 'paymentLinks',
    bannerKey: 'monetizationCharges',
  };
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders correctly', () => {
    render(<MonetizationChargesDetails {...defaultProps} />);
    expect(screen.getByText('Pricing for Links, Pages, Invoices')).toBeInTheDocument();
    expect(screen.getByText('What you’ll get')).toBeInTheDocument();
    expect(screen.getByText('Get Customised Pricing')).toBeInTheDocument();
    expect(screen.getByText('Okay, got it')).toBeInTheDocument();
  });

  it('renders no code apps and benefits', () => {
    render(<MonetizationChargesDetails {...defaultProps} />);
    expect(screen.getByText('What you’ll get')).toBeInTheDocument();
    expect(
      screen.getByText('Accept payments without any development or coding'),
    ).toBeInTheDocument();
  });

  it('calls setShowProductWiseBenefits when "View product-wise benefits" is clicked', async () => {
    render(<MonetizationChargesDetails {...defaultProps} />);
    await userEvent.click(screen.getByText('View product-wise benefits'));
    expect(mockSetShowProductWiseBenefits).toHaveBeenCalledWith(true);
  });
});
