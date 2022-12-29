import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import {
  generateUser,
  PaymentDetailsApp,
  paymentLinkConfig,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/Details';
import { render, screen } from 'test-utils';

describe('Payment Link Details', () => {
  beforeAll(() => {
    window.rzp_user = {};
    window.hj = jest.fn();
    window.rzpAnalytics = jest.fn();
    window.rzpAnalytics = jest.fn();

    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('component PaymentDetailsApp should be defined', () => {
    expect(PaymentDetailsApp).toBeDefined();
  });

  test('should render View Payment Details in the document', () => {
    const data = {
      partial_payment: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    render(
      <PaymentDetailsApp
        user={generateUser()}
        isPaymentlinksV2Enabled
        paymentlink={getPaymentLinkConfig}
      />,
      {
        initialState: {
          session: {
            user: {
              isPaymentlinksV2Enabled: true,
            },
          },
        },
      },
    );
    expect(screen.getByText('View Payment Details')).toBeInTheDocument();
  });

  test('should not render PaymentDetailsApp component when partial_payment is false', () => {
    const data = {
      partial_payment: false,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    render(
      <PaymentDetailsApp
        user={generateUser()}
        isPaymentlinksV2Enabled={false}
        paymentlink={getPaymentLinkConfig}
      />,
    );
    expect(screen.queryByText('View Payment Details')).not.toBeInTheDocument();
  });
});
