import React from 'react';
import OrderAnalytics from 'merchant/views/MagicCheckout/OrderAnalytics/index';
import '@testing-library/jest-dom/extend-expect';
import 'react-dates/initialize';
import { render, screen, server } from 'test-utils';
import { Provider } from 'react-redux';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { storeWithInitialState } from 'merchant/store';
import { fetchAnalyticsData } from './mocks/handlers';
import { waitFor } from '@testing-library/react';

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={paymentTheme}>
        <OrderAnalytics {...props} />
      </BladeProvider>
    </Provider>
  );
};

describe('Magic - Order Analytics', () => {
  test('should render order analytics tab', async () => {
    // TODO move to common handlers and remove server.use later. Separate tests at widget level
    server.use(fetchAnalyticsData());
    render(<App />);
    await waitFor(() => {
      expect(
        screen.getByText('This data is only for razorpay magic processed orders'),
      ).toBeInTheDocument();
      expect(screen.getByText('Total Sales')).toBeInTheDocument();
      expect(screen.getByText('₹ 4.62L')).toBeInTheDocument();

      expect(screen.getByText('Total Orders Placed')).toBeInTheDocument();
      expect(screen.getByText('569')).toBeInTheDocument();

      expect(screen.getByText('Average Order Value')).toBeInTheDocument();
      expect(screen.getByText('₹ 811.70')).toBeInTheDocument();

      expect(screen.getByText('Prepaid vs COD - Total Sales')).toBeInTheDocument();
      expect(screen.getByText('Prepaid vs COD - Total Orders')).toBeInTheDocument();

      expect(screen.getByText('Traffic by UTM parameters')).toBeInTheDocument();
      expect(screen.getByText('WHATSAPP')).toBeInTheDocument();
      expect(screen.getByText('248')).toBeInTheDocument();
      expect(screen.getByText('₹ 1.82L')).toBeInTheDocument();

      expect(screen.getByText('Top Selling Product')).toBeInTheDocument();
      expect(screen.getByText('(A - GP) Pack of 4 Peanut Butters (800 g)')).toBeInTheDocument();
      expect(screen.getByText('82')).toBeInTheDocument();
      expect(screen.getByText('₹ 1.30L')).toBeInTheDocument();
    });
  });
});
