import React from 'react';

import Pos from 'merchant/views/POS';
import { render, waitFor, screen } from 'test-utils';

const renderApp = () => {
  return render(<Pos />, {
    initialEntries: ['/pos/catalog'],
  });
};

describe('<POS/>', () => {
  test.skip('should redirect to default route POS is rendered', async () => {
    const { history } = renderApp();
    await waitFor(() => {
      expect(history.location.pathname).toBe('/pos/catalog');
    });
  });

  test.skip('should render default container if routed to /pos/catalog', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByTestId('catalogue-container')).toBeVisible();
    });
  });
});
