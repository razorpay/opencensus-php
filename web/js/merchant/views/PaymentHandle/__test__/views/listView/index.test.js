import '@testing-library/jest-dom/extend-expect';
import { App } from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/ListView';
import { handleInfo } from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/common';
import { fetchPaymentHandleSuccess } from 'merchant/views/PaymentHandle/__test__/mocks/handlers';
import { render, screen, server } from 'test-utils';

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            NcaPaymentFetch: {
              variables: {
                result: 'off',
              },
            },
          },
        }}
      />
    ),
}));

describe('Payment Handle List View', () => {
  const renderApp = (props = {}) =>
    render(<App {...props} />, {
      initialState: {
        session: {
          mode: 'live',
        },
        app: {
          isMobileResolution: false,
        },
        paymentHandle: {
          handleInfo: {
            data: props.data || handleInfo,
            error: null,
            loading: props.loading || false,
          },
        },
      },
      renderViaRouteGuard: false,
    });

  test('App component should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should have Banner title in the document', async () => {
    await server.use(fetchPaymentHandleSuccess());
    await renderApp();
    const bannerTitle = await screen.findByText(
      'Share your Razorpay.me link with customers as many times as you need to accept payments',
    );
    expect(bannerTitle).toBeInTheDocument();
  });
});
