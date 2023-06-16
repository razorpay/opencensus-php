import { render, screen, waitFor, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ConfigsDashboard from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { fetchPrepayConfigs } from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/__tests__/mocks/handlers';

import {
  INIT_STATE,
  MOCKED_SAVED_CONFIGS_PAYLOAD,
  MOCKED_UNSAVE_CONFIGS_PAYLOAD,
  ERROR_STATE,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/__tests__/mocks/fixtures';

jest.mock(
  'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/containers/CODPrepaidConfigs',
  () => () => {
    return (
      <div>
        <p>Add configs view</p>
      </div>
    );
  },
);

jest.mock(
  'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/containers/CODPrepaidSavedConfigs',
  () => () => {
    return (
      <div>
        <p>Saved configs view</p>
      </div>
    );
  },
);

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <ConfigsDashboard {...props} />
    </Provider>,
  );
};

describe('testing configuration dashboard', () => {
  const showNotification = jest.spyOn(NotificationsActions, 'showNotification');

  test('should show loader on the screen', () => {
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should show the add configs view', async () => {
    server.use(fetchPrepayConfigs(MOCKED_UNSAVE_CONFIGS_PAYLOAD));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Add configs view')).toBeInTheDocument();
    });
  });

  test('should show saved configs view', async () => {
    server.use(fetchPrepayConfigs(MOCKED_SAVED_CONFIGS_PAYLOAD));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Saved configs view')).toBeInTheDocument();
    });
  });

  test('should show error state if unable to fetch prepay configs', async () => {
    renderApp({ state: ERROR_STATE });
    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        className: 'magic-cod-prepaid-notification',
        type: 'error',
        message: 'Something went wrong, please try again after sometime.',
      });
    });
  });
});
