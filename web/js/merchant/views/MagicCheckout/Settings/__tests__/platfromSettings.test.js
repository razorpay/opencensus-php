import PlatformSettings from 'merchant/views/MagicCheckout/Settings/containers/PlatformSettings';
import { render, screen, waitFor } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';
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

const renderAppWithRouter = ({ state, ...props } = {}) => {
  render(<PlatformSettings {...props} />, {
    reduxStore: storeWithInitialState({ ...initState, ...state }),
  });
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
