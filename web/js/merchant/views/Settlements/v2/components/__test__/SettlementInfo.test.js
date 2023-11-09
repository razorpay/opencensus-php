import { waitForLoadingToFinish, waitFor, server, screen } from 'test-utils';
import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';
import * as analytics from 'merchant/views/Settlements/Settlements/analytics';
import {
  fetchBankSettlementStatusHandler,
  fetchIsAdminAsMerchantHandler,
  fetchSettlementErrorHandler,
} from 'merchant/views/Settlements/v2/components/__test__/mocks/handlers';
import {
  renderApp,
  enableCustomSettlementsState,
} from 'merchant/views/Settlements/v2/components/__test__/mocks/fixtures/SettlementInfo';
const settlementInfo = SettlementsDB.settlementsInfo;

describe('SettlementInfo', () => {
  const handleAnalyticsSpy = jest.spyOn(analytics, 'handleAnalytics');

  test('should show spinner on mount', () => {
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  beforeEach(() => {
    handleAnalyticsSpy.mockClear();
  });

  const renderShowCustomSettlements = async (bankSettlementResponse, isAdminMerchantResponse) => {
    server.use(fetchBankSettlementStatusHandler(bankSettlementResponse));
    server.use(fetchIsAdminAsMerchantHandler(isAdminMerchantResponse));
    renderApp({ initialState: enableCustomSettlementsState });

    await waitFor(() => {
      // custom settlements spinner
      expect(screen.queryByTestId('loader-dots')).toBeInTheDocument();
    });

    await waitFor(() => {
      expect(screen.queryByTestId('loader-dots')).not.toBeInTheDocument();
    });
  };

  test('should load settlements info', async () => {
    renderApp();
    await waitForLoadingToFinish();

    expect(screen.getByText(/Status/i)).toBeInTheDocument();
    expect(screen.getByTestId('status-label')).toHaveTextContent(settlementInfo.status);
    expect(screen.getByText(/Created At/i)).toBeInTheDocument();
    expect(screen.getByTestId('settlement-created-time')).toHaveTextContent(
      settlementInfo.created_at,
    );
    const amountElements = screen.getAllByTestId('settlement-amount');
    expect(screen.getByText(/Fees/i)).toBeInTheDocument();
    expect(amountElements[0]).toHaveTextContent(settlementInfo.fees);

    expect(screen.getByText(/Fees/i)).toBeInTheDocument();
    expect(amountElements[0]).toHaveTextContent(settlementInfo.fees);

    expect(screen.getByText('UTR')).toBeInTheDocument();
    expect(screen.getByText(settlementInfo.utr)).toBeInTheDocument();
    // Custom settlement details should not be shown
    expect(screen.queryByText(/Final Settlement Reference no/i)).not.toBeInTheDocument();
  });

  test('should call analytics after settlement info is fetched', async () => {
    renderApp();
    await waitForLoadingToFinish();
    expect(handleAnalyticsSpy).toHaveBeenCalled();
    expect(handleAnalyticsSpy).toHaveBeenCalledWith(
      'settlement details fetched',
      'status',
      { ...analytics.propertiesPayload('settlement', settlementInfo), status: 'success' },
      'settlement details',
    );
  });

  test('should show final settlement and bank settlement status when its custom settlement', async () => {
    await renderShowCustomSettlements();

    expect(screen.getByText(/Final Settlement Reference no/i)).toBeInTheDocument();
    expect(screen.getByText(settlementInfo.id)).toBeInTheDocument();
    expect(screen.getByText(/Bank Settlement Status/i)).toBeInTheDocument();
    expect(screen.getByText('SETTLED')).toBeInTheDocument();
  });

  test('should show PaymentOptimizerProvider if user has necessary permissions', async () => {
    renderApp({
      initialState: {
        session: {
          user: {
            isSingleReconEnabled: true,
            isOptimizerEnabled: true,
            merchant: { currency: 'INR' },
          },
        },
      },
    });
    await waitForLoadingToFinish();
    expect(screen.getByText(/Payment Provider/i)).toBeInTheDocument();
    expect(screen.getByTestId('payment-optimizer-provider')).toBeInTheDocument();
  });

  test('should show notification on fetch settlement info error', async () => {
    server.use(fetchSettlementErrorHandler());

    renderApp();
    await waitForLoadingToFinish();

    expect(screen.queryByText(/Status/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Created At/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Fees/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Tax/i)).not.toBeInTheDocument();

    expect(screen.getByText('failed while fetching settlement')).toBeInTheDocument();
  });

  test('should show notification on get custom settlements info error', async () => {
    await renderShowCustomSettlements({ success: false });
    expect(screen.getByText('Something went wrong, please try again later')).toBeInTheDocument();
  });

  test("should hide UTR when admin merchant doesn't give expected response", async () => {
    await renderShowCustomSettlements(false, { success: true, data: {} });
    expect(screen.queryByText('UTR')).not.toBeInTheDocument();
  });

  test('should show empty when bank settlement status is empty', async () => {
    await renderShowCustomSettlements({ success: true, data: {} }, { success: true, data: {} });
    expect(screen.getByText('Bank Settlement Status')).toBeInTheDocument();
    expect(screen.getByTestId('status-label')).toBeEmptyDOMElement();
  });

  test('should render MYR currency for Curlec orgs', async () => {
    renderApp({ props: { currency: 'MYR' } });
    await waitForLoadingToFinish();
    const settlementAmount = screen.getAllByTestId('settlement-amount');

    settlementAmount.forEach((element) => {
      expect(element).toHaveTextContent('MYR');
    });
  });
});
