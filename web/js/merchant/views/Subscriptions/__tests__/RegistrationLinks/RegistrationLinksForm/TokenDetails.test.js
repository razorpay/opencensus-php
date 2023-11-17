import React from 'react';

import { screen, render } from 'test-utils';
import App from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/TokenDetails';
import {
  CARD_TOKEN_MAX_AMOUNT,
  MY_CARD_MAX_AMOUNT,
  BILLING_FREQUENCY,
  PAYMENT_METHODS,
} from 'merchant/views/Subscriptions/constants';

describe('RL - Token Details Form', () => {
  const onBlurElement = jest.fn();
  const renderApp = (props) => {
    render(<App onBlurElement={onBlurElement} {...props} handleDateChange={() => {}} />);
  };

  test('Should render all the card token fields', () => {
    renderApp({
      method: PAYMENT_METHODS.CARD,
      amount: 20,
      user: { merchant: { currency: 'INR', country_code: 'IN' } },
      org: { custom_code: 'rzp' },
    });
    expect(
      screen.getByRole('checkbox', { name: /same as expiry of customer’s card/i }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/expiry \(dd-mm-yyyy\)/i)).toBeInTheDocument();

    ['expiry of token', 'maximum auto-debit amount', '(for domestic cards only)'].forEach(
      (fieldLabel) => {
        expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
      },
    );

    ['max 1000000'].forEach((fieldLabel) => {
      expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(screen.getByTestId('billing_frequency')).toHaveLength(4);
  });

  test('Should render all the upi token fields', () => {
    renderApp({
      method: PAYMENT_METHODS.UPI,
      amount: 200,
      mandateMaxAmount: 10001,
      user: { merchant: { currency: 'INR', country_code: 'IN' } },
      org: { custom_code: 'rzp' },
    });

    expect(screen.getAllByText(/expiry of token/i)[0]).toBeInTheDocument();
    expect(
      screen.getByRole('checkbox', {
        name: /until cancelled/i,
      }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/expiry \(dd-mm-yyyy\)/i)).toBeInTheDocument();

    [
      'billing frequency',
      'maximum billing amount',
      'this is the maximum you can charge the customer per billing cycle',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    ['max 2,00,000.00'].forEach((fieldLabel) => {
      expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(screen.getByTestId('billing_frequency')).toHaveLength(9);

    BILLING_FREQUENCY.forEach((frequency) => {
      expect(screen.getByText(frequency.label)).toBeInTheDOM();
    });
  });

  test('Should render all the Emandate token fields', () => {
    renderApp({
      method: PAYMENT_METHODS.EMANDATE,
      defaultMandateMaxAmount: 99999,
      defaultFirstChargeAmount: 0,
      amount: 20,
      user: { merchant: { currency: 'INR', country_code: 'IN' } },
      org: { custom_code: 'rzp' },
    });

    ['maximum billing amount', 'first charge amount', 'amount of first charge'].forEach(
      (fieldLabel) => {
        expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
      },
    );

    ['99999', '^0$'].forEach((fieldLabel) => {
      expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    expect(screen.getAllByText(/expiry of token/i)[0]).toBeInTheDocument();
    expect(screen.getByTestId('mandateExpireAt-date-input')).toBeInTheDocument();
    expect(
      screen.getAllByText(/Token expires in 30 years, unless otherwise specified./i)[0],
    ).toBeInTheDocument();
  });

  test('Should render all the nach token fields', () => {
    renderApp({
      method: PAYMENT_METHODS.NACH,
      defaultMandateMaxAmount: 99999,
      defaultFirstChargeAmount: 0,
      amount: 20,
      user: { merchant: { currency: 'INR', country_code: 'IN' } },
      org: { custom_code: 'rzp' },
    });

    ['maximum billing amount', 'first charge amount', 'amount of first charge'].forEach(
      (fieldLabel) => {
        expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
      },
    );

    ['99999', '^0$'].forEach((fieldLabel) => {
      expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(screen.getAllByText(/expiry of token/i)[0]).toBeInTheDocument();
    expect(screen.getByTestId('mandateExpireAt-date-input')).toBeInTheDocument();
    expect(
      screen.getAllByText(/Token expires in 30 years, unless otherwise specified./i)[0],
    ).toBeInTheDocument();
  });

  test('Should render card amount lesser than max allowed amount', () => {
    renderApp({
      method: PAYMENT_METHODS.CARD,
      amount: 201,
      mandateMaxAmount: 1000001,
      user: { merchant: { currency: 'INR', country_code: 'IN' } },
      org: { custom_code: 'rzp' },
    });
    expect(screen.getByText('Please enter an amount below')).toBeInTheDOM();
    expect(screen.getByTestId(`INR${CARD_TOKEN_MAX_AMOUNT}`)).toBeInTheDOM();
  });

  test('Should render card amount lesser than max allowed amount for Malaysia', () => {
    renderApp({
      method: PAYMENT_METHODS.CARD,
      amount: 201,
      mandateMaxAmount: 30001,
      user: { merchant: { currency: 'MYR', country_code: 'MY' } },
      org: { custom_code: 'curlec' },
    });
    expect(screen.getByText('Please enter an amount below')).toBeInTheDOM();
    expect(screen.getByTestId(`MYR${MY_CARD_MAX_AMOUNT}`)).toBeInTheDOM();
  });

  test('Should render frequency options for debit pattern enabled merchants', () => {
    renderApp({
      method: PAYMENT_METHODS.UPI,
      amount: 20,
      mandateMaxAmount: 200,
      user: { merchant: { currency: 'INR', country_code: 'IN' }, isDebitPatternEnabled: true },
      org: { custom_code: 'rzp' },
    });
    expect(screen.getByTestId('billing_frequency')).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /As and when presented/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Daily/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Weekly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Fortnightly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Monthly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Bimonthly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Quarterly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /^Half Yearly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /^Yearly/ })).toBeInTheDocument();
  });

  test('Should render frequency options for debit pattern disabled merchants', () => {
    renderApp({
      method: PAYMENT_METHODS.UPI,
      amount: 201,
      mandateMaxAmount: 10001,
      user: { merchant: { currency: 'INR', country_code: 'IN' }, isDebitPatternEnabled: false },
      org: { custom_code: 'rzp' },
    });
    expect(screen.getByTestId('billing_frequency')).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /As and when presented/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Daily/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Weekly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Fortnightly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Monthly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Bimonthly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /Quarterly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /^Half Yearly/ })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /^Yearly/ })).toBeInTheDocument();
  });
});
