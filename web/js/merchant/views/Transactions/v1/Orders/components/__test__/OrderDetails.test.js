import OrderDetails from 'merchant/views/Transactions/v1/Orders/components/OrderDetails';
import { render, fireEvent, screen } from 'test-utils';
import { order } from './constants';
import { analyticsTrack } from 'common/utils/analytics';

const mockOnTogglePayments = jest.fn();

const initProps = {
  order,
  payments: {
    loading: true,
    items: [],
    error: null,
  },
  isLoading: false,
  statusMsg: {},
  onTogglePayments: mockOnTogglePayments,
};

describe('Orders - OrderDetails Component', () => {
  it('should render Order Details', () => {
    render(<OrderDetails {...initProps} />);
    ['Order Id', order.id, 'Attempts', 'Status', 'Created At'].forEach((property) => {
      expect(screen.getByText(new RegExp(property, 'i'))).toBeInTheDocument();
    });
  });

  it('should emit track event with right properties on mount', () => {
    render(<OrderDetails {...initProps} />);
    expect(analyticsTrack).toHaveBeenCalled();
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'order details',
      actionName: 'fetched',
      screen: 'transactions',
      properties: {
        orderId: order.id,
        orderAmount: order.amount,
        orderCurrency: order.currency,
        orderStatus: order.status,
        createdAt: order.created_at,
      },
    });
  });

  it('should show loading spinner when isLoading is true', () => {
    render(<OrderDetails {...initProps} isLoading={true} />);
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  it('should show notes key and value if defined', () => {
    const notes = {
      notes_key_1: 'test test 1',
      notes_key_2: 'test note 2',
    };
    render(
      <OrderDetails
        {...initProps}
        order={{
          ...order,
          notes,
        }}
      />,
    );
    for (const note in notes) {
      if (Object.prototype.hasOwnProperty.call(notes, note)) {
        expect(screen.getByText(new RegExp(note, 'i'))).toBeInTheDocument();
        expect(screen.getByText(new RegExp(notes[note], 'i'))).toBeInTheDocument();
      }
    }
  });

  describe('Order Details Payments', () => {
    it('should show payments if orders attempts is more than 0', () => {
      render(
        <OrderDetails
          {...initProps}
          order={{
            ...order,
            attempts: 3,
          }}
        />,
      );
      // assert the presence of Payments ListGroupToggler
      const button = screen.getByRole('button', {
        name: 'Show/Hide',
      });
      expect(button).toBeInTheDocument();
      fireEvent.click(button);
      expect(mockOnTogglePayments).toHaveBeenCalled();
    });

    it('should not show payments if orders attempts is 0', () => {
      render(
        <OrderDetails
          {...initProps}
          order={{
            ...order,
            attempts: 0,
            notes: null,
          }}
        />,
      );
      expect(screen.getByText(new RegExp('No Payments'))).toBeInTheDocument();
    });
  });
});
