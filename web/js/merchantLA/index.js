import './public-paths';
import 'regenerator-runtime/runtime.js'; // eslint-disable-line
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { SpiltzServiceProvider } from 'common/splitz/context/SplitzContextProvider';

import 'common/utils/polyfills';
import store from 'merchantLA/store';

import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { I18ServiceProvider } from 'common/i18/I18ServiceProvider';
import App from 'merchantLA/containers/App';
import '../../css/merchant-la.styl';
import '../../dashboard.font';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';
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
