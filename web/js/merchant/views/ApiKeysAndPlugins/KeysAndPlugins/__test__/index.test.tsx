import React from 'react';
import { render, server, userEvent, waitFor } from 'test-utils';
import { rest } from 'msw';
import KeysAndPlugins from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/index';
import { Provider } from 'react-redux';
import merge from 'lodash/merge';
import store, { storeWithInitialState } from 'merchant/store';
import { getKeys, PLATFORM_LINKS, SUPPORTED_PLUGINS } from './mocks/fixtures';
import { Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import {
  INTEGRATION_TITLE,
  PLATFORM_TITLE,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
// import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as analytics from 'common/utils/analytics';

// let showNotificationSpy;
let analyticsTrackSpy;

const globalState = store.getState();

const getInitialState = ({ userDetails = {}, keyDetails = {}, pluginDetails = {} }) => {
  return {
    session: {
      ...globalState.session,
      user: merge(
        {
          ...globalState.session.user,
        },
        userDetails,
      ),
    },
    keys: merge(
      {
        loading: false,
        isLoaded: true,
        keys: getKeys('test'),
      },
      keyDetails,
    ),
    plugins: merge(
      {
        supported: {
          loading: false,
          items: SUPPORTED_PLUGINS,
        },
        details: {
          items: {
            [PLATFORM_LINKS.SUCCESS.business_website]: {
              website: PLATFORM_LINKS.SUCCESS.business_website,
              merchant_selected_plugin: 'Shopify',
              suggested_plugin: 'Wix',
            },
          },
        },
      },
      pluginDetails,
    ),
  };
};

describe('Keys and Plugins Section', () => {
  const renderApp = ({ initialState = {}, props = {} } = {}) =>
    render(
      <Provider store={storeWithInitialState(initialState)}>
        <KeysAndPlugins {...props} />
      </Provider>,
      {
        initialState,
      },
    );

  beforeAll(() => {
    // showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
    analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');
  });
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should show spinner if plugins are loading', () => {
    const initialState = getInitialState({ pluginDetails: { supported: { loading: true } } });
    const { getByTestId } = renderApp({ initialState });
    expect(getByTestId('spinner')).toBeVisible();
  });

  test('should show spinner if merchantPlugin is loading', () => {
    const initialState = getInitialState({ pluginDetails: { details: { loading: true } } });
    const { getByTestId } = renderApp({ initialState });
    expect(getByTestId('spinner')).toBeVisible();
  });

  test.each([
    [
      Platform.WEBSITE,
      {
        business_website: '',
        playstore_url: '',
        appstore_url: '',
      },
    ],
    [
      Platform.WEBSITE,
      {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
        playstore_url: PLATFORM_LINKS.SUCCESS.playstore_url,
        appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      },
    ],
    [
      Platform.ANDROID,
      {
        business_website: '',
        playstore_url: PLATFORM_LINKS.SUCCESS.playstore_url,
        appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      },
    ],
    [
      Platform.IOS,
      {
        business_website: '',
        playstore_url: '',
        appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      },
    ],
  ])('should show active tab as %s if present', (platform, userDetails) => {
    const initialState = getInitialState({ userDetails });

    const { getByTestId } = renderApp({ initialState });
    expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[platform]);
  });

  test('should change tab on click', async () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
    });
    const { getByTestId, getAllByTestId } = renderApp({ initialState });

    expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[Platform.WEBSITE]);
    await userEvent.click(getAllByTestId('tab')[0]);
    expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[Platform.ANDROID]);
  });

  test('should show add link option if link not present', () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: '',
        playstore_url: '',
        appstore_url: '',
      },
    });
    const { getByTestId, getByRole } = renderApp({ initialState });
    expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[Platform.WEBSITE]);
    expect(getByRole('button', { name: /add link/i })).toBeEnabled();
  });

  test('should show selected plugin if plugin if present', () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
    });

    const { getByTestId, getByText } = renderApp({ initialState });
    expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[Platform.WEBSITE]);
    expect(getByText(/integrate with shopify/i)).toBeVisible();
  });

  test('should show suggested plugin if present', () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
      pluginDetails: {
        details: {
          items: {
            [PLATFORM_LINKS.SUCCESS.business_website]: {
              website: PLATFORM_LINKS.SUCCESS.business_website,
              merchant_selected_plugin: null,
              suggested_plugin: 'Wix',
            },
          },
        },
      },
    });

    const { getByTestId, getByText } = renderApp({ initialState });
    expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[Platform.WEBSITE]);
    expect(getByText(/integrate with wix/i)).toBeVisible();
  });

  test('should not show suggested plugin if it is not supported', () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
      pluginDetails: {
        details: {
          items: {
            [PLATFORM_LINKS.SUCCESS.business_website]: {
              website: PLATFORM_LINKS.SUCCESS.business_website,
              merchant_selected_plugin: null,
              suggested_plugin: 'OpenCart',
            },
          },
        },
      },
    });
    const { getByTestId, getByText } = renderApp({ initialState });
    expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[Platform.WEBSITE]);
    const title = `Integrate with ${INTEGRATION_TITLE[Platform.WEBSITE]}`;
    expect(getByText(new RegExp(title, 'i'))).toBeVisible();
  });

  test.each([Platform.ANDROID, Platform.IOS])(
    'should not show plugin select if platform is %s',
    (platform) => {
      const initialState = getInitialState({
        userDetails: {
          business_website: '',
          playstore_url: platform === Platform.ANDROID ? PLATFORM_LINKS.SUCCESS.playstore_url : '',
          appstore_url: platform === Platform.IOS ? PLATFORM_LINKS.SUCCESS.appstore_url : '',
        },
        pluginDetails: {
          details: {
            items: {
              [PLATFORM_LINKS.SUCCESS.business_website]: {
                website: PLATFORM_LINKS.SUCCESS.business_website,
                merchant_selected_plugin: null,
                suggested_plugin: 'Wix',
              },
            },
          },
        },
      });

      const { getByTestId, queryByText } = renderApp({ initialState });
      expect(getByTestId('active-tab').textContent).toMatch(PLATFORM_TITLE[platform]);
      expect(queryByText(/website platform/i)).not.toBeInTheDocument();
    },
  );

  test('should show None of the above plugin if merchant selected plugin is empty string', async () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
      pluginDetails: {
        details: {
          items: {
            [PLATFORM_LINKS.SUCCESS.business_website]: {
              website: PLATFORM_LINKS.SUCCESS.business_website,
              merchant_selected_plugin: '',
              suggested_plugin: null,
            },
          },
        },
      },
    });

    const { getByTestId } = renderApp({ initialState });

    await waitFor(() => {
      expect(getByTestId('plugin-selected-None of the above')).toBeVisible();
    });
  });

  // TODO: fix this test, it's flaky
  test.skip('should send empty string plugin if None of the above plugin selected', async () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
      pluginDetails: {
        details: {
          items: {
            [PLATFORM_LINKS.SUCCESS.business_website]: {
              website: PLATFORM_LINKS.SUCCESS.business_website,
              merchant_selected_plugin: 'Shopify',
              suggested_plugin: null,
            },
          },
        },
      },
    });

    const { getByTestId, getByText } = renderApp({ initialState });
    expect(getByText(/website platform/i)).toBeVisible();

    await userEvent.click(getByTestId('plugin-selected-Shopify'));

    await waitFor(() => {
      expect(getByTestId('plugin-option-None of the above')).toBeVisible();
    });
    await userEvent.click(getByTestId('plugin-option-None of the above'));

    await waitFor(() => {
      expect(getByTestId('plugin-selected-None of the above')).toBeVisible();
    });
  });

  test.skip('should not trigger event if same plugin is selected again', async () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
    });

    const { getByTestId, getByText } = renderApp({ initialState });
    expect(getByText(/website platform/i)).toBeVisible();

    await userEvent.click(getByTestId('plugin-selected-Shopify'));

    await waitFor(() => {
      expect(getByTestId('plugin-option-Shopify')).toBeVisible();
    });
    await userEvent.click(getByTestId('plugin-option-Shopify'));
    expect(analyticsTrackSpy).toHaveBeenCalledTimes(0);
  });

  test.skip('should show success notification on plugin save', async () => {
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
      pluginDetails: {
        details: {
          items: {
            [PLATFORM_LINKS.SUCCESS.business_website]: {
              website: PLATFORM_LINKS.SUCCESS.business_website,
              merchant_selected_plugin: null,
              suggested_plugin: null,
            },
          },
        },
      },
    });

    const { getByTestId, getByText } = renderApp({ initialState });
    expect(getByText(/website platform/i)).toBeVisible();
    await userEvent.click(getByText(/select platform/i));

    await waitFor(() => {
      expect(getByTestId('plugin-option-Shopify')).toBeVisible();
    });
    await userEvent.click(getByTestId('plugin-option-Shopify'));
  });

  test('should show error if selected plugin is not saved', async () => {
    server.use(
      rest.post('*/merchant/api/:mode/onboarding/merchants/:mechantId/plugin', (req, res, ctx) => {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        return res(ctx.errors(['Some error occurred']), ctx.delay(50));
      }),
    );

    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
      },
      pluginDetails: {
        details: {
          items: {
            [PLATFORM_LINKS.SUCCESS.business_website]: {
              website: PLATFORM_LINKS.SUCCESS.business_website,
              merchant_selected_plugin: null,
              suggested_plugin: null,
            },
          },
        },
      },
    });

    const { getByTestId, getByText } = renderApp({ initialState });
    await userEvent.click(getByText(/select platform/i));

    await waitFor(() => {
      expect(getByTestId('plugin-option-Wix')).toBeVisible();
    });

    await userEvent.click(getByTestId('plugin-option-Wix'));
  });
});
