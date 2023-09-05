import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import { SUPPORTED_GATEWAYS } from 'merchant/views/Navigator/components/AddProvider/components/__test__/mocks/constants';
import SeamlessNote from 'merchant/views/Navigator/components/Provider/SeamlessNote';

describe('SeamlessNote component', () => {
  const mockProps = {
    seamlessDisabled: true,
    providers: SUPPORTED_GATEWAYS,
    selectedProvider: '',
    isEdit: true,
  };

  const renderComponent = (props) =>
    render(
      <Provider store={storeWithInitialState({})}>
        <BladeProvider themeTokens={paymentTheme}>
          <SeamlessNote {...props} />
        </BladeProvider>
      </Provider>,
    );

  it('should render SeamlessNote without any errors', () => {
    expect(() =>
      renderComponent({ ...mockProps, selectedProvider: 'cashfree' }),
    ).not.toThrowError();
  });

  it('should render instant option details', () => {
    renderComponent({ ...mockProps, selectedProvider: 'cashfree' });

    expect(screen.getByText('Enable Instant (beta)')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Go live with your Cashfree PG account instantly via ’Instant’ integration mode.',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'This is a beta release and supports the following payment methods - UPI and Netbanking.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Prerequisites:')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Please ensure that all necessary methods have been enabled on your Cashfree account (eg: UPI Intent)',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Notes:')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Card & Wallet Payments via Cashfree PG are currently not supported on Instant onboarding. Please setup a rule to route all Card & Wallet payments to Razorpay PG.',
      ),
    ).toBeInTheDocument();
  });

  it('should render server to server option details', () => {
    renderComponent({ ...mockProps, selectedProvider: 'cashfree', seamlessDisabled: false });

    expect(screen.getByText('Enable Server-to-Server')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your Cashfree account should have the seamless option enabled to use optimizer.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('How to enable seamless option on Cashfree?')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Write to your Cashfree relationship manager asking to enable seamless mode for your account. Mention that you are using Razorpay as the technology company to handle sensitive card data.',
      ),
    ).toBeInTheDocument();
  });
});
