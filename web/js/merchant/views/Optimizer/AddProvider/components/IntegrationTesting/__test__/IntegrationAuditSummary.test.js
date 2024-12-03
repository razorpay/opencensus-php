import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { IntegrationAuditSummary } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/IntegrationAuditSummary';

import { RAZORPAY_COVERAGE, GATEWAY_COVERAGE } from './mocks';

describe('Optimizer IntegrationTesting IntegrationAuditSummary', () => {
  const mockProps = {
    currency: 'INR',
    amount: '1',
    gateway: 'payu',
    integrationType: 'instant',
    paymentError: '',
    isPaymentSuccessfull: true,
    isWebhookFailure: false,
    refundResult: { refund_success: true },
    razorpayCoverage: RAZORPAY_COVERAGE,
    gatewayCoverage: GATEWAY_COVERAGE,
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <IntegrationAuditSummary {...props} />
      </BladeProvider>
    );
  };

  it('should render IntegrationAuditSummary without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrowError();
  });

  it('should render the successfull payment and refund summary', () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Integration audit summary')).toBeInTheDocument();
    expect(screen.getByText('Payment testing')).toBeInTheDocument();
    expect(screen.getByText('Payment of ₹1 via UPI intent was')).toBeInTheDocument();
    expect(screen.getByText('successful')).toBeInTheDocument();
    expect(screen.getByText('Refund testing')).toBeInTheDocument();
    expect(screen.getByText('Refund of ₹1 via UPI intent was')).toBeInTheDocument();
    expect(screen.getByText('successfully')).toBeInTheDocument();
    expect(screen.getByText('initiated')).toBeInTheDocument();
  });

  it('should render the failed payment summary', () => {
    const props = {
      ...mockProps,
      paymentError: 'Error at gateway or bank level.',
      isPaymentSuccessfull: false,
    };
    render(<App {...props} />);
    expect(screen.getByText('Integration audit summary')).toBeInTheDocument();
    expect(screen.getByText('Payment testing')).toBeInTheDocument();
    expect(screen.getByText('Payment of ₹1 via UPI intent has')).toBeInTheDocument();
    expect(screen.getByText('failed')).toBeInTheDocument();
    expect(screen.getByText('Payment failed')).toBeInTheDocument();
    expect(screen.getByText('Error at gateway or bank level.')).toBeInTheDocument();
  });

  it('should render the webhook failed payment summary', () => {
    const props = {
      ...mockProps,
      isWebhookFailure: true,
      isPaymentSuccessfull: true,
    };
    render(<App {...props} />);
    expect(screen.getByText('Integration audit summary')).toBeInTheDocument();
    expect(screen.getByText('Payment testing')).toBeInTheDocument();
    expect(screen.getByText('Webhook issue detected')).toBeInTheDocument();
    expect(screen.getByText('Know more')).toBeInTheDocument();
  });

  it('should render the failed refund summary', () => {
    const props = {
      ...mockProps,
      refundResult: { refund_success: false },
    };
    render(<App {...props} />);
    expect(screen.getByText('Integration audit summary')).toBeInTheDocument();
    expect(screen.getByText('Refund testing')).toBeInTheDocument();
    expect(screen.getByText('Refund of ₹1 via UPI intent')).toBeInTheDocument();
    expect(
      screen.getByText(
        'We were unable to initiate a refund at this time. You can choose to take your integration live and process refunds from your payu dashboard.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Get help')).toBeInTheDocument();
  });

  it('should render the instrument coverage summary', async () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Instrument coverage')).toBeInTheDocument();
    expect(screen.queryAllByText('On PayU')).toHaveLength(5);
    expect(screen.getByText('Cards')).toBeInTheDocument();
    expect(screen.getAllByText('Visa Cards')).toHaveLength(2);
    expect(screen.getAllByText('Mastercard')).toHaveLength(2);
    expect(screen.getAllByText('Rupay Cards')).toHaveLength(2);

    expect(screen.getByText('UPI')).toBeInTheDocument();
    await userEvent.click(screen.getByText('UPI'));
    expect(screen.getByText('Intent')).toBeInTheDocument();
    expect(screen.getByText('Collect')).toBeInTheDocument();

    expect(screen.getByText('Netbanking')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Netbanking'));
    expect(screen.getByText('HDFC Bank')).toBeInTheDocument();
    expect(screen.getByText('State Bank of India')).toBeInTheDocument();
    expect(screen.getByText('ICICI Bank')).toBeInTheDocument();
    expect(screen.getByText('Punjab National Bank - Retail Banking')).toBeInTheDocument();

    expect(screen.getByText('Wallets')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Wallets'));
    expect(screen.getByText('PhonePe')).toBeInTheDocument();
    expect(screen.getByText('JioMoney')).toBeInTheDocument();
    expect(screen.getByText('OlaMoney')).toBeInTheDocument();

    expect(screen.getByText('Others')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Others'));
    expect(screen.getByText('EMI')).toBeInTheDocument();
    expect(screen.getByText('Pluxee')).toBeInTheDocument();
  });
});
