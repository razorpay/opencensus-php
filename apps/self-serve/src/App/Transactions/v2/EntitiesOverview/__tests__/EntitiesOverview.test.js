// TODO: Fix the test properly, currently it's skipped in master

import { screen, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import {
  renderApp,
  routeTestData,
  overviewRouteTestData,
} from 'apps/self-serve/src/App/Transactions/v2/EntitiesOverview/__tests__/mocks/fixtures/EntitiesOverview';

describe.skip('EntitiesOverview', () => {
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
