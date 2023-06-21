import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent, checkIfComponentIsEmpty } from 'test-utils';
import {
  App,
  defaultUpiPayment,
  upiTransferDetails,
} from 'merchant/views/Transactions/Payments/components/__tests__/mocks/fixtures/PaymentMethod';
import { titleCase } from 'common/utils/rzp-utils';

describe('PaymentMethod', () => {
  test('should not render payment method details when payment method is not present', () => {
    render(<App card={null} />);
    checkIfComponentIsEmpty();
  });

  test('should render payment method details when payment method is netbanking', () => {
    render(
      <App
        payment={{
          method: 'netbanking',
          bank: 'SBI',
        }}
      />,
    );
    expect(screen.getByText('SBI Netbanking')).toBeInTheDocument();
  });

  test('should render payment method details when payment method is wallet', () => {
    render(
      <App
        payment={{
          method: 'wallet',
          wallet: 'Paytm',
        }}
      />,
    );
    expect(screen.getByText('Paytm Wallet')).toBeInTheDocument();
  });

  test('should render payment method details when payment method is card', () => {
    const card = {
      type: 'debit',
      last4: 4354,
      issuer: 'issuer',
      network: 'network',
      name: 'Abc Xyz',
      id: '123asdewr3',
    };
    render(
      <App
        payment={{
          method: 'card',
        }}
        card={card}
      />,
    );
    fireEvent.click(screen.getByText('Domestic Debit Card'));
    expect(screen.getByText(`${card.issuer} ,${card.network} ending`)).toBeInTheDocument();
    expect(screen.getByText(card.last4)).toBeInTheDocument();
    expect(screen.getByText(`Name on card - ${card.name}`)).toBeInTheDocument();
    expect(screen.getByText(card.id)).toBeInTheDocument();
  });

  test('should not render payment method details when payment method is card & loading', () => {
    const card = {
      details: {},
      loading: true,
    };
    render(
      <App
        payment={{
          method: 'card',
        }}
        card={card}
      />,
    );
    expect(screen.queryByText('Domestic Debit Card')).not.toBeInTheDocument();
  });

  test('should render payment method details when payment method is emi', () => {
    const card = {
      type: 'debit',
      last4: 4354,
      network: 'network',
      name: 'Abc Xyz',
      id: '123asdewr3',
      international: true,
    };
    render(
      <App
        payment={{
          method: 'emi',
          emi_plan: { duration: 6, rate: 600 },
          amount: 20000,
        }}
        card={card}
      />,
    );
    fireEvent.click(screen.getByText('EMI on International Debit Card'));
    expect(screen.getByText('6 Months EMI at')).toBeInTheDocument();
    expect(screen.getByText('6%')).toBeInTheDocument();
  });

  test('should render payment method details when payment method is upi', () => {
    const upiTransfer = {
      ...upiTransferDetails,
    };

    const payment = {
      ...defaultUpiPayment,
      upi: {
        payer_account_type: 'bank_account',
      },
      upi_metadata: {
        flow: 'in_app',
      },
    };

    render(<App payment={payment} upiTransfer={upiTransfer} onUPIClick={jest.fn()} />);
    fireEvent.click(screen.getByText('UPI'));
    expect(screen.getByText(upiTransfer.details.virtual_account.description)).toBeInTheDocument();
    expect(screen.getByText(upiTransfer.details.virtual_account_id)).toBeInTheDocument();
    expect(screen.getByText('Payer UPI ID:')).toBeInTheDocument();
    expect(screen.getByText(upiTransfer.details.payer_vpa)).toBeInTheDocument();
    expect(screen.getByText('Paid from:')).toBeInTheDocument();
    expect(screen.getByText(titleCase(payment.upi.payer_account_type))).toBeInTheDocument();
    expect(screen.getByText('Flow:')).toBeInTheDocument();
    expect(screen.getByText('Turbo UPI')).toBeInTheDocument();
  });

  test('should not render paid from details when payer_account_type data not available', () => {
    const upiTransfer = {
      ...upiTransferDetails,
    };

    const payment = {
      ...defaultUpiPayment,
    };

    render(<App payment={payment} upiTransfer={upiTransfer} onUPIClick={jest.fn()} />);
    fireEvent.click(screen.getByText('UPI'));
    expect(screen.getByText('Payer UPI ID:')).toBeInTheDocument();
    expect(screen.getByText(upiTransfer.details.payer_vpa)).toBeInTheDocument();
    expect(screen.queryByText('Paid from:')).not.toBeInTheDocument();
  });

  test('should not render flow details when upi_metadata data not available', () => {
    const upiTransfer = {
      ...upiTransferDetails,
    };

    const payment = {
      ...defaultUpiPayment,
    };

    render(<App payment={payment} upiTransfer={upiTransfer} onUPIClick={jest.fn()} />);
    fireEvent.click(screen.getByText('UPI'));
    expect(screen.getByText('Payer UPI ID:')).toBeInTheDocument();
    expect(screen.getByText(upiTransfer.details.payer_vpa)).toBeInTheDocument();
    expect(screen.queryByText('Flow:')).not.toBeInTheDocument();
  });

  test('should not render payment method details when payment method is upi & loading', () => {
    const upiTransfer = {
      details: {},
      loading: true,
    };

    const payment = {
      ...defaultUpiPayment,
      upi: {
        payer_account_type: 'credit_card',
      },
      upi_metadata: {
        flow: 'in_app',
      },
    };
    render(<App payment={payment} upiTransfer={upiTransfer} onUPIClick={jest.fn()} />);
    fireEvent.click(screen.getByText('UPI'));
    expect(screen.queryByText('Payer UPI ID:')).not.toBeInTheDocument();
    expect(screen.queryByText('Paid from:')).not.toBeInTheDocument();
    expect(screen.queryByText(titleCase(payment.upi.payer_account_type))).not.toBeInTheDocument();
    expect(screen.queryByText('Flow:')).not.toBeInTheDocument();
    expect(screen.queryByText('Turbo UPI')).not.toBeInTheDocument();
  });

  test('should render payment vpa when payment method is upi & payer vpa is not available', () => {
    const upiTransfer = {
      details: {},
      loading: false,
    };
    const payment = {
      ...defaultUpiPayment,
      vpa: 'payment@vpa',
    };
    render(<App payment={payment} upiTransfer={upiTransfer} onUPIClick={jest.fn()} />);
    fireEvent.click(screen.getByText('UPI'));
    expect(screen.getByText(payment.vpa)).toBeInTheDocument();
  });

  test('should render payment method details when payment method is bank_transfer', () => {
    const bankTransfer = {
      details: {
        virtual_account: {
          description: 'Bank transfer description',
        },
        virtual_account_id: 'qwee23234vdsv',
        payer_bank_account: {
          name: 'payer name',
          account_number: '123687875',
          ifsc: 'ICIC0012456',
        },
      },
    };
    render(
      <App
        payment={{
          method: 'bank_transfer',
          amount: 20000,
        }}
        bankTransfer={bankTransfer}
      />,
    );
    fireEvent.click(screen.getByText('Bank Transfer'));
    expect(screen.getByText(bankTransfer.details.virtual_account.description)).toBeInTheDocument();
    expect(screen.getByText(bankTransfer.details.virtual_account_id)).toBeInTheDocument();
    expect(screen.getByText(bankTransfer.details.payer_bank_account.name)).toBeInTheDocument();
    expect(
      screen.getByText(bankTransfer.details.payer_bank_account.account_number),
    ).toBeInTheDocument();
    expect(screen.getByText(bankTransfer.details.payer_bank_account.ifsc)).toBeInTheDocument();
  });

  test('should not render payment method details when payment method is bank_transfer & loading', () => {
    const bankTransfer = {
      details: {},
      loading: true,
    };
    render(
      <App
        payment={{
          method: 'bank_transfer',
          amount: 20000,
        }}
        bankTransfer={bankTransfer}
      />,
    );
    fireEvent.click(screen.getByText('Bank Transfer'));
    expect(screen.queryByText('Payer Name:')).not.toBeInTheDocument();
  });

  test('should render payment method details when payment method is app', () => {
    render(
      <App
        payment={{
          method: 'app',
          amount: 20000,
        }}
      />,
    );
    expect(screen.getByText('Application')).toBeInTheDocument();
  });

  test('should render payment method details when payment method is cod', () => {
    render(
      <App
        payment={{
          method: 'cod',
          amount: 20000,
        }}
      />,
    );
    expect(screen.getByText('Cash on Delivery')).toBeInTheDocument();
  });

  test('should render payment method details when payment method is unselected', () => {
    render(
      <App
        payment={{
          method: 'unselected',
          amount: 20000,
        }}
      />,
    );
    expect(screen.getByText('Unselected')).toBeInTheDocument();
  });
});
