import PlatformSettings from 'merchant/views/MagicCheckout/Settings/containers/PlatformSettings';
import { Router } from 'react-router-dom';
import { render, screen, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { createMemoryHistory } from 'history';

const initState = {
  magic_settings: {
    domain_url: 'https://test.myshopify.com',
    platform: 'shopify',
    shop_id: 'test',
    cod_intelligence: false,
    one_cc_auto_fetch_coupons: true,
    manual_control_cod_order: true,
    one_click_checkout: true,
  },
  session: {
    user: {
      isMagicCODOrderAutomationEnabled: true,
      role: 'owner',
    },
  },
  magicCheckout: {
    cod_order_control: true,
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <PlatformSettings {...props} />
    </Provider>
  );
};

const renderAppWithRouter = ({ state, ...props } = {}) => {
  render(
    <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
      <App state={state} {...props} />
    </Router>,
  );
};

describe('Platform settings component', () => {
  test('platform settings should render properly', async () => {
    renderAppWithRouter();
    await waitFor(() => {
      expect(screen.getByText(/Platform Settings/)).toBeInTheDocument();
    });
  });

  test('platform settings should be visible if user role is other than admin or owner', async () => {
    const customState = {
      ...initState,
      session: {
        user: {
          isMagicCODOrderAutomationEnabled: true,
          role: 'manager',
        },
      },
    };
    renderAppWithRouter({ state: customState });
    await waitFor(() => {
      expect(screen.queryByText(/Platform Settings/)).not.toBeInTheDocument();
    });
  });
});
