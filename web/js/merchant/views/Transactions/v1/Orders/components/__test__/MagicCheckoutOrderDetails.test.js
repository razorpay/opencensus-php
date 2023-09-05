import MagicCheckoutOrderDetails from 'merchant/views/Transactions/v1/Orders/components/MagicCheckoutOrderDetails';
import { render, fireEvent, screen } from 'test-utils';
import { getOrderLineItems, order, customer_details } from './constants';
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

describe('Order MagicCheckoutOrderDetails Component', () => {
  it('should render Magic Checkout Order Details', () => {
    render(<MagicCheckoutOrderDetails {...initProps} />);
    [
      'Order Id',
      order.id,
      'Order Type',
      'Total Amount Paid',
      'Attempts',
      'Status',
      'Created At',
    ].forEach((property) => {
      expect(screen.getByText(new RegExp(property, 'i'))).toBeInTheDocument();
    });
  });

  it('should emit track event with right properties on mount', () => {
    render(<MagicCheckoutOrderDetails {...initProps} />);
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
    render(<MagicCheckoutOrderDetails {...initProps} isLoading={true} />);
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  it('should render SKU details if line_items is defined', () => {
    const line_items = getOrderLineItems(9);
    render(<MagicCheckoutOrderDetails {...initProps} order={{ ...order, line_items }} />);
    expect(screen.getByText(new RegExp('SKU ID', 'i'))).toBeInTheDocument();
    expect(screen.getByText(new RegExp(line_items[0].sku, 'i'))).toBeInTheDocument();
  });

  describe('Customer Details', () => {
    const setupCustomerDetails = () => {
      render(
        <MagicCheckoutOrderDetails
          {...initProps}
          order={{ ...order, customer_details, notes: {} }}
        />,
      );
    };

    it('should show customer contact details', () => {
      setupCustomerDetails();
      expect(screen.getByText(new RegExp(customer_details?.contact, 'i'))).toBeInTheDocument();
      expect(screen.getByText(new RegExp(customer_details?.email, 'i'))).toBeInTheDocument();
    });

    it('should show shipping details', () => {
      setupCustomerDetails();
      const shipping_address = customer_details.shipping_address;
      // iterate over passed shipping details and test for all of them to be present in the component
      for (const shipping_property in shipping_address) {
        if (Object.prototype.hasOwnProperty.call(shipping_address, shipping_property)) {
          expect(
            screen.getByText(new RegExp(shipping_address[shipping_property], 'i')),
          ).toBeInTheDocument();
        }
      }
    });

    it('should show billing details', () => {
      setupCustomerDetails();
      const billing_address = customer_details.billing_address;
      // iterate over passed billing details and test for all of them to be present in the component
      for (const billing_property in billing_address) {
        if (Object.prototype.hasOwnProperty.call(billing_address, billing_property)) {
          expect(
            screen.getByText(new RegExp(billing_address[billing_property], 'i')),
          ).toBeInTheDocument();
        }
      }
    });
  });

  it('should show notes key and value if defined', () => {
    const notes = {
      notes_key_1: 'test test 1',
      notes_key_2: 'test note 2',
    };
    render(
      <MagicCheckoutOrderDetails
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
        <MagicCheckoutOrderDetails
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
        <MagicCheckoutOrderDetails
          {...initProps}
          order={{
            ...order,
            attempts: 0,
          }}
        />,
      );
      expect(screen.getByText(new RegExp('No Payments'))).toBeInTheDocument();
    });
  });
});
