import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentPageDetails from 'merchant/views/Transactions/v1/Payments/components/PaymentPageDetails';
import { render, screen, waitFor, delay, checkIfComponentIsEmpty } from 'test-utils';

describe('PaymentPageDetails', () => {
  const defaultProps = {
    payment: {
      order_id: '456',
    },
  };

  const App = (props) => {
    return <PaymentPageDetails {...defaultProps} {...props} />;
  };
  test('should render payment page details when present', async () => {
    render(<App />);
    await waitFor(() => {
      expect(screen.getByText('Payment page title')).toBeInTheDocument();
      expect(screen.getByText('Payment page ID')).toBeInTheDocument();
    });
  });

  test('should not render payment page details when not present', async () => {
    render(
      <App
        payment={{
          order_id: '123',
        }}
      />,
    );
    // wait for the API call to be completed, can't use waitFor here as initially DOM is empty only
    await delay();
    checkIfComponentIsEmpty();
  });
});
