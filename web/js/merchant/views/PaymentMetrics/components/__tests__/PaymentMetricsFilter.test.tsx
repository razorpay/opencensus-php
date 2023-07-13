import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import PaymentMetricsFilter from 'merchant/views/PaymentMetrics/components/PaymentMetricsFilter';
import { initialFilters } from 'merchant/views/PaymentMetrics/helpers';

describe('CR Comparison', () => {
  test('should render PaymentMetricsFilter', () => {
    const dateRange = initialFilters();
    const props = {
      ...dateRange,
      updateDateRange: () => {},
      updateInterval: () => {},
    };
    render(<PaymentMetricsFilter {...props} />);
    expect(screen.queryByText('Last 6 Hours')).toBeInTheDocument();
    expect(screen.queryByText('Apply')).toBeInTheDocument();
    expect(screen.queryByText('to')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Last 6 Hours'));
    expect(screen.queryByText('Last 24 Hours')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Last 24 Hours'));
    expect(screen.queryByText('Last 6 Hours')).not.toBeInTheDocument();
  });

  test('Assert Preset value change from dropdown and calender', () => {
    const dateRange = initialFilters();
    const props = {
      ...dateRange,
      updateDateRange: () => {},
      updateInterval: () => {},
    };
    const { container } = render(<PaymentMetricsFilter {...props} />);
    fireEvent.click(screen.getByText('Last 6 Hours'));
    expect(screen.queryByText('Last 24 Hours')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Last 24 Hours'));
    expect(screen.queryByText('Last 6 Hours')).not.toBeInTheDocument();
    fireEvent.click(container.getElementsByClassName('form-control')[0]);
    const oldDays = container.getElementsByClassName('rdtOld');
    if (oldDays.length > 0) {
      fireEvent.click(container.getElementsByClassName('rdtOld')[0]);
    }
  });

  test('Assert apply and clear function', () => {
    const dateRange = initialFilters();
    const props = {
      ...dateRange,
      updateDateRange: () => {},
      updateInterval: () => {},
    };
    render(<PaymentMetricsFilter {...props} />);
    fireEvent.click(screen.getByText('Last 6 Hours'));
    expect(screen.queryByText('Last 24 Hours')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Clear'));
    expect(screen.queryByText('Last 24 Hours')).not.toBeInTheDocument();
    fireEvent.click(screen.getByText('Apply'));
    fireEvent.click(screen.getByText('Last 6 Hours'));
    fireEvent.click(screen.getByText('Custom Range'));
  });
});
