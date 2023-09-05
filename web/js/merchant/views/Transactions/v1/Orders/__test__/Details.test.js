import OrderDetailsContainer from 'merchant/views/Transactions/v1/Orders/Details';
import { screen, fireEvent, waitFor } from 'test-utils';
import { fetchItem, fetchMagicCheckoutItem } from 'merchant/reducers/orders/details';
import { render } from '@testing-library/react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import {
  mockOrder,
  mockMagicOrder,
} from 'merchant/views/Transactions/v1/Orders/__test__/mocks/fixtures';

jest.mock('merchant/reducers/orders/details', () => ({
  ...jest.requireActual('merchant/reducers/orders/details'),
  fetchItem: jest.fn(),
  fetchMagicCheckoutItem: jest.fn(),
}));

jest.mock('common/utils/selfServeAnalytics', () => ({
  ...jest.requireActual('common/utils/selfServeAnalytics'),
  selfServeTrackSuccess: jest.fn(),
}));

jest.mock('merchant/views/Transactions/v2/common/utils', () => ({
  ...jest.requireActual('merchant/views/Transactions/v2/common/utils'),
  isTransactionsV2Enabled: jest.fn().mockReturnValue(true),
}));

jest.mock(
  'merchant/views/Transactions/v1/Orders/components/OrderDetails',
  () =>
    ({ onTogglePayments, isLoading, order, statusMsg }) => {
      return (
        <>
          <div>OrderDetails Component</div>
          {isLoading ? (
            <div>Loading</div>
          ) : (
            <button type="button" onClick={() => onTogglePayments(order)}>
              Order Details onTogglePayments
            </button>
          )}
          {statusMsg?.message && <p>{statusMsg.message}</p>}
        </>
      );
    },
);

jest.mock(
  'merchant/views/Transactions/v1/Orders/components/MagicCheckoutOrderDetails',
  () =>
    ({ onTogglePayments, isLoading, order, statusMsg }) =>
      (
        <>
          <div>MagicCheckoutOrderDetails Component</div>
          {isLoading ? (
            <div>Loading</div>
          ) : (
            <button type="button" onClick={() => onTogglePayments(order)}>
              Magic Checkout Order Details onTogglePayments
            </button>
          )}
          {statusMsg?.message && <p>{statusMsg.message}</p>}
        </>
      ),
);

const initState = {
  orders: {
    error: null,
    loading: false,
    items: [mockOrder, mockMagicOrder],
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
      <OrderDetailsContainer {...props} />
    </Provider>
  );
};

describe('Orders - Details Component', () => {
  const mockFetchItem = (returnValue) => {
    fetchItem.mockReturnValue({ type: 'ORDER_FETCH', payload: Promise.resolve(returnValue) });
  };
  const mockFetchMagicItem = (returnValue) => {
    fetchMagicCheckoutItem.mockReturnValue({
      type: 'ORDER_FETCH',
      payload: Promise.resolve(returnValue),
    });
  };

  afterEach(() => {
    fetchItem.mockReset();
    fetchMagicCheckoutItem.mockReset();
  });

  test('should show OrderDetails component if its regular order', async () => {
    mockFetchItem(mockOrder);
    render(<App id={mockOrder.id} />);

    await waitFor(() => {
      expect(screen.queryByText('loading')).not.toBeInTheDocument();
      expect(screen.queryByText('OrderDetails Component')).toBeInTheDocument();
      const onTogglePaymentsButton = screen.getByRole('button', {
        name: 'Order Details onTogglePayments',
      });
      expect(onTogglePaymentsButton).toBeInTheDocument();
      fireEvent.click(onTogglePaymentsButton);
    });
  });

  test('should show MagicCheckoutOrderDetails component if its magic checkout order', async () => {
    mockFetchMagicItem(mockMagicOrder);
    render(<App id={mockMagicOrder.id} />);

    await waitFor(() => {
      expect(screen.queryByText('loading')).not.toBeInTheDocument();
      expect(screen.queryByText('MagicCheckoutOrderDetails Component')).toBeInTheDocument();
      const onTogglePaymentsButton = screen.getByRole('button', {
        name: 'Magic Checkout Order Details onTogglePayments',
      });
      expect(onTogglePaymentsButton).toBeInTheDocument();
      fireEvent.click(onTogglePaymentsButton);
    });
  });

  test('should call selfServeTrackSuccess analytics event when order is fetched', async () => {
    mockFetchItem(mockOrder);
    render(<App id={mockOrder.id} />);
    await waitFor(() => {
      expect(screen.queryByText('loading')).not.toBeInTheDocument();
      expect(selfServeTrackSuccess).toHaveBeenCalledWith({
        selfServeAction: 'Order Details Fetched',
        screen: 'Order Details',
        props: {},
        version: 'v2',
      });
    });
  });

  test('should show error if order in state has error', () => {
    mockFetchItem({});
    const error = 'testing error case';
    render(
      <App
        state={{
          order: {
            error,
            loading: false,
            order: {},
          },
        }}
        id={mockOrder.id}
        error={error}
      />,
    );
    // asserting presence of error on the screen
    expect(screen.getByText(error)).toBeInTheDocument();
  });

  describe('Refetch order details on prop change', () => {
    test('should call fetchMagicCheckoutItem first then fetchItem when order is changed from magic to regular', async () => {
      jest.spyOn(require('common/utils/rzp-utils'), 'getEventCategoryFromPath').mockReturnValue('');
      mockFetchMagicItem(mockMagicOrder);
      const { rerender } = render(<App id={mockMagicOrder.id} />);
      await waitFor(() => {
        expect(fetchMagicCheckoutItem).toHaveBeenCalledWith(mockMagicOrder.id);
      });

      // rerendering with different order id for componentWillReceiveProps to act
      mockFetchItem(mockOrder);
      rerender(<App id={mockOrder.id} />);
      await waitFor(() => {
        expect(fetchItem).toHaveBeenCalledWith(mockOrder.id);
      });
    });

    test('should call fetchItem first then fetchMagicCheckoutItem when order is changed from regular to magic', async () => {
      mockFetchItem(mockOrder);
      const { rerender } = render(<App id={mockOrder.id} />);
      await waitFor(() => {
        expect(fetchItem).toHaveBeenCalledWith(mockOrder.id);
      });

      // rerendering with different order id for componentWillReceiveProps to act
      mockFetchMagicItem(mockMagicOrder);
      rerender(<App id={mockMagicOrder.id} />);
      await waitFor(() => {
        expect(fetchMagicCheckoutItem).toHaveBeenCalledWith(mockMagicOrder.id);
      });
    });
  });
});
