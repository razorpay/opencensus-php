import { screen, waitFor } from 'test-utils';
import {
  renderApp,
  routeTestData,
} from 'merchant/views/Transactions/v2/Landing/__tests__/mocks/fixtures/Landing';

describe('Landing', () => {
  test('should render Landing Analytics', () => {
    renderApp();
    expect(screen.getByText('LandingAnalytics')).toBeInTheDocument();
  });

  test.each(routeTestData)('should render %s on %s route', async (view, pathname) => {
    renderApp({ pathname });
    await waitFor(() => expect(screen.getByText(view)).toBeInTheDocument());
  });
});
