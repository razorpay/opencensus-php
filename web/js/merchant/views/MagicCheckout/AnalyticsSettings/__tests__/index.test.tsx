import React from 'react';
import { render, screen, waitFor, server, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import AnalyticsSettings from 'merchant/views/MagicCheckout/AnalyticsSettings';

import { fetchAnalyticsSettings } from 'merchant/views/MagicCheckout/AnalyticsSettings/__tests__/mocks/handler';

import { DEFAULT_CONFIGS } from 'merchant/views/MagicCheckout/AnalyticsSettings/__tests__/mocks/fixtures';

const INIT_STATE = {
  magic_settings: {
    platform: 'woocommerce',
  },
  magicAnalyticsSettings: {
    isLoading: {
      authConfigs: false,
    },
  },
};

const renderApp = ({ state = {}, ...props }: Record<string, any> = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <AnalyticsSettings {...props} />
    </Provider>,
  );
};

describe('testing analytics settings', () => {
  test('should show spinner', () => {
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should render component properly', async () => {
    server.use(fetchAnalyticsSettings(DEFAULT_CONFIGS));
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Analytics Settings')).toBeInTheDocument();
    });

    expect(screen.queryByText('Google Ads')).not.toBeInTheDocument();

    userEvent.click(screen.getByText('Facebook Ads'));
    await waitFor(() => {
      expect(screen.getAllByText('Facebook Ads').length).toBe(2);
    });
  });
});
