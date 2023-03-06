import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import CODOrdersTab from 'merchant/views/MagicCheckout/CODOrdersTab';
import 'jest-location-mock';

jest.mock('merchant/views/MagicCheckout/CODOrdersTab/constants', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/CODOrdersTab/constants'),
  TABS: [
    {
      id: 'reviewOrdersTab',
      title: 'Review Orders',
      component: <div>Review orders tab</div>,
    },
    {
      id: 'approvedOrdersTab',
      title: 'Approved Orders',
      component: <div>Approved orders tab</div>,
    },
    {
      id: 'canceledOrdersTab',
      title: 'Canceled Orders',
      component: <div>Canceled orders tab</div>,
    },
    {
      id: 'onHoldOrdersTab',
      title: 'On Hold Orders',
      component: <div>OnHold orders tab</div>,
    },
  ],
}));

jest.mock(
  'merchant/views/MagicCheckout/OrderStatusUpload/components/MainContent',
  () => (props) => {
    const { activeNav, render } = props;
    return <div>{render(activeNav)}</div>;
  },
);

jest.mock('merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer', () => () => (
  <div>Order Info drawer</div>
));

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <CODOrdersTab {...props} />
    </Provider>,
  );
};

describe('COD orders tab component', () => {
  test('component should render properly', () => {
    renderApp();
    expect(screen.getByText(/Approved orders/i)).toBeInTheDocument();
  });

  test('should be able to click on other tabs', async () => {
    renderApp();
    const element = screen.getByText(/Approved orders/i);
    expect(element).toBeInTheDocument();

    await userEvent.click(element);
    expect(screen.getByText(/Approved orders tab/i)).toBeInTheDocument();
  });

  test('should open order info drawer if order id query param is available', async () => {
    window.location.assign('/app/magic/cod-orders?order_id=test_Order_123');
    renderApp();

    await expect(screen.getByText(/order info drawer/i)).toBeInTheDocument();
  });
});
