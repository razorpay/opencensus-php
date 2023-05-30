import React from 'react';
import { render, screen } from 'test-utils';
import Form from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form';

const paymentButtonEntity = {
  currency: 'INR',
  settings: {
    template_type: null,
    payment_button_label: null,
    checkout_options: {
      email: 'email',
      phone: 'phone',
    },
    payment_button_theme: 'rzp-dark-standard',
    payment_button_text: 'Donate Now',
    payment_button_template_type: 'donation',
  },
  receipt: {
    enable_receipt: '1',
    selected_udf_field: '',
    enable_custom_serial_number: '0',
    enable_80g_details: '0',
  },
};

const props = {
  paymentButtonEntity,
  activeTabIndex: 0,
  onChangeActiveTabIndex: jest.fn(),
  submitPaymentButtonForm: jest.fn(),
};

jest.mock(
  'merchant/views/PaymentButton/PaymentButton/Create/components/Form/DonationAmountDetails',
  () => () => <div>Donation Amount Details</div>,
);

jest.mock(
  'merchant/views/PaymentButton/PaymentButton/Create/components/Form/CustomerDetails',
  () => () => <div>Customer Details</div>,
);

jest.mock(
  'merchant/views/PaymentButton/PaymentButton/Create/components/Form/ReviewAndCreate',
  () => () => <div>Review And Create</div>,
);

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

describe('Form - Unit test', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentButtons: () => ({
        interaction: jest.fn(),
      }),
    };
    window.currencyList = CURRENCY_LIST;
  });

  const renderApp = () => render(<Form {...props} />);

  test('form app component to be defined', () => {
    expect(Form).toBeDefined();
  });

  test('form app component should have necessary elements', () => {
    renderApp();
    expect(screen.getByText('Button Details')).toBeInTheDocument();
    expect(screen.getByText('Donations Button')).toBeInTheDocument();
    expect(screen.getByText('Button Type')).toBeInTheDocument();
  });
});
