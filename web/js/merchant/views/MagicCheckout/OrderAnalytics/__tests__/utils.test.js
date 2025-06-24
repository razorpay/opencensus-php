import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor } from 'test-utils';
import { customTooltip } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import { CHART_ELEM, CHART_OPTIONS } from './mocks/fixtures';

describe('Magic - Order Analytics utils', () => {
  test('should add tooltip component to the DOM', async () => {
    render(<div className="chart-item" />);
    customTooltip(CHART_OPTIONS, CHART_ELEM, 'total_sales');
    await waitFor(() => {
      expect(screen.queryByText('Total Sales')).toBeInTheDocument();
      expect(screen.queryByText('₹78.06k')).toBeInTheDocument();
    });
  });
});
