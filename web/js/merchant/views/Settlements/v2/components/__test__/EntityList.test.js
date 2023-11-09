import EntityList from 'merchant/views/Settlements/v2/components/EntityList';
import {
  settlementsListData,
  settlementTabBreakupDetails,
  settlementsListRefundData,
} from 'merchant/views/Settlements/__test__/data/SettlementsDB';
import { render, screen, waitFor, waitForLoadingToFinish, server, userEvent } from 'test-utils';
import { sanitizeTabName } from 'merchant/views/Settlements/v2/util';
import {
  transactionDetailsSuccessHandler,
  transactionSourceDetailsErrorHandler,
} from 'merchant/views/Settlements/v2/components/__test__/mocks/fixtures/handlers';
import * as analytics from 'merchant/views/Settlements/Settlements/analytics';

const defaultProps = {
  breakupDetails: settlementTabBreakupDetails,
  settlementId: 'test-settlement-id',
  activeTab: settlementTabBreakupDetails.items[0].component,
  user: {
    merchant: {
      currency: 'INR',
    },
  },
};

jest.mock('merchant/components/StatusLabel', () => ({
  ...jest.requireActual('merchant/components/StatusLabel'),
  __esModule: true,
  PaymentStatusLabel: () => <div data-testid="payment-status-label" />,
  RefundStatusLabel: () => <div data-testid="refund-status-label" />,
  DisputeStatusLabel: () => <div data-testid="dispute-status-label" />,
}));

jest.mock('merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider', () => ({
  __esModule: true,
  default: () => <div data-testid="payment-optimizer-provider" />,
}));

const optimizerProviderItem = {
  component: 'payment_domestic',
  amount: 34647940,
  count: 204,
  type: 'credit',
  fee: 678291,
  tax: 122088,
  optimizer_provider: 'Razorpay',
  id: 'test',
};

const settledByItem = {
  component: 'payment_domestic',
  amount: 34647940,
  count: 204,
  type: 'credit',
  fee: 678291,
  tax: 122088,
  settled_by: 'Razorpay',
  id: 'test',
};

const App = (props) => <EntityList {...defaultProps} {...props} />;

const renderApp = ({ props, initialState } = {}) => {
  const renderObj = render(<App {...props} />, { initialState });
  return renderObj;
};

