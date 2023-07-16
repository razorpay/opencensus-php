import { render, screen, userEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ShopifyOrderEditing from 'merchant/views/MagicCheckout/ShopifyOrderEditing/PageContent';

import * as ModalActions from 'merchant_common/reducers/modals';

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

describe('Shopify Order Editing', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');
  test('should open the order edit modal when the status is unfulfilled', async () => {
    render(<App />);

    const editCtaWrapper = await waitFor(() =>
      screen.findByTestId(`edit-${testOrder.platform_order_id}`),
    );

    expect(editCtaWrapper).toBeInTheDocument();

    const button = editCtaWrapper.querySelector('button');
    await userEvent.click(button);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
      expect(openModalSpy).toHaveBeenCalledTimes(1);
      expect(openModalSpy).toHaveBeenCalledWith({
        size: 'large',
        className: 'order-editing-modal',
        component: expect.any(Object),
      });
    });
  });
});
