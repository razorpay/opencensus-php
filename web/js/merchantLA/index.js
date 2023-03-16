import 'regenerator-runtime/runtime.js'; // eslint-disable-line
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { HashRouter as Router } from 'react-router-dom';

import 'common/utils/polyfills';
import store from 'merchantLA/store';

import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';

import App from 'merchantLA/containers/App';
import '../../css/merchant-la.styl';
import '../../dashboard.font';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';

__webpack_public_path__ = (window.cdnDashboardUrl || '') + `/dist/`; // eslint-disable-line

capturePrometheusMetric({
  name: Metrics.PAGE_VIEW,
  labels: { pathname: getPathForMetrics(window?.location?.pathname) },
});
render(
  <Provider store={store}>
    <ConfirmModalProvider>
      <Router basename="/app">
        <ErrorBoundary>
          <App />
        </ErrorBoundary>
      </Router>
    </ConfirmModalProvider>
  </Provider>,
  document.getElementById('react-root'),
);
