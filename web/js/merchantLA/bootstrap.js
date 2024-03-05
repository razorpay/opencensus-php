import 'regenerator-runtime/runtime.js'; // eslint-disable-line
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { render } from 'react-dom';
import { Provider } from 'react-redux';
import { BrowserRouter as Router } from 'react-router-dom';
import { SpiltzServiceProvider } from 'shell/SpiltzServiceContext';

import 'common/utils/polyfills';
import store from 'merchantLA/store';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { I18ServiceProvider } from 'shell/I18Context';

import App from 'merchantLA/containers/App';
import '../../css/merchant-la.styl';
import '../../dashboard.font';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';
import { capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';

redirectToAppRoute('/app');

capturePrometheusMetric({
  name: Metrics.PAGE_VIEW,
  labels: { pathname: getPathForMetrics(window?.location?.pathname) },
});
render(
  <Provider store={store}>
    <BladeProvider themeTokens={paymentTheme}>
      <ConfirmModalProvider>
        <Router basename="/app">
          <SpiltzServiceProvider dashboardType="linkedAccount">
            <ErrorBoundary>
              <I18ServiceProvider>
                <App />
              </I18ServiceProvider>
            </ErrorBoundary>
          </SpiltzServiceProvider>
        </Router>
      </ConfirmModalProvider>
    </BladeProvider>
  </Provider>,
  document.getElementById('react-root'),
);
