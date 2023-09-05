import OrdersListContainer from 'merchant/views/Transactions/v1/Orders/List';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import { MockListPayload } from 'merchant/views/Transactions/v1/Orders/__test__/mocks/fixtures';
import { fetchOrders } from 'merchant/reducers/collection';
import { analyticsTrack } from 'common/utils/analytics';

jest.mock('common/utils/analytics', () => ({
  ...jest.requireActual('common/utils/analytics'),
  analyticsTrack: jest.fn(),
}));

jest.mock('merchant/reducers/collection', () => ({
  ...jest.requireActual('merchant/reducers/collection'),
  fetchOrders: jest.fn(),
}));

fetchOrders.mockReturnValue({
  type: 'ORDERS_FETCH',
  payload: Promise.resolve(MockListPayload),
});

const initProps = {
  location: {
    search: '',
  },
};

describe('Orders - List Component', () => {
  afterEach(() => {
    analyticsTrack.mockReset();
  });
  test('should render List component', () => {
    render(<OrdersListContainer {...initProps} />);
    expect(screen.getByTestId('orders-list')).toBeInTheDocument();
    const submitBtn = screen.getByRole('button', {
      name: 'Search',
    });
    expect(submitBtn).toBeInTheDocument();
    const clearBtn = screen.getByRole('button', {
      name: 'Clear',
    });
    expect(clearBtn).toBeInTheDocument();
    fireEvent.click(clearBtn);
  });

  test('should fire orders search clicked analytics event on submit click', () => {
    render(<OrdersListContainer {...initProps} />);
    const submitBtn = screen.getByRole('button', {
      name: 'Search',
    });
    expect(submitBtn).toBeInTheDocument();
    fireEvent.click(submitBtn);
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'orders search',
      actionName: 'clicked',
      screen: 'transactions',
      properties: expect.anything(),
    });
  });

  describe('Should fire order search result analytics event on search response', () => {
    test('should pass status as success when fetchOrders response is success', async () => {
      render(<OrdersListContainer {...initProps} />);
      const submitBtn = screen.getByRole('button', {
        name: 'Search',
      });
      expect(submitBtn).toBeInTheDocument();
      fireEvent.click(submitBtn);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          objectName: 'orders search',
          actionName: 'result',
          screen: 'transactions',
          properties: expect.anything(),
        });
        expect(analyticsTrack.mock.calls[1][0].properties.status).toBe('success');
      });
    });

    test('should pass status as failure when fetchOrders response is failure', async () => {
      jest.spyOn(require('common/utils/rzp-utils'), 'getKeysSeparatedByPipe').mockReturnValue('');
      fetchOrders.mockReturnValue({
        type: 'ORDERS_FETCH',
        payload: Promise.reject({
          errors: [new Error('Test Error')],
        }),
      });
      render(<OrdersListContainer {...initProps} />);
      const submitBtn = screen.getByRole('button', {
        name: 'Search',
      });
      expect(submitBtn).toBeInTheDocument();
      fireEvent.click(submitBtn);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          objectName: 'orders search',
          actionName: 'result',
          screen: 'transactions',
          properties: expect.anything(),
        });
        expect(analyticsTrack.mock.calls[1][0].properties.status).toBe('failure');
      });
    });
  });
});
