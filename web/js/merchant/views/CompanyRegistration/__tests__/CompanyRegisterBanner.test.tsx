import React from 'react';
import { render, screen, fireEvent } from 'test-utils';

import CompanyRegisterBanner from '../components/CompanyRegisterBanner';
import { HEADER_BENEFITS_OFFER, RIZE_INCORPORATION } from '../constant';
import { trackStartRegistrationCtaClick } from '../analytics';

jest.mock('../utils', () => ({
  styleBasedOnDevice: jest.fn(() => ({})),
  parseBannerData: jest.fn(() => ({
    firstLine: 'Register your company',
    secondLineSubText: 'Easily with',
    highlightedText: 'Our Services',
    isButtonRequire: true,
    buttonText: 'Start Registration',
  })),
}));

jest.mock('../analytics', () => ({
  trackStartRegistrationCtaClick: jest.fn(),
}));

describe('CompanyRegisterBanner Component', () => {
  beforeAll(() => {
    // Mock window.open
    global.window.open = jest.fn();
  });
  test('renders the banner correctly', () => {
    render(<CompanyRegisterBanner isSmallDevice={false} />);

    expect(screen.getByText('Register your company')).toBeInTheDocument();
    expect(screen.getByText('Easily with')).toBeInTheDocument();
    expect(screen.getByText('Our Services')).toBeInTheDocument();
    expect(screen.getByText('Start Registration')).toBeInTheDocument();
  });

  test('renders correct number of benefits', () => {
    render(<CompanyRegisterBanner isSmallDevice={false} />);

    HEADER_BENEFITS_OFFER.forEach((benefit) => {
      expect(screen.getByText(benefit)).toBeInTheDocument();
    });
  });

  test('calls trackStartRegistrationCtaClick on button click', () => {
    render(<CompanyRegisterBanner isSmallDevice={false} />);

    const button = screen.getByText('Start Registration');
    fireEvent.click(button);

    expect(trackStartRegistrationCtaClick).toHaveBeenCalled();
  });

  test('opens the correct URL when button is clicked', () => {
    window.open = jest.fn();
    render(<CompanyRegisterBanner isSmallDevice={false} />);

    const button = screen.getByText('Start Registration');
    fireEvent.click(button);

    expect(window.open).toHaveBeenCalledWith(RIZE_INCORPORATION, '_blank', 'noopener');
  });
});
