import React, { useContext } from 'react';

import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { render, screen, server, userEvent, waitForElementToBeRemoved } from 'test-utils';

const App = () => {
  const { state } = useContext(PosDeviceStoreContext);
  const { cartItems, isCartOpen } = state;
  return (
    <div>
      <p data-testid="cart-items-length">{cartItems.length}</p>
      {isCartOpen ? <div>Mock Cart</div> : null}
      <AddToCartButton plan="monthly" productCode="android-smart-pos" openCartOnUpdate />
    </div>
  );
};

const renderApp = () => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <App />
    </PosDeviceStoreProvider>,
  );
};

describe('<AddToCartButton/>', () => {
  beforeEach(async () => {
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should render Add to Cart button on screen', () => {
    expect(screen.getByText('Add to cart')).toBeVisible();
  });

  test('should update cart on clicking on Add to cart', async () => {
    await userEvent.click(screen.getByText('Add to cart'));
    expect(screen.getByTestId('cart-items-length')).toHaveTextContent('1');
  });

  test('should open cart if passed openCartOnUpdate as true', async () => {
    await userEvent.click(screen.getByText('Add to cart'));
    expect(screen.getByText('Mock Cart')).toBeVisible();
  });
});
