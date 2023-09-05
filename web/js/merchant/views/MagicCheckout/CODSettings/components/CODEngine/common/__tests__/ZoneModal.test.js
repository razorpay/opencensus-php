import { screen, render, userEvent, waitFor } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ZoneModal from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/ZoneModal';
import {
  DB_FEE_RULE,
  DB_COUNTRIES,
  DB_ZONE,
} from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';
import { MODAL_MODES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';

// import { fetchSummaryWithoutSlabsAndZones } from './mocks/handlers';

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
    zones: [DB_ZONE],
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
      <ZoneModal allcountries={DB_COUNTRIES} {...props} />
    </Provider>
  );
};

jest.setTimeout(35000);

describe('COD Engine', () => {
  // mocking react virtualized - https://stackoverflow.com/a/62214834
  const originalOffsetHeight = Object.getOwnPropertyDescriptor(
    HTMLElement.prototype,
    'offsetHeight',
  );
  const originalOffsetWidth = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'offsetWidth');

  beforeAll(() => {
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', { configurable: true, value: 50 });
    Object.defineProperty(HTMLElement.prototype, 'offsetWidth', { configurable: true, value: 50 });
  });

  afterAll(() => {
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', originalOffsetHeight);
    Object.defineProperty(HTMLElement.prototype, 'offsetWidth', originalOffsetWidth);
  });

  beforeEach(() => {
    showNotificationSpy.mockClear();
    closeModalSpy.mockClear();
  });

  test('should render zone modal', async () => {
    render(<App />);
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    expect(screen.queryAllByTestId('zone-item')).toHaveLength(6);
  });

  test('should check item in zone', async () => {
    render(<App />);
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const checkbox = screen.getByRole('checkbox', { name: 'India' });
    expect(checkbox).not.toBeChecked();
    await userEvent.click(checkbox);
    expect(checkbox).toBeChecked();
  });

  test('should check unchecked items when India is selected', async () => {
    render(<App />);
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const checkbox = screen.getByRole('checkbox', { name: 'Assam' });
    const indiaCheckbox = screen.getByRole('checkbox', { name: 'India' });
    expect(indiaCheckbox).not.toBeChecked();
    expect(checkbox).not.toBeChecked();
    await userEvent.click(indiaCheckbox);
    expect(indiaCheckbox).toBeChecked();
    expect(checkbox).toBeChecked();
  });

  test('should uncollapse states', async () => {
    render(<App />);
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const statesToggle = screen.getByText('0 of 5 states');
    expect(screen.queryByRole('checkbox', { name: 'Bihar' })).toBeInTheDocument();
    await userEvent.click(statesToggle);
    expect(screen.queryByRole('checkbox', { name: 'Bihar' })).not.toBeInTheDocument();
  });

  test('should filter countries based on search', async () => {
    render(<App />);
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const searchInput = screen.getByTestId('search-input');
    await userEvent.type(searchInput, 'Bihar');
    await waitFor(
      () => {
        expect(screen.queryByRole('checkbox', { name: 'Bihar' })).toBeInTheDocument();
        expect(screen.queryByRole('checkbox', { name: 'Assam' })).not.toBeInTheDocument();
      },
      { timeout: 10000 },
    );
    await userEvent.clear(searchInput);
    await waitFor(
      () => {
        expect(screen.queryByRole('checkbox', { name: 'Assam' })).toBeInTheDocument();
      },
      { timeout: 10000 },
    );
  });

  test('should mark parent as indeterminate', async () => {
    render(<App />);
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const searchInput = screen.getByTestId('search-input');
    await userEvent.type(searchInput, 'India');
    await waitFor(
      async () => {
        const checkbox = screen.queryByRole('checkbox', { name: 'Bihar' });
        expect(checkbox).toBeInTheDocument();
        await userEvent.click(checkbox);
        expect(screen.queryByRole('checkbox', { name: 'India' })).toBePartiallyChecked();
      },
      { timeout: 2500 },
    );
  });

  test('should save zone', async () => {
    render(<App mode={MODAL_MODES.EDIT} id={DB_ZONE.id} />);
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const nameInput = screen.getByTestId('search-input');
    await userEvent.type(nameInput, 'New Zone');
    await waitFor(async () => {
      expect(nameInput).toHaveValue('New Zone');
      const saveBtn = screen.getByTestId('confirm-button');
      await userEvent.click(saveBtn);
      expect(showNotificationSpy).toHaveBeenCalled();
      expect(closeModalSpy).toHaveBeenCalled();
    });
  });
});
