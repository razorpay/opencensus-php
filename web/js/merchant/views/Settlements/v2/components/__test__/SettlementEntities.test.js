import SettlementEntities from 'merchant/views/Settlements/v2/components/SettlementEntities';
import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';

import { screen, render, userEvent, checkIfComponentIsEmpty } from 'test-utils';

const breakupDetails = SettlementsDB.settlementTabBreakupDetails;

const defaultInitialState = {
  settlement: {
    breakupDetails,
  },
};

jest.mock('merchant/views/Settlements/v2/components/EntityList', () => ({
  __esModule: true,
  default: ({ activeTab }) => (
    <div>
      <p>Entity List</p>
      <p data-testid="active-tab">{activeTab}</p>
    </div>
  ),
}));

const defaultProps = {};

const renderApp = ({ props, initialState = defaultInitialState } = {}) =>
  render(<SettlementEntities {...defaultProps} {...props} />, { initialState });

describe('Settlement Entities', () => {
  test('should render the component', () => {
    renderApp();
    expect(screen.getByText('Entity List')).toBeInTheDocument();
    // Tabs
    expect(screen.getByText('Payment')).toBeInTheDocument();
    expect(screen.getByText('Refund')).toBeInTheDocument();
    expect(screen.getByTestId('active-tab')).toHaveTextContent(breakupDetails.items[0].component);
  });

  test('should change active tab on handleChange', async () => {
    renderApp();
    const reversalTab = screen.getByText('Refund');
    await userEvent.click(reversalTab);
    expect(screen.getByTestId('active-tab')).toHaveTextContent('refund');
  });

  test("should not show anything when there's an error", () => {
    renderApp({ initialState: { settlement: { breakupDetails: { error: true, items: [] } } } });
    checkIfComponentIsEmpty();
  });

  test("should show no transactions detected when there's no data", () => {
    renderApp({ initialState: { settlement: { breakupDetails: { loading: false, items: [] } } } });
    expect(
      screen.getByText('No transactions were detected for this settlement'),
    ).toBeInTheDocument();
  });

  test("should show spinner when there's no activeTab", () => {
    renderApp({ initialState: { settlement: { breakupDetails: { loading: true, items: [] } } } });
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
    expect(screen.getByTestId('loader-dots')).toBeInTheDocument();
  });
});
