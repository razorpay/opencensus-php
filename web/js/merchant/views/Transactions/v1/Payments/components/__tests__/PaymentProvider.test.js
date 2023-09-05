import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentProvider';
import { render, screen } from 'test-utils';

describe('PaymentProvider', () => {
  const defaultProps = {
    payment: {
      provider: 'provider name',
    },
  };

  const App = (props) => {
    return <PaymentProvider {...defaultProps} {...props} />;
  };

  test('should render payment provider', () => {
    render(<App />);
    expect(screen.getByText('provider name')).toBeInTheDocument();
  });

  test('should render payment provider when payment provider is cred', () => {
    render(
      <App
        payment={{
          provider: 'cred',
          acquirer_data: {
            amount: 123,
          },
        }}
      />,
    );
    expect(screen.getByText('CRED')).toBeInTheDocument();
  });

  test('should render payment provider when payment provider is google_pay', () => {
    render(
      <App
        payment={{
          provider: 'google_pay',
          acquirer_data: {
            amount: 123,
          },
        }}
      />,
    );
    expect(screen.getByText('Google Pay')).toBeInTheDocument();
  });
});
