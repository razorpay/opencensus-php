import '@testing-library/jest-dom/extend-expect';
import { generateDynamicComponent } from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';
import * as EditColumnsModal from 'merchant/views/Transactions/model';
import {
  renderApp,
  defaultStore,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentsList';
import { screen, fireEvent, waitFor, errorHandlers, server } from 'test-utils';

jest.mock('merchant/components/Sidebar/helpers', () => ({
  ...jest.requireActual('merchant/components/Sidebar/helpers'),
  isJKOfflineMerchant: jest.fn(),
}));

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

  test('Should show new filters if JK Org and mobile view', async () => {
    isJKOfflineMerchant.mockReturnValue(true);
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
        app: {
          isMobileResolution: true,
        },
      },
    });

    const filterBtn = screen.getByRole('button', {
      name: /Filters/i,
    });
    expect(filterBtn).toBeInTheDocument();
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

  describe('Payments edit columns modal', () => {
    const fetchMerchantColumnPreferencesSpy = jest.spyOn(
      EditColumnsModal,
      'fetchMerchantColumnPreferences',
    );
    const fetchPaymentNotesKeysSpy = jest.spyOn(EditColumnsModal, 'fetchPaymentNotesKeys');

    test('Should fetch notes lists columns and preferences if custom tab view is enabled', async () => {
      await fetchPaymentsList({
        initialState: {
          ...defaultStore,
          session: {
            ...defaultStore.session,
            user: {
              isCustomTransactionTabView: true,
              ...defaultStore.session.user,
            },
          },
        },
      });
      expect(screen.getByText(/Edit Columns/)).toBeInTheDocument();
      expect(fetchMerchantColumnPreferencesSpy).toHaveBeenCalled();
      expect(fetchPaymentNotesKeysSpy).toHaveBeenCalled();
    });

    test('Should return a dynamic component object with the correct title and value function', () => {
      const columnName = 'domain';
      const dynamicComponent = generateDynamicComponent(columnName);
      expect(dynamicComponent.title).toBe(columnName);

      const item = {
        notes: {
          [columnName]: 'Example Note',
        },
      };

      const valueResult = dynamicComponent.value(item);
      expect(valueResult).toBe('Example Note');
    });

    test('Should return undefined if the item does not have notes for the specified column', () => {
      const columnName = 'domain';
      const dynamicComponent = generateDynamicComponent(columnName);

      const item = {
        notes: {
          notThatColumn: 'Another Note',
        },
      };

      const valueResult = dynamicComponent.value(item);
      expect(valueResult).toBeUndefined();
    });
  });
});
