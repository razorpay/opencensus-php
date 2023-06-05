import React from 'react';
import { screen, render } from 'test-utils';
import App from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/TokenDetails';
import { CARD_TOKEN_MAX_AMOUNT, MY_CARD_MAX_AMOUNT } from 'merchant/views/Subscriptions/constants';

describe('RL - Token Details Form', () => {
  const onBlurElement = jest.fn();
  const renderApp = (props) => {
    render(<App onBlurElement={onBlurElement} {...props} handleDateChange={() => {}} />);
  };

  test('Should render all the card token fields', () => {
    renderApp({
      isCardPayment: true,
      mandateMethod: 'card',
      amount: 20,
      user: { merchant: { currency: 'INR', country_code: 'IN' } },
      org: { custom_code: 'rzp' },
    });
    expect(
      screen.getByRole('checkbox', { name: /same as expiry of customer’s card/i }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/expiry \(dd-mm-yyyy\)/i)).toBeInTheDocument();

    [
      'expiry of token',
      'maximum auto-debit amount',
      '(for domestic cards only)',
      'you can charge the customer upto ₹15000 for each recurring payment. payments above ₹15000 will ask for otp verification from the customer.',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    ['max 15000'].forEach((fieldLabel) => {
      expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should render all the upi token fields', () => {
    renderApp({
      isUPIPayment: true,
      mandateMethod: 'upi',
      amount: 20,
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
      'monthly you can charge the customer once in a month',
      'as and when presented you can charge the customer any time',
    ].forEach((fieldLabel) => {
      expect(screen.getByRole('radio', { name: new RegExp(fieldLabel, 'i') })).toBeInTheDocument();
    });

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
  });

  test('Should render all the Emandate token fields', () => {
    renderApp({
      isEmandatePayment: true,
      mandateMethod: 'emandate',
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
    expect(
      screen.getByRole('checkbox', {
        name: /until cancelled/i,
      }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/expiry \(dd-mm-yyyy\)/i)).toBeInTheDocument();
  });

  test('Should render all the nach token fields', () => {
    renderApp({
      isNACHPayment: true,
      mandateMethod: 'nach',
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
    expect(
      screen.getByRole('checkbox', {
        name: /until cancelled/i,
      }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/expiry \(dd-mm-yyyy\)/i)).toBeInTheDocument();
  });

  test('Should render card amount lesser than max allowed amount', () => {
    renderApp({
      isCardPayment: true,
      mandateMethod: 'card',
      amount: 201,
      mandateMaxAmount: 1000001,
      user: { merchant: { currency: 'INR', country_code: 'IN' } },
      org: { custom_code: 'rzp' },
    });
    expect(
      screen.getByText(`Please enter an amount below ₹${CARD_TOKEN_MAX_AMOUNT}`),
    ).toBeInTheDocument();
  });

  test('Should render card amount lesser than max allowed amount for Malaysia', () => {
    renderApp({
      isCardPayment: true,
      mandateMethod: 'card',
      amount: 201,
      mandateMaxAmount: 30001,
      user: { merchant: { currency: 'MYR', country_code: 'MY' } },
      org: { custom_code: 'curlec' },
    });
    expect(
      screen.getByText(`Please enter an amount below RM${MY_CARD_MAX_AMOUNT}`),
    ).toBeInTheDocument();
  });
});
