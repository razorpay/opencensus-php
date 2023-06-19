import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import { Provider } from 'react-redux';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { storeWithInitialState } from 'merchant/store';
import CODSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/CODSettingsTab';

const initState = {
  magic_settings: {
    platform: 'shopify',
    cod_engine: true,
    cod_engine_type: 'location',
  },
  magicCODEngine: {
    loading: false,
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
    const CODEngineTab = await screen.findByText('COD Engine');
    const BlockListTab = await screen.findByText('Block List');
    expect(CODEngineTab).toBeInTheDocument();
    expect(BlockListTab).toBeInTheDocument();
  });
});
