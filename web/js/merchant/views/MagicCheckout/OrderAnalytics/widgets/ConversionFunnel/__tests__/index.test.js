import React from 'react';
import { fireEvent, render, screen, waitFor } from 'test-utils';
import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import ConversionFunnel from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/ConversionFunnel';
import {
  LOADING_ORDER_ANALYTICS,
  ORDER_ANALYTICS_WITH_ALL_3_CONVERSION_FUNNELS,
  ORDER_ANALYTICS_WITH_ONLY_CONVERSION_FUNNEL,
} from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/ConversionFunnel/__tests__/mocks/fixtures';

jest.mock('merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext', () => ({
  useOrderAnalyticsContext: jest.fn(),
}));

describe('Conversion Funnel', () => {
  test('should show spinner when loading', () => {
    useOrderAnalyticsContext.mockReturnValue(LOADING_ORDER_ANALYTICS);
    render(<ConversionFunnel />);
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should render conversion funnel with only 1 dropdown option', async () => {
    useOrderAnalyticsContext.mockReturnValue(ORDER_ANALYTICS_WITH_ONLY_CONVERSION_FUNNEL);
    render(<ConversionFunnel />);

    await waitFor(() => {
      expect(screen.getByText('Conversion Funnel')).toBeInTheDocument();
      const dropdown = screen.queryByTestId('conversion-funnel-dropdown');
      expect(dropdown).not.toBeInTheDocument();
    });
  });

  test('should render conversion funnel with all 3 dropdown options', async () => {
    useOrderAnalyticsContext.mockReturnValue(ORDER_ANALYTICS_WITH_ALL_3_CONVERSION_FUNNELS);
    render(<ConversionFunnel />);

    await waitFor(() => {
      expect(screen.getByText('Conversion Funnel')).toBeInTheDocument();
      const dropdown = screen.getByTestId('conversion-funnel-dropdown');
      const options = screen.getAllByRole('option');
      expect(options).toHaveLength(3);
      expect(options[0]).toHaveValue('FUNNEL');
      expect(options[1]).toHaveValue('FUNNEL_LOGGED_IN');
      expect(options[2]).toHaveValue('FUNNEL_LOGGED_OUT');
      expect(screen.queryByText('Total')).toBeInTheDocument();
      fireEvent.change(dropdown, {
        target: { value: 'FUNNEL_LOGGED_IN' },
      });
      expect(dropdown).toHaveValue('FUNNEL_LOGGED_IN');
      expect(dropdown).not.toHaveValue('FUNNEL_LOGGED_OUT');
      expect(screen.queryByText('Logged In')).toBeInTheDocument();
      fireEvent.change(dropdown, {
        target: { value: 'FUNNEL_LOGGED_OUT' },
      });
      expect(dropdown).toHaveValue('FUNNEL_LOGGED_OUT');
      expect(dropdown).not.toHaveValue('FUNNEL_LOGGED_IN');
      expect(screen.queryByText('Logged Out')).toBeInTheDocument();
    });
  });
});
