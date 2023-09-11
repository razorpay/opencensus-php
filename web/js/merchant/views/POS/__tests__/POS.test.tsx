import React from 'react';
import { Router } from 'react-router-dom';

import Pos from 'merchant/views/POS';
import { createMemoryHistory } from 'history';
import { render, waitFor, screen } from 'test-utils';

const renderApp = () => {
  const history = createMemoryHistory({ initialEntries: ['/pos'] });
  const match = {
    params: {
      page: '',
    },
  };
  render(
    <Router history={history}>
      <Pos location={history.location} history={history} match={match} />
    </Router>,
  );

  return { history };
};

describe('<POS/>', () => {
  test('should redirect to default route POS is rendered', async () => {
    const { history } = renderApp();
    await waitFor(() => {
      expect(history.location.pathname).toBe('/pos/catalog');
    });
  });

  test('should render default container if routed to /pos/catalog', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByTestId('catalogue-container')).toBeVisible();
    });
  });
});
