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
    // Check if title, description are rendered
    expect(screen.getByText('Legal Protection')).toBeInTheDocument();
    expect(screen.getByText('Keep your personal assets safe if the business faces issues.')).toBeInTheDocument();
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
