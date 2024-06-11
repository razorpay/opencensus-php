import { screen } from 'test-utils';
import { renderApp } from 'merchant/views/Settings/PaymentMethods/components/__tests__/mocks/fixtures/Paypal';

describe('Paypal', () => {
  describe('When IE Revamp is enabled', () => {
    test('should render Paypal instructions', () => {
      renderApp();
      expect(
        screen.getByText('Accept International Payments using PayPal on Razorpay Checkout'),
      ).toBeInTheDocument();
      expect(screen.getByText('PayPal')).toBeInTheDocument();
    });

    test('should render Pending badge when terminal status is created', () => {
      renderApp({
        initialState: {
          config: {
            paypal_terminals: [
              {
                terminal: {
                  status: 'created',
                },
              },
            ],
          },
        },
      });
      expect(screen.getByText('Pending')).toBeInTheDocument();
    });

    test('should render Activated badge when terminal status is activated', () => {
      renderApp({
        initialState: {
          config: {
            paypal_terminals: [
              {
                terminal: {
                  status: 'activated',
                },
              },
            ],
          },
        },
      });
      expect(screen.getByText('Activated')).toBeInTheDocument();
    });
  });

  describe('When IE Revamp is disabled', () => {
    test('should render Paypal instructions', () => {
      renderApp({
        props: {
          isIERevamp: false,
        },
      });
      expect(
        screen.getByText('Accept International Payments using PayPal on Razorpay Checkout'),
      ).toBeInTheDocument();
    });

    test('should render pending badge when terminal status is created', () => {
      renderApp({
        props: {
          isIERevamp: false,
        },
        initialState: {
          config: {
            paypal_terminals: [
              {
                terminal: {
                  status: 'created',
                },
              },
            ],
          },
        },
      });
      expect(screen.getByText('pending')).toBeInTheDocument();
    });

    test('should render activated badge when terminal status is activated', () => {
      renderApp({
        props: {
          isIERevamp: false,
        },
        initialState: {
          config: {
            paypal_terminals: [
              {
                terminal: {
                  status: 'activated',
                },
              },
            ],
          },
        },
      });
      expect(screen.getByText('activated')).toBeInTheDocument();
    });
  });
});
