import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';

import { PaymentMethodCoverage } from 'merchant/views/Optimizer/AddProvider/components/PaymentMethodCoverage';

describe('Add Provider > PaymentMethodCoverage', () => {
  const mockProps = {
    isFormEdit: false,
    selectedProvider: 'payu',
    methods: ['card', 'upi', 'netbanking'],
    gatewayCoverage: [
      {
        method: 'card',
        enabled: true,
      },
      {
        method: 'upi',
        enabled: false,
      },
      {
        method: 'netbanking',
        enabled: true,
      },
    ],
    razorpayCoverage: [
      {
        method: 'card',
        enabled: true,
      },
      {
        method: 'upi',
        enabled: true,
      },
      {
        method: 'netbanking',
        enabled: true,
      },
    ],
    businessName: 'Razorpay',
    isGatewayCoverageMissing: true,
    isRazorpayCoverageMissing: false,
    gatewayErrorMessage: null,
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
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
    const props = {
      ...mockProps,
      gatewayCoverage: [
        {
          method: 'card',
          enabled: true,
        },
        {
          method: 'upi',
          enabled: true,
        },
        {
          method: 'netbanking',
          enabled: true,
        },
      ],
      razorpayCoverage: [
        {
          method: 'card',
          enabled: true,
        },
        {
          method: 'upi',
          enabled: false,
        },
        {
          method: 'netbanking',
          enabled: true,
        },
      ],
      isGatewayCoverageMissing: false,
      isRazorpayCoverageMissing: true,
    };
    render(<App {...props} />);
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

  it('should render the gateway error message', () => {
    const props = {
      ...mockProps,
      gatewayErrorMessage: 'Gateway error message',
    };
    render(<App {...props} />);
    expect(screen.getByText('Gateway error message')).toBeInTheDocument();
  });
});
