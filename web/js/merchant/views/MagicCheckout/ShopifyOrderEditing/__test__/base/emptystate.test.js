// test util imports
import { render, screen, waitFor } from 'test-utils';

// redux imports
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

// ui imports
import ShopifyOrderEditing from 'merchant/views/MagicCheckout/ShopifyOrderEditing/PageContent';

// API Imports
import { RESPONSE_WITH_NO_ORDER } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/__test__/mocks/fixtures';
import { fetchShopifyOrders } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

jest.mock('merchant/views/MagicCheckout/ShopifyOrderEditing/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/ShopifyOrderEditing/api'),
  fetchShopifyOrders: jest.fn(),
}));

fetchShopifyOrders.mockReturnValue(RESPONSE_WITH_NO_ORDER);

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <ShopifyOrderEditing {...props} />
    </Provider>
  );
};

describe('Magic - Shopify Order edititng', () => {
  test('should render Shopify order editing with "No orders found" message', async () => {
    render(<App />);

    // Wait for the "No orders found" message to be rendered
    await waitFor(() => {
      expect(screen.getByText('No orders found for the criteria!')).toBeInTheDocument();
    });
  });
});
