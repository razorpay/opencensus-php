import Details from 'merchant/views/Settlements/v2/Details';
import { render, screen, userEvent, waitFor } from 'test-utils';
import {
  settlementTabBreakupDetails,
  settleBreakupDetailsWithNewBreakup,
} from 'merchant/views/Settlements/__test__/data/SettlementsDB';
import { calculateCreditDebitAmount } from 'merchant/views/Settlements/v2/util';
import * as navigatorDetailsActions from 'merchant/reducers/navigator/details';

jest.mock('merchant/views/Settlements/v2/components/SettlementInfo', () => ({
  __esModule: true,
  default: ({ settlementId }) => (
    <div data-testid="settlement-info">SettlementId - {settlementId}</div>
  ),
}));

jest.mock('merchant/views/Settlements/v2/components/SettlementBreakup', () => ({
  __esModule: true,
  default: ({ settlementId }) => (
    <div data-testid="settlement-breakup">SettlementId - {settlementId}</div>
  ),
}));

jest.mock('merchant/views/Settlements/v2/components/SettlementEntities', () => ({
  __esModule: true,
  default: ({ settlementId }) => (
    <div data-testid="settlement-entities">SettlementId - {settlementId}</div>
  ),
}));

jest.mock('merchant/components/TestModeBanner', () => ({
  __esModule: true,
  default: () => <div data-testid="test-mode-banner">Test Mode Banner</div>,
}));

jest.mock('common/ui/Amount', () => ({
  ...jest.requireActual('merchant/components/File/Upload'),
  __esModule: true,
  default: ({ value }) => <div data-testid="amount">Amount: {value}</div>,
}));

jest.mock('merchant/views/Settlements/v2/components/TotalAmount', () => ({
  __esModule: true,
  default: ({ infoComp, value }) => (
    <>
      <div>Total Amount: {value}</div>
      {infoComp}
    </>
  ),
}));

jest.mock('merchant/views/Settlements/v2/components/MismatchBanner', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/views/Settlements/v2/components/MismatchBanner'),
  MismatchBanner: ({ totalAmount, calculatedAmounts, gatewayName }) => (
    <div data-testid="mismatch-banner">
      <p>Total Amount: {totalAmount}</p>
      <p>Calculated Amounts: {calculatedAmounts}</p>
      <p>Gateway name: {gatewayName ? gatewayName : 'not found'}</p>
    </div>
  ),
}));

const settlementId = 'setl_JFeIgD63bF8doh';

const defaultInitialState = {
  settlement: {
    breakupDetails: { ...settlementTabBreakupDetails, isBreakupNew: false },
    settlement: {
      created_at: 1649156071,
      entity: 'settlement',
      fees: 0,
      id: settlementId,
      optimizer_provider: 'Razorpay',
      resourceIdField: 'id',
      resourceUrl: 'settlements',
      settled_by: 'Razorpay',
      tax: 0,
    },
  },
  session: {
    user: {
      merchant: {
        currency: 'INR',
      },
    },
    mode: 'test',
  },
};

// component is not mapped with withRouter, hence match props is passed directly
const defaultProps = {
  match: {
    params: {
      id: settlementId,
    },
  },
};

const renderApp = ({ props, initialState = {} } = {}) => {
  return render(<Details {...defaultProps} {...props} />, {
    initialState: { ...defaultInitialState, ...initialState },
    path: '/settlements/:id',
    initialEntries: [`/settlements/${settlementId}`],
  });
};

const fetchTerminalProvidersMock = jest.spyOn(navigatorDetailsActions, 'fetchTerminalProviders');

