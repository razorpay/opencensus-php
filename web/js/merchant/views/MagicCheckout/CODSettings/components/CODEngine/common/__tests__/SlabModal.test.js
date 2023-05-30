import { screen, render, userEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import SlabModal from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SlabModal';
import {
  DB_FEE_RULE,
  DB_COUNTRIES,
} from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';

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
    fee_rules: [DB_FEE_RULE],
    zones: [DB_COUNTRIES],
    validations: {
      fee_rules: true,
      zones: true,
    },
  },
};

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <SlabModal {...props} />
    </Provider>
  );
};

describe('COD Engine', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
    closeModalSpy.mockClear();
  });

  test('should render slab modal', () => {
    render(<App />);
    const SlabModal = screen.getByTestId('slab-modal');
    expect(SlabModal).toBeInTheDocument();
    expect(screen.getAllByRole('spinbutton')).toHaveLength(6);
    expect(screen.getByText('+ Add rate slabs')).toBeInTheDocument();
  });

  test('should add new slabs', async () => {
    render(<App />);
    const addSlabBtn = screen.getByText('+ Add rate slabs');
    await userEvent.type(screen.getAllByRole('spinbutton')[4], '200');
    expect(screen.getAllByRole('spinbutton')[4]).toHaveValue(200);
    await userEvent.type(screen.getAllByRole('spinbutton')[5], '300');
    expect(screen.getAllByRole('spinbutton')[5]).toHaveValue(300);
    await userEvent.click(addSlabBtn);
    expect(screen.getAllByRole('spinbutton')).toHaveLength(9);
  });

  test('should error if slab in range already exists for lte', async () => {
    render(<App />);
    await userEvent.type(screen.getAllByRole('spinbutton')[4], '102');
    expect(screen.getAllByRole('spinbutton')[4]).toHaveValue(102);
    await userEvent.type(screen.getAllByRole('spinbutton')[5], '300');
    expect(screen.getAllByRole('spinbutton')[5]).toHaveValue(300);
    await userEvent.clear(screen.getAllByRole('spinbutton')[1]);
    await userEvent.type(screen.getAllByRole('spinbutton')[1], '101');
    expect(screen.getAllByRole('spinbutton')[1]).toHaveValue(101);
    expect(screen.getByText('Invalid value')).toBeInTheDocument();
  });

  test('should error if slab in range already exists for gte', async () => {
    render(<App />);
    await userEvent.type(screen.getAllByRole('spinbutton')[4], '200');
    expect(screen.getAllByRole('spinbutton')[4]).toHaveValue(200);
    await userEvent.clear(screen.getAllByRole('spinbutton')[3]);
    await userEvent.type(screen.getAllByRole('spinbutton')[3], '201');
    expect(screen.getAllByRole('spinbutton')[3]).toHaveValue(201);
    expect(screen.getByText('Invalid value')).toBeInTheDocument();
  });

  test('should error if negative value is entered', async () => {
    render(<App />);
    await userEvent.type(screen.getAllByRole('spinbutton')[4], '-200');
    expect(screen.getAllByRole('spinbutton')[4]).toHaveValue(-200);
    expect(screen.getByText('Invalid value')).toBeInTheDocument();
  });

  test('should save slab', async () => {
    render(<App />);
    await userEvent.type(screen.getAllByRole('spinbutton')[4], '200');
    await userEvent.type(screen.getAllByRole('spinbutton')[5], '300');
    const saveBtn = screen.getByTestId('save-slab');
    expect(saveBtn).toBeInTheDocument();
    await userEvent.click(saveBtn);
    expect(showNotificationSpy).toHaveBeenCalled();
    expect(closeModalSpy).toHaveBeenCalled();
  });
});
