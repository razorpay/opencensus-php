import { storeWithInitialState } from 'merchant/store';
import { TABS } from 'merchant/views/MagicCheckout/Settings/constants';
import NestedVerticalTabs, {
  TabItem,
} from 'merchant/views/MagicCheckout/Settings/containers/NestedVerticalTab';
import { render as renderMain, screen, userEvent, waitFor } from 'test-utils';

const initState = {
  magic_settings: {
    platform: 'shopify',
    showTabHeading: false,
  },
  magicCheckout: {
    cod_order_control: true,
    rcod: false,
  },
  session: {
    user: {
      isMagicPrepayCODEnabled: true,
      isMagicCODOrderAutomationEnabled: true,
      isMagicCODEngineEnabled: true,
      role: 'owner',
    },
  },
};
const MockedComponent = () => <div>Mocked Component</div>;

const variantOn = { variables: { result: 'on' } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      magic_analytics_setting: variantOn,
      magic_shopify_shipping_engine: variantOn,
      magic_coupon_engine: variantOn,
      magic_hide_cod_when_disabled: variantOn,
      checkout_v2: variantOn,
    },
  }),
  withSplitzService: jest.fn(),
}));

const render = (ui, config = { state: {} }) => {
  return renderMain(ui, {
    reduxStore: storeWithInitialState({ ...initState, ...config.state }),
  });
};

describe('Nested vertical tabs component', () => {
  test('Nested vertical tab should render fine', async () => {
    render(<NestedVerticalTabs />);

    await waitFor(() => {
      TABS[initState.magic_settings.platform].forEach((item) => {
        expect(screen.getByText(item.label)).toBeInTheDocument();
      });
    });
  });

  test('should navigate to destined route', async () => {
    const { history } = render(<NestedVerticalTabs />);

    const MagicIntelligenceTab = screen.getByText('RTO Settings');

    expect(MagicIntelligenceTab).toBeInTheDocument();

    await userEvent.click(MagicIntelligenceTab);
    expect(history.location.pathname).toEqual('/magic/settings/rto-settings');
  });

  test('should not show COD order automation tab if feature is not enabled', async () => {
    const customState = {
      magicCheckout: {
        cod_order_control: false,
      },
    };
    render(<NestedVerticalTabs />, { state: customState });

    await waitFor(() => expect(screen.queryByText('COD Review Workflow')).not.toBeInTheDocument());
  });

  test('tab item should be rendered properly', () => {
    const props = {
      tabHeading: 'Store settings',
      showTabHeading: true,
      tabContent: MockedComponent,
      className: 'store-settings',
    };
    render(<TabItem {...props} />);
    expect(screen.getByText('Mocked Component')).toBeInTheDocument();
  });

  test('should not display tab heading if not required', () => {
    const props = {
      tabContent: MockedComponent,
      className: 'store-settings',
    };
    render(<TabItem {...props} />);
    expect(screen.queryByText('Store settings')).not.toBeInTheDocument();
  });

  test('should not display store settings if user role is other than admin or owner', async () => {
    const customState = {
      ...initState,
      session: {
        user: {
          isMagicCODOrderAutomationEnabled: true,
          role: 'manager',
        },
      },
    };
    render(<NestedVerticalTabs />, {
      state: customState,
    });
    await waitFor(() => expect(screen.queryByText('Store Settings')).not.toBeInTheDocument());
  });

  test('should display COD and RTO settings if rcod is enabled', async () => {
    const customState = {
      magicCheckout: {
        rcod: true,
      },
    };
    render(<NestedVerticalTabs />, {
      state: customState,
    });
    await waitFor(() => expect(screen.queryByText('COD Settings')).toBeInTheDocument());
    await waitFor(() => expect(screen.queryByText('RTO Settings')).toBeInTheDocument());
  });
});
