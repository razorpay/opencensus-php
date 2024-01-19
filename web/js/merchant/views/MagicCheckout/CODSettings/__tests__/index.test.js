import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import { Provider } from 'react-redux';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { storeWithInitialState } from 'merchant/store';
import CODSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/CODSettingsTab';
import { UPDATE_WOOC_PLUGIN_MSG } from 'merchant/views/MagicCheckout/Settings/constants';

const initState = {
  magic_settings: {
    platform: 'shopify',
    cod_engine: true,
    cod_engine_type: 'location',
  },
  magicCODEngine: {
    loading: {
      summary: false,
      fee_rules: false,
      zones: false,
      item_categories: false,
      mapping: false,
    },
    error: {},
    configs: {
      cod_engine: true,
      cod_engine_type: 'slab_charges',
      shop_id: 'magic-checkout-test-store-1',
      engine: 'Basic',
      rate_slabs: true,
    },
    fee_rules: [],
    zones: [],
    validations: {
      fee_rules: true,
      zones: true,
    },
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <BladeProvider themeTokens={paymentTheme}>
        <CODSettingsTab {...props} />
      </BladeProvider>
    </Provider>
  );
};

describe('COD Engine', () => {
  test('should render cod engine tabs', async () => {
    render(<App />);

    //should not show update plugin message if platform isn't woocommerce
    expect(screen.queryByText(UPDATE_WOOC_PLUGIN_MSG)).not.toBeInTheDocument();

    const CODEngineTab = await screen.findByText('Magic COD');
    const BlockListTab = await screen.findByText('Block List');
    const AllowListTab = await screen.findByText('Allow List');

    expect(CODEngineTab).toBeInTheDocument();
    expect(BlockListTab).toBeInTheDocument();
    expect(AllowListTab).toBeInTheDocument();
  });

  test('should show update plugin message if platform is woocommerce', () => {
    const customState = {
      ...initState,
      magic_settings: {
        ...initState.magic_settings,
        platform: 'woocommerce',
      },
    };
    render(<App state={customState} />);
    expect(screen.getByText(UPDATE_WOOC_PLUGIN_MSG)).toBeInTheDocument();
  });

  test('should not show allowlist tab if engine is not basic', async () => {
    const customState = {
      ...initState,
      magicCODEngine: {
        ...initState.magicCODEngine,
        configs: {
          ...initState.magicCODEngine.configs,
          engine: 'Advance',
        },
      },
    };

    render(<App state={customState} />);
    const AllowListTab = await screen.findByText('Allow List');
    expect(AllowListTab).toBeInTheDocument();
  });
});
