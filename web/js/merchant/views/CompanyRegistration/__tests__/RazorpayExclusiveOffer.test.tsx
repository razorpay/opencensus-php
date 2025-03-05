import React from 'react';
import { render, screen, fireEvent } from 'test-utils';

import RazorpayExclusiveOffer from '../components/RazorpayExclusiveOffer';
import { EXCLUSIVE_OFFER_HEADER } from '../constant';

describe('Test RazorpayExclusiveOffer Component', () => {
  test('Heading text render correctly', () => {
    render(<RazorpayExclusiveOffer isSmallDevice />);
    const headerText = screen.getByText(EXCLUSIVE_OFFER_HEADER);
    expect(headerText).toBeInTheDocument();
  });

  test('renders offer cards correctly', () => {
    render(<RazorpayExclusiveOffer isSmallDevice />);
    // Check if title, description, and label are rendered
    expect(screen.getByText('3 months free')).toBeInTheDocument();
    expect(screen.getByText('Business Banking+')).toBeInTheDocument();
    expect(screen.getByText('Visit Razorpay X')).toBeInTheDocument();
  });

  test('opens the correct URL when clicking on the link', () => {
    render(<RazorpayExclusiveOffer isSmallDevice={false} />);

    const link = screen.getByText('Visit Razorpay X');
    window.open = jest.fn(); // Mock window.open

    fireEvent.click(link);
    expect(window.open).toHaveBeenCalledWith(
      'https://razorpay.com/x/?utm_source=direct&utm_medium=rize_razorpay_dashboard',
      '_blank',
      'noopener',
    );
  });

  test('renders different layout for small devices', () => {
    render(<RazorpayExclusiveOffer isSmallDevice />);

    const box = screen.getByTestId('offers-box');
    expect(box).toHaveStyle('flex-direction: column');
  });

  test('renders different layout for larger devices', () => {
    render(<RazorpayExclusiveOffer isSmallDevice={false} />);

    const box = screen.getByTestId('offers-box');
    expect(box).toHaveStyle('flex-direction: row');
  });
});
