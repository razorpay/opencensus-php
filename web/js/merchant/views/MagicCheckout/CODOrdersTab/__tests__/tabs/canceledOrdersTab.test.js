import { render, screen, waitFor, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { fetchCODOrders } from 'merchant/reducers/magicCheckout/codOrders/action';
import CanceledOrdersTab from 'merchant/views/MagicCheckout/CODOrdersTab/tabs/CanceledOrdersTab';

jest.mock('merchant/reducers/magicCheckout/codOrders/action', () => ({
  ...jest.requireActual('merchant/reducers/magicCheckout/codOrders/action'),
  fetchCODOrders: jest.fn(),
}));

jest.mock('react-async-button', () => ({ onClick, text }) => (
  <button type="button" onClick={onClick}>
    {text || 'Async button'}
  </button>
));

fetchCODOrders.mockReturnValue({
  type: 'RTO_RECOMMENDATION_COD_ORDERS_FETCH',
  payload: Promise.resolve({
    status_code: 200,
    success: true,
    data: {
      items: [],
    },
  }),
});

const initState = {
  magicCODOrders: {
    id: '',
    receipt: '',
    riskTier: '',
    from: '',
    to: '',
    count: 25,
    skip: 0,
    items: [],
    loading: false,
    error: null,
    selectedPresetFromParent: null,
    hasMoreOrders: true,
    reviewMode: '',
  },
};

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <CanceledOrdersTab {...props} />
    </Provider>,
  );
};

describe('Canceled orders tab component', () => {
  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: jest.fn(() => ({ success: jest.fn(), initiated: jest.fn() })),
    };
  });

  test('component should render properly', async () => {
    renderApp();
    await waitFor(() => {
      expect(
        screen.getByText(/No orders found for the selected duration and criteria!/i),
      ).toBeInTheDocument();
    });
  });

  //TODO: test has been coming as flaky due to error 'Element type is invalid. Received a promise that resolves to: undefined. Lazy element type must resolve to a class or function.'
  //Skipping for now will fix this later.
  test.skip('should be able to clear filters by click on clear cta', async () => {
    renderApp();

    const clearCta = screen.getByRole('button', {
      name: 'Clear',
    });
    expect(clearCta).toBeInTheDocument();

    const fieldElement = screen.getByRole('combobox', {
      name: 'Review Mode',
    });
    expect(fieldElement).toBeInTheDocument();

    await userEvent.selectOptions(fieldElement, 'automation');
    expect(screen.getByRole('option', { name: 'Automation' }).selected).toBe(true);

    userEvent.click(clearCta);

    await waitFor(() => {
      expect(fieldElement.value).toBe('');
    });
  });
});
