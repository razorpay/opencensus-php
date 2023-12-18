import { createMemoryHistory } from 'history';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import StreaksReward from 'merchant/views/AccountAndSettings/Rewards';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

describe('Tests for the StreaksReward page', () => {
  const renderApp = () => {
    const history = createMemoryHistory({ initialEntries: [ROUTES_INFO.STREAK_REWARD] });

    return render(<StreaksReward location={history.location} mode="live" />, {
      path: ROUTES_INFO.STREAK_REWARD,
      initialEntries: [ROUTES_INFO.STREAK_REWARD],
    });
  };

  test('Location should change to StreaksReward url when clicking the StreaksReward tab', async () => {
    const { history } = renderApp();

    await userEvent.click(screen.getByTestId('flex-link'));

    expect(history?.location?.pathname).toBe(ROUTES_INFO.STREAK_REWARD);
  });
});
