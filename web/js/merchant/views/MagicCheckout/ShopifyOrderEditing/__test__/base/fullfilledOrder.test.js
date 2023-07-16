// test util imports
import { render, screen, waitFor } from 'test-utils';

// redux imports
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

// ui imports
import ShopifyOrderEditing from 'merchant/views/MagicCheckout/ShopifyOrderEditing/PageContent';

// API Imports
import { RESPONSE_WITH_FULLFILLED_ORDER } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/__test__/mocks/fixtures';
import { fetchShopifyOrders } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

jest.mock('merchant/views/MagicCheckout/ShopifyOrderEditing/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/ShopifyOrderEditing/api'),
  fetchShopifyOrders: jest.fn(),
}));

fetchShopifyOrders.mockReturnValue(RESPONSE_WITH_FULLFILLED_ORDER);

const testOrder = {
  id: 'order_MCnpyKC1BO0bzf',
  display_name: '#6740',
  fulfillment_status: 'FULFILLED',
  platform_order_id: 'gid://shopify/Order/5075741868199',
  currency: 'INR',
  created_at: '2023-07-12T07:12:29Z',
  payment_status: 'PAID',
  price: 7500,
  customer: 'John',
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <ShopifyOrderEditing {...props} />
    </Provider>
  );
};

describe('Magic - Shopify Order edititng', () => {
  test('should render shopify order editing', async () => {
    render(<App />);

    await waitFor(() => screen.findByText('order_MCnpyKC1BO0bzf'));
    const { id, display_name, customer, price, fulfillment_status, platform_order_id } = testOrder;

    // Table Headers
    expect(screen.getByText('Razorpay Order Id')).toBeInTheDocument();
    expect(screen.getByText('Shopify Order Id')).toBeInTheDocument();
    expect(screen.getByText('Created At')).toBeInTheDocument();
    expect(screen.getByText('Customer Name')).toBeInTheDocument();
    expect(screen.getByText('Price')).toBeInTheDocument();
    expect(screen.getByText('Fulfillment Status')).toBeInTheDocument();
    expect(screen.getByText('Payment Status')).toBeInTheDocument();
    expect(screen.getByText('Action')).toBeInTheDocument();

    // Some API Data
    expect(screen.getByText(id)).toBeInTheDocument();

    expect(screen.getByText(display_name)).toBeInTheDocument();
    expect(screen.getByText(customer)).toBeInTheDocument();
    expect(screen.getByText(`₹ ${price / 100}`)).toBeInTheDocument();
    expect(screen.getByText(fulfillment_status)).toBeInTheDocument();

    const editButton = await waitFor(() => screen.findByTestId(`edit-${platform_order_id}`));

    if (fulfillment_status === 'FULFILLED') {
      expect(editButton.getAttribute('class')).toContain('disable');
    }
  });
});
