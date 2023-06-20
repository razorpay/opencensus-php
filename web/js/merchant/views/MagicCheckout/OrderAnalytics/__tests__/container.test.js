import React from 'react';
import OrderAnalytics from 'merchant/views/MagicCheckout/OrderAnalytics/index';
import '@testing-library/jest-dom/extend-expect';
import 'react-dates/initialize';
import { render, screen, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <OrderAnalytics {...props} />
    </Provider>
  );
};

describe('Magic - Order Analytics', () => {
  test('should render order analytics tab', async () => {
    render(<App />);
    await waitFor(() => {
      expect(
        screen.getByText('This data is only for Razorpay Magic processed orders'),
      ).toBeInTheDocument();
      expect(screen.getAllByText('Total Sales')[0]).toBeInTheDocument();
      expect(screen.getAllByText('₹ 4.62L')[0]).toBeInTheDocument();

      expect(screen.getByText('Total Orders Placed')).toBeInTheDocument();
      expect(screen.getAllByText('569')[0]).toBeInTheDocument();

      expect(screen.getAllByText('Average Order Value')[0]).toBeInTheDocument();
      expect(screen.getAllByText('₹ 811.70')[0]).toBeInTheDocument();

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
