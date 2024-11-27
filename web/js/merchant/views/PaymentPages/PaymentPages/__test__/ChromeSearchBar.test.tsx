import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import ChromeSearchBar from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/ChromeSearchBar';

describe('ChromeSearchBar', () => {
  test('renders correctly in desktop preview mode', () => {
    render(<ChromeSearchBar isDesktopPreview={true} isMobile={false} />);
    expect(screen.getByText('https://pages.razorpay.com/')).toBeInTheDocument();
  });

  test('renders correctly in mobile mode', () => {
    render(<ChromeSearchBar isDesktopPreview={false} isMobile={true} />);
    expect(screen.getByText('https://pages.razorpay.com/')).toBeInTheDocument();
    expect(screen.getByText('C')).toBeInTheDocument();
  });
});
