import React from 'react';
import { render, screen, waitFor, server, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import GoogleAnalytics from 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAnalytics';

import { saveEventConfigs } from 'merchant/views/MagicCheckout/AnalyticsSettings/__tests__/mocks/handler';

import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import {
  GOOGLE_ANALYTICS_CONFIGS,
  DEFAULT_CONFIGS,
  GOOGLE_ANALYTICS_EVENT_CONFIGS,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/__tests__/mocks/fixtures';

const renderApp = ({ state = {}, ...props }: Record<string, any> = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <GoogleAnalytics {...props} />
    </Provider>,
  );
};

const openModalSpy = jest.spyOn(ModalActions, 'openModal');
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

describe('testing google analytics component', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
    showNotificationSpy.mockClear();
  });

  test('should render component', () => {
    renderApp({ analyticsSettingsConfigs: { merchantAnalyticsConfigs: GOOGLE_ANALYTICS_CONFIGS } });

    expect(screen.getByText('its a secret')).toBeInTheDocument();
  });

  test('should be able to click on add account CTA', async () => {
    renderApp({ analyticsSettingsConfigs: { merchantAnalyticsConfigs: GOOGLE_ANALYTICS_CONFIGS } });

    const addAccountCta = screen.getByRole('button', {
      name: 'Add account',
    });

    const saveEventsCta = screen.queryByRole('button', {
      name: 'Save events',
    });

    expect(saveEventsCta).not.toBeInTheDocument();
    await userEvent.click(addAccountCta);

    //checking image count as for each account addition there would be a card display having account image in header
    expect(screen.getAllByRole('img', { name: 'analytics-icon' }).length).toBe(3);
  });

  test('should be able to save configs', async () => {
    server.use(saveEventConfigs(GOOGLE_ANALYTICS_EVENT_CONFIGS));

    renderApp({ analyticsSettingsConfigs: { merchantAnalyticsConfigs: DEFAULT_CONFIGS } });

    const fieldElement = screen.getByTestId('integration-type') as HTMLOptionElement;

    await userEvent.selectOptions(fieldElement, 'frontend');

    const selectedOption = screen.getByRole('option', {
      name: 'GTag (Frontend)',
    }) as HTMLOptionElement;

    expect(selectedOption.selected).toBe(true);

    userEvent.click(screen.getByRole('button', { name: 'Save events' }));
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });

  test('should be able to delete configs', async () => {
    renderApp({ analyticsSettingsConfigs: { merchantAnalyticsConfigs: GOOGLE_ANALYTICS_CONFIGS } });

    const removeCta = screen.getAllByRole('img', { name: 'trash-outline' });
    expect(removeCta.length).toBe(2);

    userEvent.click(removeCta[1]);

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  test('should be able to click on edit analytics events', async () => {
    renderApp({ analyticsSettingsConfigs: { merchantAnalyticsConfigs: GOOGLE_ANALYTICS_CONFIGS } });

    expect(screen.queryByText('Save events')).not.toBeInTheDocument();

    const editCta = screen.getByTestId('edit-icon');

    userEvent.click(editCta);
    await waitFor(() => {
      expect(screen.getByText('Save events')).toBeInTheDocument();
    });
  });
});
