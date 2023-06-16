import { render, screen, waitFor, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { rest } from 'msw';
import OrderInfoDrawer from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/container/OrderInfoDrawer';
import 'react-dates/initialize';

import {
  INIT_STATE,
  ORDER_INFO_RES,
} from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/__tests__/mocks/fixtures';

jest.mock(
  'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/OrderFilters',
  () => () => {
    return (
      <div>
        <p>Order filters</p>
      </div>
    );
  },
);

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <OrderInfoDrawer {...props} />
    </Provider>,
  );
};

describe('testing order info drawer', () => {
  test('order info drawer should render properly', async () => {
    const id = 'order_JfP6IFRXAaXv3W';
    server.use(
      rest.get(`*/merchant/api/test/1cc/prepay/orders/${id}`, (req, res, ctx) => {
        return res(ctx.status(200), ctx.json(ORDER_INFO_RES), ctx.delay(50));
      }),
    );
    renderApp({
      setIsSliderOpen: jest.fn(),
      isSliderOpen: true,
      requiredOrderId: 'order_JfP6IFRXAaXv3W',
    });

    await waitFor(() => {
      expect(screen.getByText(/razorpay order id/i)).toBeInTheDocument();
    });
  });

  test('should not show order details if slide is not open', () => {
    renderApp({
      state: { ...INIT_STATE, magicCheckout: { cod_order_control: false } },
      setIsSliderOpen: jest.fn(),
      isSliderOpen: false,
      requiredOrderId: 'order_JfP6IFRXAaXv3W',
    });
    expect(screen.queryByText(/razorpay order id/i)).not.toBeInTheDocument();
  });
});
