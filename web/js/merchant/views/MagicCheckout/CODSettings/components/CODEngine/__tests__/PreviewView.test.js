import { screen, render } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import PreviewView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/PreviewView';
import {
  DB_FEE_RULE,
  DB_COUNTRIES,
} from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';

const initState = {
  magic_settings: {
    platform: 'shopify',
    cod_engine: true,
    cod_engine_type: 'slab_charges',
    rcodEnabled: false,
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
    fee_rules: [DB_FEE_RULE],
    zones: [DB_COUNTRIES],
    validations: {
      fee_rules: true,
      zones: true,
    },
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <PreviewView {...props} />
    </Provider>
  );
};

describe('PreviewView', () => {
  test('setting type is not shown when rcod app is active', () => {
    const customState = {
      magic_settings: {
        ...initState.magic_settings,
        rcodEnabled: true,
      },
    };

    render(<App state={customState} />);
    expect(screen.queryByText('Type of setting')).not.toBeInTheDocument();
  });

  test('basic settings preview is rendered', () => {
    render(<App />);
    expect(screen.getByText('COD eligibility slabs & fee')).toBeInTheDocument();
  });

  test('zones section is not shown when rcod app is active', () => {
    const customState = {
      magic_settings: {
        ...initState.magic_settings,
        rcodEnabled: true,
      },
    };

    render(<App state={customState} />);
    expect(screen.queryByText('COD eligibility zones')).not.toBeInTheDocument();
  });
});
