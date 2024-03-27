import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';

import { PaymentMethodCoverage } from '../PaymentMethodCoverage';

describe('Add Provider > PaymentMethodCoverage', () => {
  const mockProps = {
    methods: ['card', 'upi', 'netbanking'],
    isEdit: false,
    isFormEdit: false,
    selectedProvider: 'payu',
    mandatoryMethods: ['upi'],
    gatewayCoverage: {
      card: {
        supported: true,
      },
      upi: {
        supported: false,
      },
      netbanking: {
        supported: true,
      },
    },
    razorpayCoverage: {
      card: {
        supported: true,
      },
      upi: {
        supported: false,
      },
      netbanking: {
        supported: true,
      },
    },
    businessName: 'Razorpay',
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={paymentTheme}>
        <PaymentMethodCoverage {...props} />
      </BladeProvider>
    );
  };

  it('should render PaymentMethodCoverage without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrowError();
  });

  it('should render the correct number of methods', () => {
    render(<App {...mockProps} />);
    expect(screen.getAllByText('Card')).toHaveLength(2);
    expect(screen.getAllByText('Upi')).toHaveLength(2);
    expect(screen.getAllByText('Netbanking')).toHaveLength(2);
  });

  it('should render the error message when mandatory methods are not covered for razorpay', () => {
    render(<App {...mockProps} />);
    expect(
      screen.getByText(
        'One or more payment methods are not supported. Please ensure that all necessary methods are enabled on your Razorpay account.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Go to Account & Settings' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Test integration' })).toBeDisabled();
  });

  it('should render the error message when mandatory methods are not covered for gateway', () => {
    render(<App {...mockProps} />);
    expect(
      screen.getByText(
        'One or more payment methods are not supported. Please reach out to your payu account manager or payu support team for help.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Test integration' })).toBeDisabled();
  });
});
