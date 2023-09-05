import RefundDetailsContainer from 'merchant/views/Transactions/v1/Refunds/Details';
import { fireEvent, screen, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { render } from '@testing-library/react';
import { storeWithInitialState } from 'merchant/store';
import { fetchItem } from 'merchant/reducers/refunds/details';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { refund as mockRefund } from 'merchant/views/Transactions/v1/Refunds/__test__/mocks/fixtures';
import * as RzpUtils from 'common/utils/rzp-utils';

jest.mock('merchant/reducers/refunds/details', () => ({
  ...jest.requireActual('merchant/reducers/refunds/details'),
  fetchItem: jest.fn(),
}));

jest.mock('common/utils/selfServeAnalytics', () => ({
  ...jest.requireActual('common/utils/selfServeAnalytics'),
  selfServeTrackSuccess: jest.fn(),
}));

jest.mock('merchant/views/Transactions/v2/common/utils', () => ({
  ...jest.requireActual('merchant/views/Transactions/v2/common/utils'),
  isTransactionsV2Enabled: (_) => true,
}));

jest.mock(
  'merchant/views/Transactions/v1/Refunds/components/RefundDetails',
  () =>
    ({ viewRefundHistory, isLoading, refund, statusMsg }) => {
      return (
        <>
          <div>RefundDetails Component</div>
          {isLoading ? (
            <div>Loading</div>
          ) : (
            <button type="button" onClick={() => viewRefundHistory(refund)}>
              RefundDetails viewRefundHistory
            </button>
          )}
          {statusMsg?.message && <p>{statusMsg.message}</p>}
        </>
      );
    },
);

const initState = {
  orders: {
    error: null,
    loading: false,
    items: [mockRefund],
  },
};

const App = ({ state, ...props }) => {
  return (
    <Provider
      store={storeWithInitialState({
        ...initState,
        ...state,
      })}
    >
      <RefundDetailsContainer {...props} />
    </Provider>
  );
};

describe('Refunds - Details Component', () => {
  const mockFetchItem = (returnValue) => {
    fetchItem.mockReturnValue({ type: 'REFUND_FETCH', payload: Promise.resolve(returnValue) });
  };

  afterEach(() => {
    fetchItem.mockReset();
  });

  test('should show Refunds Details component', async () => {
    mockFetchItem(mockRefund);
    render(<App id={mockRefund.id} />);

    await waitFor(() => {
      expect(screen.queryByText('loading')).not.toBeInTheDocument();
      expect(screen.queryByText('RefundDetails Component')).toBeInTheDocument();
      const onTogglePaymentsButton = screen.getByRole('button', {
        name: 'RefundDetails viewRefundHistory',
      });
      expect(onTogglePaymentsButton).toBeInTheDocument();
      fireEvent.click(onTogglePaymentsButton);
    });
  });

  test('should make self Serve track success analytics event when refund is fetched', async () => {
    mockFetchItem(mockRefund);
    render(<App id={mockRefund.id} />);
    await waitFor(() => {
      expect(screen.queryByText('loading')).not.toBeInTheDocument();
      expect(selfServeTrackSuccess).toHaveBeenCalledWith({
        selfServeAction: 'Refund Details Fetched',
        screen: 'Refund Details',
        props: {},
        version: 'v2',
      });
    });
  });

  test('should call refund history open analytics event on view history CTA', async () => {
    mockFetchItem(mockRefund);
    render(<App id={mockRefund.id} />);

    await waitFor(() => {
      expect(screen.queryByText('loading')).not.toBeInTheDocument();
      const viewRefundHistoryCTA = screen.getByRole('button', {
        name: 'RefundDetails viewRefundHistory',
      });
      expect(viewRefundHistoryCTA).toBeInTheDocument();
      fireEvent.click(viewRefundHistoryCTA);
      expect(window.rzpAnalytics).toHaveBeenCalled();
    });
  });

  test('should show error if refund in state has error', () => {
    jest.spyOn(RzpUtils, 'getEventCategoryFromPath').mockReturnValue('');
    mockFetchItem({});
    const error = 'testing error case';
    render(
      <App
        state={{
          refund: {
            error,
            loading: false,
            refund: {},
          },
        }}
        id={mockRefund.id}
        error={error}
      />,
    );
    // asserting presence of error on the screen
    expect(screen.getByText(error)).toBeInTheDocument();
  });

  test('should refetch refund on prop refund change', async () => {
    mockFetchItem(mockRefund);
    const { rerender } = render(<App id={mockRefund.id} />);
    await waitFor(() => {
      expect(fetchItem).toHaveBeenCalledWith(mockRefund.id);
    });

    const newRefundId = 'rfnd_KKJQbZpqbERqDl';
    mockFetchItem({ ...mockRefund, id: newRefundId });
    rerender(<App id={newRefundId} />);
    await waitFor(() => {
      expect(fetchItem).toHaveBeenCalledWith(newRefundId);
    });

    mockFetchItem(null);
    rerender(<App id="rfnd_KKJQbZpqbERsh" />);
  });
});
