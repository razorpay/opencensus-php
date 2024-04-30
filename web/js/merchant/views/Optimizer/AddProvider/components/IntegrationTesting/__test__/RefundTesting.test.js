import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';

import { RefundTesting } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/RefundTesting';

describe('Optimizer IntegrationTesting RefundTesting', () => {
  const mockProps = {
    gateway: 'payu',
    payments: [
      {
        id: 'txn_1234567890',
        settled_by: 'payu',
        method: 'upi',
        amount: '100',
        currency: 'INR',
      },
      {
        id: 'txn_1234567893',
        settled_by: 'paytm',
        method: 'upi',
        amount: '5000',
        currency: 'INR',
      },
    ],
    setPayments: jest.fn(),
    isPaymentsTableLoading: false,
    initiateRefund: jest.fn(),
    isRefundDetialsFetched: false,
    setIsRefundDetialsFetched: jest.fn(),
    refundResult: {},
    setRefundResult: jest.fn(),
    integrationType: 'instant',
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <RefundTesting {...props} />
      </BladeProvider>
    );
  };

  it('should render RefundTesting without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrow();
  });

  it('should render the correct elements', () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Refund testing')).toBeInTheDocument();
    expect(
      screen.getByText(
        `Please select the transaction(s) for which you'd want to initiate refund(s) for`,
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Transaction ID')).toBeInTheDocument();
    expect(screen.getByText('Gateway')).toBeInTheDocument();
    expect(screen.getByText('Method')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('txn_1234567890')).toBeInTheDocument();
    expect(screen.getByText('payu')).toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: 'Initiate refund' })).toHaveLength(2);
  });

  it('should render the success refund initiated badge', () => {
    const props = {
      ...mockProps,
      payments: [
        {
          id: 'txn_1234567890',
          settled_by: 'payu',
          method: 'upi',
          amount: '100',
          currency: 'INR',
          refund_success: true,
        },
        {
          id: 'txn_1234567893',
          settled_by: 'paytm',
          method: 'upi',
          amount: '5000',
          currency: 'INR',
        },
      ],
    };
    render(<App {...props} />);
    expect(screen.getAllByRole('button', { name: 'Initiate refund' })).toHaveLength(1);
    expect(screen.getAllByText('Initiated')).toHaveLength(1);
    expect(screen.queryByText('Refund Failed')).not.toBeInTheDocument();
  });

  it('should render the alert on failed refund for payu', () => {
    const props = {
      ...mockProps,
      payments: [
        {
          id: 'txn_1234567890',
          settled_by: 'payu',
          method: 'upi',
          amount: '100',
          currency: 'INR',
          refund_success: false,
        },
        {
          id: 'txn_1234567893',
          settled_by: 'paytm',
          method: 'upi',
          amount: '5000',
          currency: 'INR',
        },
      ],
      refundResult: {
        refund_success: false,
      },
    };
    render(<App {...props} />);
    expect(screen.getAllByRole('button', { name: 'Initiate refund' })).toHaveLength(1);
    expect(screen.getByText('Refund failed')).toBeInTheDocument();
    expect(
      screen.getByText(
        'We were unable to initiate a refund at this time. You can choose to take your integration live and process refunds from your payu dashboard.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Get help')).toBeInTheDocument();
  });

  it('should render the alert on failed refund for paytm', () => {
    const props = {
      ...mockProps,
      gateway: 'paytm',
      payments: [
        {
          id: 'txn_1234567890',
          settled_by: 'payu',
          method: 'upi',
          amount: '100',
          currency: 'INR',
        },
        {
          id: 'txn_1234567893',
          settled_by: 'paytm',
          method: 'upi',
          amount: '5000',
          currency: 'INR',
          refund_success: false,
        },
      ],
      refundResult: {
        refund_success: false,
      },
    };
    render(<App {...props} />);
    expect(screen.getAllByRole('button', { name: 'Initiate refund' })).toHaveLength(1);
    expect(screen.getByText('Refund failed')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Refunds are currently disabled on your Paytm account. Please reach out to the Paytm support team to enable refunds via API for your Paytm account.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Know more')).toBeInTheDocument();
  });
});
