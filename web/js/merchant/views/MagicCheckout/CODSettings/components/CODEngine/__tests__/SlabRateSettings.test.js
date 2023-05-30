import { screen, userEvent, render, waitFor } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import SlabRateSettings from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/SlabRateSettings';
import {
  DB_FEE_RULE,
  DB_ZONE,
  INITIAL_STATE,
} from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';

const openModalSpy = jest.spyOn(ModalActions, 'openModal');
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <SlabRateSettings {...props} />
    </Provider>
  );
};

describe('COD Engine', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
    showNotificationSpy.mockClear();
  });

  test('should render cod engine tabs', () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        editMode: false,
        fee_rules: [DB_FEE_RULE],
        zones: [DB_ZONE],
      },
    };
    render(<App state={state} />);
    const SlabRateSettings = screen.getByText('Cart order value');
    expect(SlabRateSettings).toBeInTheDocument();
  });

  test('should open slab modal when clicked on add more slabs', async () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        editMode: false,
        fee_rules: [],
        zones: [DB_ZONE],
      },
    };
    render(<App state={state} />);
    const addBtn = screen.getByText('+ Add slabs');
    expect(addBtn).toBeInTheDocument();
    await userEvent.click(addBtn);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  test('should open slab modal when clicked on create more slabs', async () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        editMode: false,
        fee_rules: [DB_FEE_RULE],
        zones: [DB_ZONE],
      },
    };
    render(<App state={state} />);
    const addBtn = screen.getByText('+ Create more slabs');
    expect(addBtn).toBeInTheDocument();
    await userEvent.click(addBtn);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  test('should open slab modal when clicked on create more slabs', async () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        editMode: false,
        fee_rules: [DB_FEE_RULE],
        zones: [DB_ZONE],
      },
    };
    render(<App state={state} />);
    const radioBtn = screen.getByRole('radio', { name: 'Free COD' });
    expect(radioBtn).toBeInTheDocument();
    expect(radioBtn).not.toBeChecked();
    await userEvent.click(radioBtn);
    expect(radioBtn).toBeChecked();
  });

  test('delete slab', async () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        editMode: false,
        fee_rules: [DB_FEE_RULE],
        zones: [DB_ZONE],
      },
    };
    const { container } = render(<App state={state} />);
    const deleteBtn = container.querySelector("[data-blade-component='icon-button']");
    expect(deleteBtn).toBeInTheDocument();
    await userEvent.click(deleteBtn);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });
});
