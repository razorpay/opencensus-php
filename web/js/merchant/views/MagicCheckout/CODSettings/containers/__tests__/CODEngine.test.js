import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import CODEngine from 'merchant/views/MagicCheckout/CODSettings/containers/CODEngine';
import {
  INITIAL_STATE,
  DB_FEE_RULE,
  DB_ZONE,
} from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';
import * as ModalActions from 'merchant_common/reducers/modals';

const openModalSpy = jest.spyOn(ModalActions, 'openModal');

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <CODEngine {...props} />
    </Provider>
  );
};

describe('COD Engine', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
  });

  test('should render cod engine settings view', () => {
    render(<App state={INITIAL_STATE} />);
    const Heading = screen.getByText('COD Settings');
    const SubHeading = screen.getByText(
      'Configure COD eligibility, rate, zones, product catalogues, fees',
    );
    const EngineDropdown = screen.getByRole('combobox');
    expect(Heading).toBeInTheDocument();
    expect(SubHeading).toBeInTheDocument();
    expect(EngineDropdown).toBeInTheDocument();
  });

  test('should render cod engine empty view', () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        configs: { ...INITIAL_STATE.magicCODEngine.configs, cod_engine: false },
      },
    };
    render(<App state={state} />);
    expect(screen.queryByText('No data to show')).toBeInTheDocument();
  });

  test('should render cod engine preview view', () => {
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
    const Tables = screen.getAllByRole('table');
    expect(Tables.length).toBe(2);
  });

  test('should verify validations', async () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        editMode: true,
        fee_rules: [],
        zones: [DB_ZONE],
      },
    };
    render(<App state={state} />);
    const saveBtn = screen.getByRole('button', { name: 'Save & apply' });
    expect(saveBtn).toBeInTheDocument();
    await userEvent.click(saveBtn);
    await waitFor(() => {
      expect(screen.getByText('Required')).toBeInTheDocument();
    });
  });

  test('should call save config action', async () => {
    const state = {
      ...INITIAL_STATE,
      magicCODEngine: {
        ...INITIAL_STATE.magicCODEngine,
        editMode: true,
        fee_rules: [DB_FEE_RULE],
        zones: [DB_ZONE],
      },
    };
    render(<App state={state} />);
    const saveBtn = screen.getByRole('button', { name: 'Save & apply' });
    expect(saveBtn).toBeInTheDocument();
    await userEvent.click(saveBtn);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });
});
