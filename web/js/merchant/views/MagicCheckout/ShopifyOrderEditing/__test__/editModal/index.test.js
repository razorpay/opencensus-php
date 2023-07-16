import { render, screen, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ShopifyOrderEditModal from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal';
import { START_EDITING_RESPONSE } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/__test__/mocks/fixtures';
import { beginOrderEditing } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

jest.mock('merchant/views/MagicCheckout/ShopifyOrderEditing/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/ShopifyOrderEditing/api'),
  beginOrderEditing: jest.fn(),
}));

jest.mock('merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/views', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/views'),
  DefaultView: () => (
    <div>
      <h1>Default View</h1>
    </div>
  ),
}));

beginOrderEditing.mockReturnValue({
  data: START_EDITING_RESPONSE,
});

const Modal = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <ShopifyOrderEditModal id={1234} display_id={6797} {...props} />
    </Provider>
  );
};

describe('Magic - Shopify Order edititng', () => {
  test('should render Shopify Order Edit Modal', async () => {
    render(<Modal />);

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Edit Order - 6797' })).toBeInTheDocument();
    });
  });
});
