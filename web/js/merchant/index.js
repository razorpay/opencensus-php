import './public-paths';
// eslint-disable-next-line import/extensions
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import 'common/utils/polyfills';
import store from './store';
import App from './containers/App';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import '../../css/merchant.styl';
import '../../dashboard.font';
import { capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';
import { SpiltzServiceProvider } from 'common/splitz/context/SplitzContextProvider';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';
import { I18ServiceProvider } from 'common/i18/I18ServiceProvider';

(async () => {
  if (localStorage.referrer === 'chrome-extension') {
    await import(/* webpackChunkName: "extension" */ './extension-entry');
  }
})();

if (module.hot) {
  module.hot.accept();
}

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
