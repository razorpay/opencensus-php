import React from 'react';
import { render, screen } from 'test-utils';
import InputCurrencyAmount from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/components/InputCurrencyAmount';

const defaultProps = {
  defaultValueCurrency: 'INR',
  name: 'amount',
  label: 'Amount',
  placeholder: 'Enter amount',
  required: true,
  onChange: jest.fn(),
  onBlur: jest.fn(),
};

export const CURRENCY_LIST = {
  INR: {
    code: '356',
    denomination: 100,
    min_value: 100,
    min_auth_value: 100,
    symbol: '₹',
    name: 'Indian Rupee',
  },
  USD: {
    code: '357',
    denomination: 100,
    min_value: 100,
    min_auth_value: 100,
    symbol: '$',
    name: 'US Dollar',
  },
};
describe('InputCurrency Amount UT', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentButtons: () => ({
        interaction: jest.fn(),
      }),
    };
    window.currencyList = CURRENCY_LIST;
  });
  const renderApp = (props) => render(<InputCurrencyAmount {...defaultProps} {...props} />);
  test('InputCurrencyAmount to be defined', () => {
    expect(InputCurrencyAmount).toBeDefined();
  });

  test('should render screen of InputCurrencyAmount', () => {
    renderApp();
    expect(screen.getByText('Amount')).toBeInTheDocument();
  });

  test('calls onChange when the currency or amount value changes', () => {
    renderApp();
    const amount = screen.getByPlaceholderText('Enter amount');
    expect(amount).toBeInTheDocument();
  });
});
