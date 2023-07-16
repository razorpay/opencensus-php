import { render, screen, userEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ShopifyOrderEditing from 'merchant/views/MagicCheckout/ShopifyOrderEditing/PageContent';

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

test('should show the particular order when searched via filters', async () => {
  render(<App />);

  const searchBtn = await waitFor(() => screen.getByRole('button', { name: /search/i }));

  const inputElement = screen.getByLabelText('Shopify Order ID');
  await userEvent.type(inputElement, testOrder.display_name);

  await userEvent.click(searchBtn);

  await waitFor(() => screen.findByText(testOrder.id));

  expect(screen.getByText(testOrder.id)).toBeInTheDocument();
});
