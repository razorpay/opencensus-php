import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { fetchPaymentHandle, createPaymentHandle } from 'merchant/reducers/paymentHandle';
import {
  App,
  paymentHandle,
  MockFetchErrorResponse,
} from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/PaymentHandle';

jest.mock('merchant/reducers/paymentHandle', () => ({
  fetchPaymentHandle: jest.fn(),
  createPaymentHandle: jest.fn(),
}));

describe('Payment Handle', () => {
  beforeAll(() => {
    window.rzp_user = {};
    window.rzpQ = {
      component: jest.fn(),
    };
  });

  const renderApp = (props = {}) => {
    return render(<App {...props} />, {
      initialState: {
        session: {
          user: {
            isPaymentHandleEnabled: props.isPaymentHandleEnabled || false,
            isPaymentHandleSplitzEnabled: true,
            isAllowedView: () => true,
            findTag: () => false,
          },
        },
        paymentHandle,
      },
    });
  };

  test('Payment Handle App to be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render loading screen', () => {
    renderApp();
    expect(
      screen.getAllByText('Something went wrong, Our team will get back to you shortly.')[0],
    ).toBeInTheDocument();
  });

  test('should load onboarding screen as payment handle does not exist initially', async () => {
    fetchPaymentHandle.mockReturnValue({
      type: 'FETCH_PAYMENT_HANDLE::ERROR',
      payload: Promise.reject(MockFetchErrorResponse),
    });
    createPaymentHandle.mockReturnValue({
      type: 'FETCH_PAYMENT_HANDLE::SUCCESS',
      payload: Promise.resolve({
        status_code: 200,
        data: {
          title: 'Demo',
          slug: '@deepnewbusiness',
          url: 'https://razorpay.me/@deepnewbusiness',
          id: 'pl_JneRfB2WsBgkr2',
        },
        success: true,
      }),
    });
    renderApp();
    const getStartedCTA = screen.getByRole('button', { name: 'Get Started' });
    expect(getStartedCTA).toBeInTheDocument();
    await userEvent.click(getStartedCTA);
    const props = {
      isPaymentHandleEnabled: true,
    };
    renderApp(props);
    expect(screen.getAllByText('Payment List')[0]).toBeInTheDocument();
  });
});
