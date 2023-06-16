import { render, screen } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import CODToPrepaidLinks from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks';
import 'jest-location-mock';

jest.mock(
  'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/container/CODPrepaidStatusContainer',
  () => () => <div>COD prepaid container</div>,
);
jest.mock(
  'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/container/OrderInfoDrawer',
  () => () => <div>Order Info drawer</div>,
);

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <CODToPrepaidLinks {...props} />
    </Provider>,
  );
};

describe('testing component', () => {
  test('should be able to render properly', () => {
    renderApp();
    expect(screen.getByText(/COD prepaid container/i)).toBeInTheDocument();
  });

  test('should be able to open order info drawer', async () => {
    window.location.assign('/app/magic/cod-orders?order_id=test_Order_123');
    renderApp();

    await expect(screen.getByText(/order info drawer/i)).toBeInTheDocument();
  });
});
