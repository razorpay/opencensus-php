import { screen, waitFor } from 'test-utils';
import {
  renderApp,
  routeTestData,
  overviewRouteTestData,
} from 'merchant/views/Transactions/v2/EntitiesOverview/__tests__/mocks/fixtures/EntitiesOverview';

describe('EntitiesOverview', () => {
  test.each(overviewRouteTestData)(
    'should render Entity Analytics on %s route',
    async (pathname) => {
      renderApp({ pathname });
      await waitFor(() => expect(screen.getByText('EntityAnalytics')).toBeInTheDocument());
    },
  );

  test.each(routeTestData)('should render %s on %s route', async (view, pathname) => {
    renderApp({ pathname });
    await waitFor(() => expect(screen.getByText(view)).toBeInTheDocument());
  });
});
