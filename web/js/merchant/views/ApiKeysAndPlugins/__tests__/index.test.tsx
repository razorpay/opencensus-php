import React from 'react';
import { render, waitFor, screen } from 'test-utils';
import ApiKeysAndPlugins from 'merchant/views/ApiKeysAndPlugins/index';
import merge from 'lodash/merge';
import store, { storeWithInitialState } from 'merchant/store';
import { Router } from 'react-router-dom';
import { Provider } from 'react-redux';
import { createMemoryHistory } from 'history';
import { PLATFORM_LINKS } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/__test__/mocks/fixtures';
// Mock `window.location` with Jest spies and extend expect
import 'jest-location-mock';

const globalState = store.getState();

const getInitialState = ({ userDetails = {} } = {}) => {
  return {
    session: {
      mode: 'test',
      ...globalState.session,
      user: merge(
        {
          ...globalState.session.user,
        },
        userDetails,
      ),
    },
  };
};

const history = createMemoryHistory({ initialEntries: ['/onboarding/api-keys'] });
history.replace = jest.fn();

const renderApp = ({ initialState = {}, ...props } = {}) =>
  render(
    <Provider store={storeWithInitialState(initialState)}>
      <Router history={history}>
        <ApiKeysAndPlugins {...props} />
      </Router>
    </Provider>,
    {
      initialState,
    },
  );

describe('ApiKeys', () => {
  test('should render back in fullscreen view', async () => {
    const props = { isFullScreenMode: true };
    const initialState = getInitialState({
      userDetails: {
        business_website: '',
        playstore_url: '',
        appstore_url: '',
      },
    });
    renderApp({ initialState, ...props });

    await waitFor(() => {
      expect(screen.getByText('Back')).toBeInTheDocument();
    });
  });

  test('should not render toggle mode in normal view', async () => {
    const props = { isFullScreenMode: false };
    const initialState = getInitialState();
    renderApp({ initialState, ...props });

    await waitFor(() => {
      expect(screen.queryByText('Live Mode')).not.toBeInTheDocument();
    });
  });

  test('should render toggle mode in fullscreen view', async () => {
    const props = { isFullScreenMode: true };
    const initialState = getInitialState({
      userDetails: {
        business_website: PLATFORM_LINKS.SUCCESS.business_website,
        merchant_business_detail: {
          website_details: {
            website_present: true,
          },
        },
      },
    });

    renderApp({ initialState, ...props });

    await waitFor(() => {
      expect(screen.getByText('Live Mode')).toBeInTheDocument();
    });
  });
});
