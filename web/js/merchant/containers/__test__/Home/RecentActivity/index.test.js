import RecentActivity from 'merchant/containers/Home/RecentActivity/index';
import store from 'merchant/store';
import { render, waitFor, userEvent, screen } from 'test-utils';
import merge from 'lodash/merge';
import { fetchSettlements } from 'merchant/reducers/collection';
import { settlementMockRes } from 'merchant/views/Settlements/__test__/data/SettlementsDB';

jest.mock('merchant/reducers/collection', () => ({
  ...jest.requireActual('merchant/reducers/collection'),
  fetchSettlements: jest.fn(),
}));

fetchSettlements.mockReturnValue({
  type: 'SETTLEMENTS_FETCH',
  payload: Promise.resolve(settlementMockRes),
});

const globalState = store.getState();
const user = {
  ...globalState.session.user,
  contact_name: 'Test Merchant',
};

const getInitialState = ({ userDetails = {} } = {}) => {
  return {
    ...globalState,
    session: {
      ...globalState.session,
      user: merge(
        {
          ...globalState.session.user,
          ...user,
        },
        userDetails,
      ),
    },
  };
};

jest.spyOn(window, 'close').mockImplementation(jest.fn());

const renderApp = ({ props = {}, initialState }) =>
  render(<RecentActivity {...props} />, {
    initialState,
  });

describe('test for RecentActivity component', () => {
  test('should render RM as currency symbol when currency passed is MYR', async () => {
    const props = { settlementCurrency: 'MYR' };
    const updatedState = getInitialState();
    renderApp({ props, initialState: updatedState });
    const settlementTabBtn = screen.getByText('SETTLEMENTS');
    await waitFor(() => {
      userEvent.click(settlementTabBtn);
      const currencySymbols = screen.getAllByText('RM');
      const currencySymbol = currencySymbols[1];
      expect(currencySymbol).toHaveTextContent('RM');
    });
  });
});
