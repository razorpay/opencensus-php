import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import GoogleAccount from 'merchant/views/MagicCheckout/AnalyticsSettings/common/GoogleAccount';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { OAUTH_CONFIGS } from 'merchant/views/MagicCheckout/AnalyticsSettings/__tests__/mocks/fixtures';

const renderApp = ({ state = {}, ...props }: Record<string, any> = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <GoogleAccount {...props} />
    </Provider>,
  );
};

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

describe('testing Google account component', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  test('should render properly', () => {
    renderApp({ oAuthAccountConfigs: OAUTH_CONFIGS.accounts });

    expect(screen.getByText('Google Account')).toBeInTheDocument();
    expect(screen.getByText('test@gmail.com')).toBeInTheDocument();
  });

  test('should be able to click on edit and back cta', async () => {
    renderApp({ oAuthAccountConfigs: OAUTH_CONFIGS.accounts });

    const editCta = screen.getByText('Edit');
    await userEvent.click(editCta);

    expect(screen.queryByText('test@gmail.com')).not.toBeInTheDocument();

    await userEvent.click(editCta);
    expect(screen.queryByText('test@gmail.com')).toBeInTheDocument();
  });

  test('should be able to click on sing in with google cta', async () => {
    renderApp({ oAuthAccountConfigs: OAUTH_CONFIGS.accounts });

    const editCta = screen.getByText('Edit');
    await userEvent.click(editCta);

    const signInCta = screen.getByText('Sign in with Google');

    await userEvent.click(signInCta);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});
