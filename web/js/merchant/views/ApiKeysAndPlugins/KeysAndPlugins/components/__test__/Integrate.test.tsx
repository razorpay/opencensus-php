import React from 'react';
import { render, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { Integrate } from '..';
import {
  PLATFORM_LINKS,
  SUPPORTED_PLUGINS,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/__test__/mocks/fixtures';
import { Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import {
  INTEGRATION_GUIDE,
  INTEGRATION_TITLE,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import * as analytics from 'common/utils/analytics';

let analyticsSpy;
const initialState = {
  session: {
    user: {
      business_website: PLATFORM_LINKS.SUCCESS.business_website,
    },
  },
  plugins: {
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
};

describe('API Keys & Plugins - Integrate', () => {
  const renderApp = ({ initialState, ...rest }) =>
    render(
      <Provider store={storeWithInitialState(initialState)}>
        <Integrate
          selectedPlugin={SUPPORTED_PLUGINS.Shopify.name}
          selectedPlatform={Platform.WEBSITE}
          {...rest}
        />
      </Provider>,
    );

  beforeAll(() => {
    analyticsSpy = jest.spyOn(analytics, 'analyticsTrack');
  });

  beforeEach(() => {
    analyticsSpy.mockClear();
  });

  test('should render component without errors', () => {
    expect(() => renderApp({ initialState: {} })).not.toThrowError();
  });

  test.each(Object.values(Platform))(
    'should show correct %s platform title if plugin is not selected',
    (platform) => {
      const { getByTestId, queryByTestId } = renderApp({
        initialState,
        selectedPlugin: null,
        selectedPlatform: platform,
      });
      expect(getByTestId('integration-title').textContent).toMatch(INTEGRATION_TITLE[platform]);
      expect(queryByTestId('integration-guide')?.getAttribute('href')).toEqual(
        INTEGRATION_GUIDE[platform],
      );
      expect(queryByTestId('integration-url')).not.toBeInTheDocument();
    },
  );

  test('should render connect and integrate guide correctly if plugin is Shopify', () => {
    const { getByTestId, queryByTestId } = renderApp({
      initialState,
      selectedPlugin: SUPPORTED_PLUGINS.Shopify.name,
    });

    expect(getByTestId('integration-title').textContent).toMatch(SUPPORTED_PLUGINS.Shopify.name);
    expect(queryByTestId('integration-url')?.getAttribute('href')).toEqual(
      SUPPORTED_PLUGINS.Shopify.integration_url,
    );
    expect(queryByTestId('integration-guide')?.getAttribute('href')).toEqual(
      SUPPORTED_PLUGINS.Shopify.integration_guide,
    );
  });

  test('should render setup button if plugin is supported and integration url is present', () => {
    const { getByTestId } = renderApp({
      initialState,
      selectedPlugin: SUPPORTED_PLUGINS.Wix.name,
    });

    expect(getByTestId('integration-title').textContent).toMatch(SUPPORTED_PLUGINS.Wix.name);
    expect(getByTestId('integration-url').getAttribute('href')).toEqual(
      SUPPORTED_PLUGINS.Wix.integration_url,
    );
    expect(getByTestId('integration-guide').getAttribute('href')).toEqual(
      SUPPORTED_PLUGINS.Wix.integration_guide,
    );
  });

  test('should not render setup button if integration url is not present', () => {
    const { getByTestId } = renderApp({
      initialState,
      selectedPlugin: SUPPORTED_PLUGINS.WordPress.name,
    });
    expect(getByTestId('integration-title').textContent).toMatch(SUPPORTED_PLUGINS.WordPress.name);
    expect(getByTestId('integration-guide').getAttribute('href')).toEqual(
      SUPPORTED_PLUGINS.WordPress.integration_guide,
    );
  });

  test('should not render setup button if plugin is not supported', () => {
    const { getByTestId, queryByTestId } = renderApp({
      initialState,
      selectedPlugin: 'Squarespace',
    });

    expect(getByTestId('integration-title').textContent).toMatch(
      INTEGRATION_TITLE[Platform.WEBSITE],
    );
    expect(queryByTestId('integration-url')).not.toBeInTheDocument();
    expect(getByTestId('integration-guide')).toBeInTheDocument();
    expect(getByTestId('integration-guide').getAttribute('href')).toEqual(
      INTEGRATION_GUIDE[Platform.WEBSITE],
    );
  });

  test.each([Platform.ANDROID, Platform.IOS])(
    'should not render setup button if platform is not %s',
    (platform) => {
      const { getByTestId } = renderApp({
        initialState,
        selectedPlugin: SUPPORTED_PLUGINS.Shopify.name,
        selectedPlatform: platform,
      });
      expect(getByTestId('integration-title').textContent).toMatch(INTEGRATION_TITLE[platform]);
    },
  );

  test.each(['integration-url', 'integration-guide'])(
    'should trigger analytics events on integrate %s click',
    async (cta) => {
      const { getByTestId } = renderApp({
        initialState,
        selectedPlugin: SUPPORTED_PLUGINS.Wix.name,
        product: 'PH',
      });

      await userEvent.click(getByTestId(cta));
      expect(analyticsSpy).toHaveBeenCalledTimes(1);
      expect(analyticsSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          screen: 'API Keys & Plugins',
          actionName: 'Clicked',
          properties: {
            product: 'PH',
            paymentChannel: INTEGRATION_TITLE[Platform.WEBSITE],
            plugin: SUPPORTED_PLUGINS.Wix.name,
          },
        }),
      );
    },
  );
});
