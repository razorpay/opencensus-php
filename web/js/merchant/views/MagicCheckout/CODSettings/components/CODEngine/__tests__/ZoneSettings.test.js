import { screen, render, waitFor, userEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ZoneSettings from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/ZoneSettings';
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
      <ZoneSettings {...props} />
    </Provider>
  );
};

describe('Zone settings', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
  });

  test('should render zone settings', async () => {
    render(<App />);
    await waitFor(() => {
      const ZoneSettings = screen.getByText('COD zones');
      expect(ZoneSettings).toBeInTheDocument();
      expect(screen.queryByText('+ Create more zones')).toBeInTheDocument();
    });
  });

  test('should open slab modal when clicked on add more slabs', async () => {
    render(<App />);
    const addBtn = screen.getByText('+ Create more zones');
    expect(addBtn).toBeInTheDocument();
    await userEvent.click(addBtn);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  test('delete zone', async () => {
    const { container } = render(<App />);
    const deleteBtn = container.querySelector("[data-blade-component='icon-button']");
    expect(deleteBtn).toBeInTheDocument();
    await userEvent.click(deleteBtn);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });
});
