import React from 'react';

import { render, screen, userEvent } from 'test-utils';
import Reports from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/components/index';

const renderReports = (newProps = {}) => {
  render(
    <Reports
      reportsTimeRange={{
        start: 1709231400,
        end: 1711688600,
      }}
      setReportsTimeRange={jest.fn()}
      showNotification={jest.fn()}
      dashboardView="rcod"
      {...newProps}
    />,
  );
};

describe('Magic - Order Analytics Reports', () => {
  test('should display loader when button is clicked', () => {
    renderReports();
    const orderReportsBtn = screen.queryByText(/Order Reports/i);
    const checkoutReportsBtn = screen.queryByText(/Checkout Reports/i);
    expect(orderReportsBtn).toBeInTheDocument();
    expect(checkoutReportsBtn).toBeInTheDocument();

    userEvent.click(orderReportsBtn);
    screen.findByText(/Generating Order Reports/i);
  });
});
