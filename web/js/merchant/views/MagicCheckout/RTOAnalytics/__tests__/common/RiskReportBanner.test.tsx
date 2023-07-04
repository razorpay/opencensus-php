import React from 'react';
import { render, screen } from 'test-utils';
import RiskReportBanner from 'merchant/views/MagicCheckout/RTOAnalytics/common/RiskReportBanner';

describe('testing risk report banner', () => {
  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: jest.fn(() => ({ success: jest.fn(), initiated: jest.fn() })),
    };
  });

  test('should render banner properly', () => {
    render(<RiskReportBanner />);
    expect(screen.getByText(/^Increase prepaid orders?/i)).toBeInTheDocument();
  });
});