describe('EntityList', () => {
  const handleAnalyticsMock = jest.spyOn(analytics, 'handleAnalytics');

  afterEach(async () => {
    // this is to make sure all state updates are handled before the component unmounts
    await waitFor(() => {
      expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
    });
    handleAnalyticsMock.mockClear();
  });

  const getEntityRows = () => screen.queryAllByTestId(new RegExp(/entity-item-row-\w+/));

  const getSearchInput = (activeTab = defaultProps.activeTab) =>
    screen.getByRole('textbox', {
      name: new RegExp(activeTab, 'i'),
    });

  const clickSubmit = async () => {
    const submitButton = screen.getByRole('button', { name: /search/i });
    await userEvent.click(submitButton);
  };

  test('should show activeTab text and spinner initially', () => {
    renderApp();
    // tab name is sanitized in ComponentListFilter component - uses adjustment
    expect(
      screen.getByText(new RegExp(sanitizeTabName(defaultProps.activeTab), 'i')),
    ).toBeInTheDocument();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should render settlements list', async () => {
    renderApp();
    await waitForLoadingToFinish();
    const itemsList = getEntityRows();
    expect(itemsList).toHaveLength(settlementsListData.length);
  });

  test("should hide spinner and settlements table when there's an error", async () => {
    server.use(transactionSourceDetailsErrorHandler());
    renderApp();
    await waitForLoadingToFinish();
    expect(screen.getByText('failed to load transaction source details')).toBeInTheDocument();
    expect(getEntityRows()).toHaveLength(0);
  });

  test('should go to next page and prev page on clicking next, prev', async () => {
    renderApp();
    await waitForLoadingToFinish();
    const nextButton = screen.getByRole('button', { name: /next/i });
    await userEvent.click(nextButton);
    // 2nd page has 5 items
    await waitFor(() => expect(getEntityRows()).toHaveLength(5));
    const prevButton = screen.getByRole('button', { name: /prev/i });
    await userEvent.click(prevButton);
    await waitFor(() => expect(getEntityRows()).toHaveLength(10));
  });

  test('should render empty column headers when list data is empty', async () => {
    server.use(transactionDetailsSuccessHandler([]));
    const { container } = renderApp();
    await waitForLoadingToFinish();
    await waitFor(() => expect(getEntityRows()).toHaveLength(0));
    // for column headers
    expect(container.querySelector('thead tr').children).toHaveLength(0);
  });

  test('should search for transactions with searchId and count on clicking submit', async () => {
    renderApp();
    await waitForLoadingToFinish();

    const searchField = getSearchInput();
    expect(searchField).toBeInTheDocument();
    const searchTerm = settlementsListData[0].id;
    await userEvent.type(searchField, searchTerm);
    await clickSubmit();
    expect(handleAnalyticsMock).toHaveBeenCalled();
    expect(handleAnalyticsMock).toHaveBeenCalledWith(
      'settlement details search',
      'clicked',
      { searchTerm, totalNoOfPayments: undefined },
      'settlement details',
    );
    await waitForLoadingToFinish();
    await waitFor(() => expect(getEntityRows()).toHaveLength(1));
  });

  test('should show error notification if search fails', async () => {
    renderApp();
    await waitForLoadingToFinish();
    await userEvent.type(getSearchInput(), 'error-input');
    await clickSubmit();
    await waitForLoadingToFinish();
    await waitFor(() =>
      expect(screen.getByText('failed to load transaction source details')).toBeInTheDocument(),
    );
  });

  // TODO: fix this test, it's flaky
  test.skip('should clear input and reset to defaults on clicking clear', async () => {
    renderApp();
    await waitForLoadingToFinish();
    await userEvent.type(getSearchInput(), 'no-results');
    await clickSubmit();
    await waitFor(() => expect(getEntityRows()).toHaveLength(0));

    const clearButton = screen.getByText(/clear/i);
    await userEvent.click(clearButton);
    expect(getSearchInput()).toHaveValue('');
    await waitForLoadingToFinish();
    await waitFor(() => expect(getEntityRows()).toHaveLength(10));
  });

  test("should fetch default page one results on clicking submit when there's no search id", async () => {
    renderApp();
    await waitForLoadingToFinish();
    const nextButton = screen.getByRole('button', { name: /next/i });
    await userEvent.click(nextButton);
    // as second page has only 5 items
    await waitFor(() => expect(getEntityRows()).toHaveLength(5));
    await clickSubmit();
    await waitFor(() => expect(getEntityRows()).toHaveLength(10));
  });

  describe('Entity List Item', () => {
    test('should render refund list data', async () => {
      renderApp({ props: { activeTab: 'refund' } });
      await waitForLoadingToFinish();
      await waitFor(() => expect(screen.getAllByTestId('refund-status-label')).toHaveLength(1));
      const refundId = settlementsListRefundData[0].id;
      const idLink = screen.getByRole('link', { name: refundId });
      expect(idLink).toBeInTheDocument();
      expect(idLink).toHaveAttribute(
        'href',
        `/refunds/${refundId}?init_point=refunds-table&init_page=Settlements.Refunds`,
      );
      await userEvent.click(idLink);
    });

    test('should render payment list data', async () => {
      renderApp({ props: { activeTab: 'payment' } });
      await waitForLoadingToFinish();
      await waitFor(() => expect(screen.getAllByTestId('payment-status-label')).toHaveLength(10));
      const paymentId = settlementsListData[0].id;
      const idLink = screen.getByRole('link', { name: paymentId });
      expect(idLink).toBeInTheDocument();
      expect(idLink).toHaveAttribute(
        'href',
        `/payments/${paymentId}?init_point=payments-table&init_page=Settlements.Payments`,
      );
      await userEvent.click(idLink);
      expect(handleAnalyticsMock).toHaveBeenCalled();
      expect(handleAnalyticsMock).toHaveBeenCalledWith(
        'settlement details payment id',
        'clicked',
        analytics.propertiesPayload('payment', settlementsListData[0]),
        'settlement details',
      );
    });

    test('should render dispute list data', async () => {
      renderApp({ props: { activeTab: 'dispute' } });
      await waitForLoadingToFinish();
      await waitFor(() => expect(screen.getAllByTestId('dispute-status-label')).toHaveLength(10));
      const disputeId = settlementsListData[0].id;
      const idLink = screen.getByRole('link', { name: disputeId });
      expect(idLink).toBeInTheDocument();
      expect(idLink).toHaveAttribute('href', `/disputes/${disputeId}`);
      expect(getEntityRows()).toHaveLength(10);
    });

    test('should render transfer list data', async () => {
      renderApp({ props: { activeTab: 'transfer' } });
      await waitForLoadingToFinish();
      const transferId = settlementsListData[0].id;
      const idLink = screen.getByRole('link', { name: transferId });
      expect(idLink).toBeInTheDocument();
      expect(idLink).toHaveAttribute(
        'href',
        `/route/transfers/${transferId}?init_point=transfers-table&init_page=Settlements.Transfers`,
      );
      expect(getEntityRows()).toHaveLength(10);
    });

    test('should render payment domestic list data', async () => {
      renderApp({ props: { activeTab: 'payment_domestic' } });
      await waitForLoadingToFinish();
      const paymentDomesticId = settlementsListData[0].id;
      const idLink = screen.getByRole('link', { name: paymentDomesticId });
      expect(idLink).toBeInTheDocument();
      expect(idLink).toHaveAttribute(
        'href',
        `/payments/${paymentDomesticId}?init_point=payments-table&init_page=Settlements.Payments`,
      );
      expect(getEntityRows()).toHaveLength(10);
    });

    test('should render payout list data', async () => {
      renderApp({ props: { activeTab: 'payout' } });
      await waitForLoadingToFinish();
      expect(screen.queryByRole('link')).not.toBeInTheDocument();
      expect(getEntityRows()).toHaveLength(10);
    });

    test('should render on demand settlement list data', async () => {
      const activeTab = 'ondemand settlement';
      renderApp({ props: { activeTab } });
      await waitForLoadingToFinish();
      expect(screen.queryByRole('link')).not.toBeInTheDocument();
      expect(getEntityRows()).toHaveLength(10);
      await userEvent.type(getSearchInput(activeTab), 'error-input');
      await clickSubmit();
      await waitForLoadingToFinish();
      expect(getEntityRows()).toHaveLength(1);
    });

    test('should render optimizer provider if optimizer provider exists', async () => {
      server.use(transactionDetailsSuccessHandler([optimizerProviderItem]));
      const { container } = renderApp({
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
      expect(screen.getAllByTestId('payment-optimizer-provider')).toHaveLength(1);
      expect(container.querySelector('thead tr').children[1]).toHaveTextContent(
        /payment provider/i,
      );
    });

    test('should only move provider key to the first index if optimizer provider exists', async () => {
      const { container } = renderApp({
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
      expect(container.querySelector('thead tr').children[1]).not.toHaveTextContent(
        /optimizer provider/i,
      );
    });

    test('should not render settled_by header', async () => {
      server.use(transactionDetailsSuccessHandler([settledByItem]));
      const { container } = renderApp({
        initialState: {
          session: { user: { merchant: { currency: 'INR' } } },
        },
      });
      await waitForLoadingToFinish();

      const columnsLengthWithoutSettledByHeader = Object.keys(settledByItem).length - 1;
      expect(container.querySelector('thead tr').children).toHaveLength(
        columnsLengthWithoutSettledByHeader,
      );

      const entityRow = getEntityRows()[0];
      expect(entityRow.children).toHaveLength(columnsLengthWithoutSettledByHeader);
    });

    test('should render RM as symbol for MYR currency', async () => {
      server.use(transactionDetailsSuccessHandler([settledByItem]));
      renderApp({
        props: {
          currency: 'MYR',
        },
      });
      await waitForLoadingToFinish();
      const currencySymbols = screen.getAllByText('RM');
      const currencySymbol = currencySymbols[1];
      expect(currencySymbol).toHaveTextContent('RM');
    });
  });
});
