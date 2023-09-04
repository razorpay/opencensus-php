import { screen, render } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import SettingsView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/SettingsView';
import {
  DB_FEE_RULE,
  DB_COUNTRIES,
} from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';
import * as ModalActions from 'merchant_common/reducers/modals';

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
    fee_rules: [DB_FEE_RULE],
    zones: [DB_COUNTRIES],
    validations: {
      fee_rules: true,
      zones: true,
    },
  },
};
const openModalSpy = jest.spyOn(ModalActions, 'openModal');

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <SettingsView {...props} />
    </Provider>
  );
};

describe('Setting view', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
  });

  test('should render settings with basic and advanced in dropdown', () => {
    render(<App />);
    const SettingsView = screen.getByText('Type of setting');
    expect(SettingsView).toBeInTheDocument();
    const options = screen.getAllByRole('option');
    expect(screen.queryByText('Advanced')).toBeInTheDocument();
    expect(options.length).toBe(2);
  });
});
