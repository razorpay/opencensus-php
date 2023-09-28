import '@testing-library/jest-dom/extend-expect';
import { screen, fireEvent, waitFor, errorHandlers, server } from 'test-utils';
import { analyticsTrack } from 'common/utils/analytics';
import {
  renderApp,
  defaultStore,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentsList';

describe('PaymentsList', () => {
  const fetchPaymentsList = async (params) => {
    renderApp(params);
    await waitFor(() =>
      expect(analyticsTrack).toHaveBeenCalledWith({
        objectName: 'payments collection search',
        actionName: 'result',
        screen: 'PAYMENTS',
        toLumberjack: true,
        properties: {
          queryParams: '',
          success: true,
        },
      }),
    );
  };

  test('should fetch payment list', async () => {
    await fetchPaymentsList();
  });

  test('should fetch payment list when isRoute is true', async () => {
    await fetchPaymentsList({ props: { isRoute: true } });
  });

  test('should not show payment failure analysis when show failure analysis condition is not met', async () => {
    await fetchPaymentsList({
      initialState: {
        ...defaultStore,
        session: {
          ...defaultStore.session,
          user: {
            isFAEnabled: true,
            getMaxFAMtv: 200,
            findTag: () => false,
            isOrgAllowedFunctionality: () => true,
          },
        },
      },
    });
    expect(screen.queryByText('Payment Failure Analysis')).not.toBeInTheDocument();
  });

  describe('Analytics on payments search', () => {
    test('should call analytics event when payments search is done', async () => {
      await fetchPaymentsList();
      fireEvent.click(screen.getByText('Search Analytics'));
      await waitFor(() =>
        expect(analyticsTrack).toHaveBeenCalledWith({
          objectName: 'payments search',
          actionName: 'clicked',
          screen: 'transactions',
          properties: {
            location: 'payments',
            paymentId: undefined,
            paymentStatus: undefined,
          },
        }),
      );
    });
  });

  describe('Analytics on clear payments search', () => {
    test('should call analytics event when clear payments search is done', async () => {
      await fetchPaymentsList();
      fireEvent.click(screen.getByText('Clear Analytics'));
      await waitFor(() =>
        expect(window.rzpAnalytics).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Payments',
          eventAction: 'Clear Search Params - Payments',
        }),
      );
    });
  });

  describe('Analytics on submit payments search', () => {
    test('should call analytics event when submit payments search is done with success', async () => {
      await fetchPaymentsList();
      fireEvent.click(screen.getByText('Submit Search'));
      await waitFor(() =>
        expect(analyticsTrack).toHaveBeenCalledWith({
          objectName: 'payments search',
          actionName: 'result',
          screen: 'transactions',
          properties: {
            count: undefined,
            emailFilled: false,
            location: 'payments',
            notesFilled: false,
            paymentId: undefined,
            paymentStatus: undefined,
            resultsReturned: true,
            status: 'success',
          },
        }),
      );
    });

    test('should call analytics event when submit payments search is done with failure', async () => {
      await fetchPaymentsList();
      server.use(errorHandlers.internalServerError);
      fireEvent.click(screen.getByText('Submit Search'));
      await waitFor(() =>
        expect(analyticsTrack).toHaveBeenCalledWith({
          objectName: 'payments search',
          actionName: 'result',
          screen: 'transactions',
          properties: {
            emailFilled: false,
            location: 'payments',
            notesFilled: false,
            paymentId: undefined,
            paymentStatus: undefined,
            resultsReturned: false,
            status: 'failure',
          },
        }),
      );
    });
  });
});
