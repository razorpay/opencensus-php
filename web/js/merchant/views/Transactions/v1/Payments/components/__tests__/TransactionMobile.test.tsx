import React from 'react';

import { screen, render, waitFor, userEvent } from 'common/services/test/test-utils';

import TransactionsMobile from 'merchant/views/Transactions/v1/Payments/components/TransactionMobile';

const App = (props) => <TransactionsMobile {...props} />;

const MOCK_TRANSACTIONS = [
  {
    id: 'pay_aabbdd',
    entity: 'payment',
    amount: 1625000,
    currency: 'INR',
    status: 'captured',
    method: 'upi',
    captured: true,
    acquirer_data: {
      rrn: '12345678',
      upi_transaction_id: '',
    },
    created_at: 1730271784,
    resourceUrl: 'payments',
    capturableAmount: 1625000,
  },
  {
    id: 'pay_aabbcc',
    entity: 'payment',
    amount: 1625000,
    currency: 'INR',
    status: 'captured',
    method: 'upi',
    captured: true,
    acquirer_data: {
      rrn: '12345678',
      upi_transaction_id: '',
    },
    created_at: 1730188201,
    resourceUrl: 'payments',
    capturableAmount: 1625000,
  },
  {
    id: 'pay_aabbcd',
    entity: 'payment',
    amount: 1625000,
    currency: 'INR',
    status: 'captured',
    method: 'upi',
    captured: true,
    acquirer_data: {
      rrn: '12345678',
      upi_transaction_id: '',
    },
    created_at: 1730188201,
    resourceUrl: 'payments',
    capturableAmount: 1625000,
  },
];
describe('TransactionsMobile', () => {
  test('Should render correctly', () => {
    render(<App items={MOCK_TRANSACTIONS} />, {});
    expect(screen.getByText(/Oct 30, 2024/i)).toBeInTheDocument();
  });

  test('Should render error message if transactions are empty', () => {
    render(<App items={[]} />, {});
    expect(
      screen.getByText(/No payments found for the selected duration and criteria!/i),
    ).toBeInTheDocument();
  });

  test('Should show loader if transactions are loading', () => {
    render(<App loading={true} items={[]} />, {});
    expect(screen.getByTestId('txn-spinner')).toBeInTheDocument();
  });

  test('Should render transactions with appropriate data', () => {
    render(<App items={MOCK_TRANSACTIONS} />, {});
    expect(screen.getAllByTestId('transactions-header').length).toBe(2);
    expect(screen.getAllByText(/Captured/i).length).toBe(3);
  });

  test('Should redirect to payment details page on row click', async () => {
    const navigationMock = jest.fn();
    const location = {
      ...window.location,
      pathname: '/payments',
      hash: '#somehash',
    };
    Object.defineProperty(window, 'location', {
      value: location,
    });
    render(<App items={MOCK_TRANSACTIONS} handleNavigate={navigationMock} />, {});
    const transactionsRow = screen.getAllByText(/Captured/i);
    userEvent.click(transactionsRow[0]);
    await waitFor(() => {
      expect(navigationMock).toHaveBeenCalledWith(
        '/payments/pay_aabbdd?init_page=Payments#somehash',
      );
    });
  });
});
