import { screen } from 'test-utils';
import {
  renderApp,
  routeTestData,
} from 'merchant/views/Transactions/v2/__tests__/mocks/fixtures/TransactionsContainer';

describe.skip('TransactionsContainer', () => {
  test.each(routeTestData)('should render the %s view on %s route', (view, pathname) => {
    renderApp({ pathname });
    expect(screen.getByText(view)).toBeInTheDocument();
  });
});
