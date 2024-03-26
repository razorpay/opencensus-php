import { fireEvent, render, screen, waitFor } from 'test-utils';
import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import ConversionRate from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/ConversionRate';
import {
  ORDER_ANALYTICS_WITH_ALL_3_CONVERSION_RATES,
  ORDER_ANALYTICS_WITH_ONLY_CONVERSION_RATE,
} from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/__tests__/mocks/fixtures';

jest.mock('merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext', () => ({
  useOrderAnalyticsContext: jest.fn(),
}));

describe('Conversion Rate', () => {
  test('should render conversion rate with only 1 dropdown option', async () => {
    useOrderAnalyticsContext.mockReturnValue(ORDER_ANALYTICS_WITH_ONLY_CONVERSION_RATE);
    render(<ConversionRate />);
    await waitFor(() => {
      expect(screen.getByText('Conversion Rate')).toBeInTheDocument();
      const dropdown = screen.queryByTestId('conversion-rate-dropdown');
      expect(dropdown).not.toBeInTheDocument();
    });
  });

  test('should renders conversion rate with all 3 dropdown options', async () => {
    useOrderAnalyticsContext.mockReturnValue(ORDER_ANALYTICS_WITH_ALL_3_CONVERSION_RATES);
    render(<ConversionRate />);
    await waitFor(() => {
      expect(screen.getByText('Conversion Rate')).toBeInTheDocument();
      const dropdown = screen.getByTestId('conversion-rate-dropdown');
      expect(screen.queryByText('Total')).toBeInTheDocument();
      const options = screen.getAllByRole('option');
      expect(options).toHaveLength(3);
      expect(options[0]).toHaveValue('RATE');
      expect(options[1]).toHaveValue('RATE_LOGGED_IN');
      expect(options[2]).toHaveValue('RATE_LOGGED_OUT');
      fireEvent.change(dropdown, {
        target: { value: 'RATE_LOGGED_IN' },
      });
      expect(dropdown).toHaveValue('RATE_LOGGED_IN');
      expect(dropdown).not.toHaveValue('RATE_LOGGED_OUT');
      expect(screen.queryByText('Logged In')).toBeInTheDocument();
      fireEvent.change(dropdown, {
        target: { value: 'RATE_LOGGED_OUT' },
      });
      expect(dropdown).toHaveValue('RATE_LOGGED_OUT');
      expect(dropdown).not.toHaveValue('RATE_LOGGED_IN');
      expect(screen.queryByText('Logged Out')).toBeInTheDocument();
    });
  });
});