describe('Settlement v2 Details', () => {
  test('should show loader dots and hide show more/less text on loading', () => {
    renderApp({
      initialState: {
        ...defaultInitialState,
        settlement: {
          ...defaultInitialState.settlement,
          breakupDetails: { ...defaultInitialState.settlement.breakupDetails, loading: true },
        },
      },
    });

    expect(screen.getByTestId('loader-dots')).toBeInTheDocument();
    expect(screen.queryByText('Show More')).not.toBeInTheDocument();
    expect(screen.queryByText('Show Less')).not.toBeInTheDocument();
  });

  test('should render settlement details', () => {
    renderApp();
    const allSettlementsLink = screen.getByRole('link', { name: 'All Settlements' });
    expect(allSettlementsLink).toBeInTheDocument();
    expect(allSettlementsLink).toHaveAttribute('href', '/settlements');
    expect(screen.getByText(`Settlement Id: ${settlementId}`)).toBeInTheDocument();
    ['settlement-info', 'settlement-breakup', 'settlement-entities'].forEach((testId) => {
      const settlementElement = screen.getByTestId(testId);
      expect(settlementElement).toBeInTheDocument();
      expect(settlementElement).toHaveTextContent(`SettlementId - ${settlementId}`);
    });
    expect(screen.getByTestId('test-mode-banner')).toBeInTheDocument();
    const creditDebitAmount = calculateCreditDebitAmount(settlementTabBreakupDetails.items, false);
    expect(screen.getByText(`Amount: ${creditDebitAmount.credit}`)).toBeInTheDocument();
    expect(screen.getByText(`Amount: ${creditDebitAmount.debit}`)).toBeInTheDocument();
    expect(screen.getByText('Total Amount: 34491790')).toBeInTheDocument();
    expect(screen.getByText('Show More')).toBeInTheDocument();
    expect(screen.queryByText('Show Less')).not.toBeInTheDocument();
  });

  test('should toggle show more on clicking settlement details', async () => {
    renderApp();
    expect(screen.queryByText('Show Less')).not.toBeInTheDocument();
    const showMore = screen.getByText('Show More');
    expect(showMore).toBeInTheDocument();
    await userEvent.click(showMore);
    expect(screen.queryByText('Show More')).not.toBeInTheDocument();
    expect(screen.getByText('Show Less')).toBeInTheDocument();
  });

  test('should render settlement details when isBreakupNew is true', () => {
    renderApp({
      initialState: {
        ...defaultInitialState,
        settlement: {
          ...defaultInitialState.settlement,
          breakupDetails: settleBreakupDetailsWithNewBreakup,
        },
      },
    });
    const creditDebitAmount = calculateCreditDebitAmount(
      settleBreakupDetailsWithNewBreakup.items,
      true,
    );
    expect(screen.getByText(`Amount: ${creditDebitAmount.credit}`)).toBeInTheDocument();
    expect(screen.getByText(`Amount: -${creditDebitAmount.debit}`)).toBeInTheDocument();

    expect(screen.getByText('Total Amount: 35006490')).toBeInTheDocument();
  });

  test('should show mismatch banner when user is isSingleReconEnabled and isOptimizerEnabled', async () => {
    renderApp({
      initialState: {
        ...defaultInitialState,
        session: {
          ...defaultInitialState.session,
          user: {
            isSingleReconEnabled: true,
            isOptimizerEnabled: true,
            merchant: { currency: 'INR' },
          },
        },
      },
    });

    expect(fetchTerminalProvidersMock).toHaveBeenCalled();

    const mismatchBanner = await screen.findByTestId('mismatch-banner');
    await waitFor(() => expect(mismatchBanner).toBeInTheDocument());
    expect(mismatchBanner).toHaveTextContent('Total Amount: 309');
    expect(mismatchBanner).toHaveTextContent('Calculated Amounts: -1036.67');
    expect(mismatchBanner).toHaveTextContent('Gateway name: Razorpay');
  });

  test('should gateway name be empty when user is isSingleReconEnabled and isOptimizerEnabled when settledBy is empty', async () => {
    renderApp({
      initialState: {
        ...defaultInitialState,
        session: {
          ...defaultInitialState.session,
          user: {
            isSingleReconEnabled: true,
            isOptimizerEnabled: true,
            merchant: { currency: 'INR' },
          },
        },
        settlement: {
          ...defaultInitialState.settlement,
          settlement: {},
        },
      },
    });

    expect(fetchTerminalProvidersMock).toHaveBeenCalled();

    const mismatchBanner = await screen.findByTestId('mismatch-banner');
    await waitFor(() => expect(mismatchBanner).toBeInTheDocument());
    expect(mismatchBanner).toHaveTextContent('Gateway name: not found');
  });
});
