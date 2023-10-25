import './public-paths';
// eslint-disable-next-line import/extensions
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { render } from 'react-dom';
import { Provider } from 'react-redux';
import { BrowserRouter as Router } from 'react-router-dom';

import 'common/utils/polyfills';
import { I18ServiceProvider } from 'common/i18/I18ServiceProvider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';
import { SpiltzServiceProvider } from 'common/splitz/context/SplitzContextProvider';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';

import App from './containers/App';
import store from './store';
import '../../css/merchant.styl';
import '../../dashboard.font';

(async () => {
  if (localStorage.referrer === 'chrome-extension') {
    await import(/* webpackChunkName: "extension" */ './extension-entry');
  }
})();

if (module.hot) {
  module.hot.accept();
}

// The backend will handle checking whether user is logged in or not.
// Logged in => merchant bundle (this one) is served that has redirection logic.
// Logged out => newAuth bundle is served which doesn't have this redirection logic.
redirectToAppRoute('/app');

capturePrometheusMetric({
  name: Metrics.PAGE_VIEW,
  labels: { pathname: getPathForMetrics(window.location.pathname) },
});
render(
  <Provider store={store}>
    <ConfirmModalProvider>
      <Router basename="/app">
        <SpiltzServiceProvider dashboardType="merchant">
          <ErrorBoundary>
            <I18ServiceProvider>
              <App />
            </I18ServiceProvider>
          </ErrorBoundary>
        </SpiltzServiceProvider>
      </Router>
    </ConfirmModalProvider>
  </Provider>,
  document.getElementById('react-root'),
);
