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

describe('PlatformSettings', () => {
  test('should render', async () => {
    renderAppWithRouter();
    await waitFor(() => {
      expect(screen.getByText(/Platform Settings/)).toBeInTheDocument();
    });
  });

  test('should not be visible if user role is other than admin or owner', async () => {
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

  test('should have title `Magic Checkout Settings` if user has onboarded C360', async () => {
    const customState = {
      ...initState,
      session: {
        user: {
          isMagicCODOrderAutomationEnabled: true,
          role: 'owner',
          isC360OnboardingCompleted: true,
        },
      },
    };
    renderAppWithRouter({ state: customState });
    await waitFor(() => {
      expect(screen.queryByText(/Platform Settings/)).not.toBeInTheDocument();
      expect(
        screen.queryByText(/Magic Checkout Settings/),
      ).toBeInTheDocument();
    });
  });

  test('should have title `Platform Settings` if user has not onboarded C360', async () => {
    const customState = {
      ...initState,
      session: {
        user: {
          isMagicCODOrderAutomationEnabled: true,
          role: 'owner',
          isC360OnboardingCompleted: false,
        },
      },
    };
    renderAppWithRouter({ state: customState });
    await waitFor(() => {
      expect(screen.queryByText(/Platform Settings/)).toBeInTheDocument();
      expect(
        screen.queryByText(/Magic Checkout Settings/),
      ).not.toBeInTheDocument();
    });
  });
});
