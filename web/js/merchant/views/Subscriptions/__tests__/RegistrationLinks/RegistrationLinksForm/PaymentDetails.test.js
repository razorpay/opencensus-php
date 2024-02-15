import React from 'react';
import { screen, render, updateUseI18ServiceSpy } from 'test-utils';
import App from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/PaymentDetails';
import User from 'merchant/models/User';
import store from 'merchant/store';

const stateSpy = jest.spyOn(store, 'getState');

describe('RL - Payment Details Form', () => {
  const onBlurElement = jest.fn();
  const availableMethods = ['card', 'emandate', 'upi', 'nach'];
  const renderApp = (props) => {
    render(<App availableMethods={availableMethods} onBlurElement={onBlurElement} {...props} />);
  };

  test('Should render all Payment Details Fields', () => {
    renderApp();

    ['^Payment Method$', 'method to be used for registration link', 'Internal Notes'].forEach(
      (fieldLabel) => {
        expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
      },
    );
    expect(screen.getByRole('button', { name: /\+ add new/i })).toBeInTheDocument();
  });

  test('Should render all the Card Fields', () => {
    renderApp({ isCardPayment: true, mandateMethod: 'card' });

    ['^Card$', 'via credit and debit cards', 'authorisation amount'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(
      screen.getByRole('link', {
        name: /supported banks & networks/i,
      }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/minimum 1/i)).toBeInTheDocument();
  });

  test('Should render all the UPI Fields', () => {
    renderApp({ isUPIPayment: true, mandateMethod: 'upi' });

    ['^UPI$', 'via upi mandate', 'authorisation amount'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(
      screen.getByRole('link', {
        name: /supported banks & upi apps/i,
      }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/max 200000/i)).toBeInTheDocument();
  });

  test('Should render all the UPI TPV Form Fields', () => {
    renderApp({ isUPIPayment: true, mandateMethod: 'upi', isTPVEnabledMerchant: true });

    ['^UPI$', 'via upi mandate', 'authorisation amount'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(
      screen.getByRole('link', {
        name: /supported banks & upi apps/i,
      }),
    ).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/max 200000/i)).toBeInTheDocument();
    [
      '^bank details$',
      'ifsc on the bank account',
      'customer/beneficiary name on the account',
      'bank account number',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    ['ifsc', 'beneficiary name', 'account number'].forEach((fieldLabel) => {
      expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should render all the Emandate Fields', () => {
    renderApp({ isEmandatePayment: true, mandateMethod: 'emandate' });

    [
      '^Emandate$',
      'via netbanking and debit card on supported bank accounts',
      '^bank details$',
      'preferred bank for authentication',
      'ifsc on the bank account',
      'account details',
      'customer/beneficiary name on the account',
      'bank account number',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    ['bank name', 'ifsc', 'beneficiary name', 'account number', 'account type'].forEach(
      (fieldLabel) => {
        expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
      },
    );

    expect(
      screen.getByRole('link', {
        name: /supported banks & methods/i,
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('checkbox', {
        name: /skip bank details/i,
      }),
    ).toBeInTheDocument();
  });

  test('Should render all the Nach Fields', () => {
    renderApp({ isNACHPayment: true, mandateMethod: 'nach' });
    [
      '^NACH$',
      'via a nach form',
      '^bank details$',
      'ifsc on the bank account',
      'account details',
      'customer/beneficiary name on the account',
      'bank account number',
      'reference 1',
      'reference 2',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    ['ifsc', 'beneficiary name', 'account number', 'account type'].forEach((fieldLabel) => {
      expect(screen.getByPlaceholderText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('hide link if subscriptions.supported_bank_links config tag is enabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: new User(),
        org: {
          custom_code: 'curlec',
        },
      },
    });
    updateUseI18ServiceSpy('subscriptions.supported_bank_links');
    renderApp({
      isEmandatePayment: false,
      isCardPayment: false,
      isUPIPayment: false,
      isNACHPayment: false,
      mandateMethod: 'card',
    });

    expect(screen.queryByText(/supported banks & networks/i)).not.toBeInTheDocument();
  });
});
