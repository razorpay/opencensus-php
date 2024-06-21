import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen, act } from '@testing-library/react';

import * as allFetch from 'merchant/utils/ajax';
import { PaymentTesting } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/PaymentTesting';

describe('Optimizer IntegrationTesting PaymentTesting', () => {
  beforeEach(() => {
    jest
      .spyOn(allFetch, 'merchantFetch')
      .mockReturnValue(Promise.resolve({ success: true, isError: false, data: undefined }));
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const mockProps = {
    currency: 'INR',
    amount: '1',
    setAmount: jest.fn(),
    setMerchantKey: jest.fn(),
    isPaymentDone: false,
    paymentId: 'pay_NzdTMcDcAZsA9L',
    isPaymentSuccessfull: false,
    setIsPaymentSuccessfull: jest.fn(),
    isWebhookFailure: false,
    setIsWebhookFailure: jest.fn(),
    paymentError: '',
    setPaymentError: jest.fn(),
    gateway: 'payu',
    businessName: 'Razorpay',
    changeIntegrationTestingStep: jest.fn(),
    isPaymentDetailsFetched: false,
    setIsPaymentDetailsFetched: jest.fn(),
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <PaymentTesting {...props} />
      </BladeProvider>
    );
  };

  it('should render PaymentTesting without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrowError();
  });

  it('should render the correct elements', () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Payment testing')).toBeInTheDocument();
    expect(
      screen.getByText(
        'You would now be prompted to make a test transaction on the Razorpay Checkout, which would then be refunded to your account in the next step',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Payment type')).toBeInTheDocument();
    expect(screen.getByText('Payment method')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
  });

  it('should render the correct elements when payment is successfull', () => {
    const props = {
      ...mockProps,
      isPaymentDone: true,
      isPaymentSuccessfull: true,
    };
    render(<App {...props} />);
    expect(screen.getByText('Payment of ₹1 via UPI intent was')).toBeInTheDocument();
    expect(screen.getByText('successful')).toBeInTheDocument();
  });

  it('should render the correct elements when payment is not successfull', async () => {
    const props = {
      ...mockProps,
      isPaymentDone: true,
      isPaymentSuccessfull: false,
      isPaymentDetailsFetched: true,
    };
    await act(() => render(<App {...props} />));
    expect(screen.getByText('Payment of ₹1 via UPI intent has')).toBeInTheDocument();
    expect(screen.getByText('failed')).toBeInTheDocument();
  });

  it('should render the correct elements when payment is failed due to webhook issue', async () => {
    const props = {
      ...mockProps,
      isPaymentDone: true,
      isPaymentSuccessfull: true,
      isWebhookFailure: true,
    };
    await act(() => render(<App {...props} />));
    expect(screen.getByText('Webhook issue detected')).toBeInTheDocument();
    expect(screen.getByText('Know more')).toBeInTheDocument();
  });

  it('should render the amount error text when amount is not valid', async () => {
    const props = {
      ...mockProps,
      amount: '0',
    };
    await act(() => render(<App {...props} />));
    expect(screen.getByText('Amount should be greater or equal than 1')).toBeInTheDocument();
  });
});
