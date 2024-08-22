import React from 'react';
import { render, screen } from 'test-utils';
import MonetizationChargesModalDesktop from '../../components/MonetizationChargesModalDesktop';

describe('MonetizationChargesModalDesktop', () => {
  const defaultProps = {
    isOpen: true,
    setIsOpen: jest.fn(),
    showProductWiseBenefits: false,
    setShowProductWiseBenefits: jest.fn(),
    showCustomPricing: false,
    setShowCustomPricing: jest.fn(),
    screen: 'paymentLinks',
    bannerKey: 'monetizationCharges',
  };
  afterEach(() => {
    jest.clearAllMocks();
  });

  it('renders MonetizationChargesDetails by default', () => {
    render(<MonetizationChargesModalDesktop {...defaultProps} />);
    expect(screen.getByText('Pricing for Links, Pages, Invoices')).toBeInTheDocument();
    expect(screen.getByText('What you’ll get')).toBeInTheDocument();
  });

  it('renders ProductWiseBenefits when showProductWiseBenefits is true', () => {
    const props = { ...defaultProps, showProductWiseBenefits: true };
    render(<MonetizationChargesModalDesktop {...props} />);
    expect(screen.getByText('Product-wise Benefits')).toBeInTheDocument();
    expect(screen.getByText('Payment Links')).toBeInTheDocument();
    expect(screen.getByText('Create links to sell anywhere')).toBeInTheDocument();
  });

  it('renders CustomPricing when showCustomPricing is true', () => {
    const props = { ...defaultProps, showCustomPricing: true };
    render(<MonetizationChargesModalDesktop {...props} />);
    expect(screen.getByText('Get Customised Pricing')).toBeInTheDocument();
    expect(
      screen.getByText('Monthly revenue over ₹5 lakh? Submit your details and we’ll contact you'),
    ).toBeInTheDocument();
  });
});
