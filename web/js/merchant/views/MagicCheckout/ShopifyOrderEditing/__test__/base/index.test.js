// test util imports
import { render, screen, waitFor } from 'test-utils';

// ui imports
import ShopifyOrderEditing from 'merchant/views/MagicCheckout/ShopifyOrderEditing';

jest.mock('merchant/views/MagicCheckout/ShopifyOrderEditing/PageContent', () => () => {
  return (
    <div>
      <h1>Order Editing Table</h1>
    </div>
  );
});

test('should render Shopify order editing component', async () => {
  render(<ShopifyOrderEditing />);

  await waitFor(() => {
    expect(screen.getByText('Order Editing Table')).toBeInTheDocument();
  });
});
