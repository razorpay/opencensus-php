// eslint-disable-next-line import/extensions
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import React, { Suspense } from 'react';

import 'react-dates/initialize';
import { I18nProvider } from '@razorpay/i18nify-react';
import { render } from 'react-dom';
import { Provider } from 'react-redux';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { I18ServiceProvider } from 'shell/I18Context';
import 'common/utils/polyfills';
import { SpiltzServiceProvider } from 'shell/SpiltzServiceContext';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';

import App from './containers/App';
import store from './store';
import '../../css/merchant.styl';
import '../../dashboard.font';
import '@razorpay/blade/fonts.css';

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
  <ErrorBoundary resetOnProps>
    <I18nProvider>
      <Provider store={store}>
        <BrowserRouter basename="/app">
          <ConfirmModalProvider>
            <SpiltzServiceProvider dashboardType="merchant">
              <I18ServiceProvider>
                <Suspense fallback={<div>Loading</div>}>
                  <Routes>
                    <Route path="*" element={<App />} />
                  </Routes>
                </Suspense>
              </I18ServiceProvider>
            </SpiltzServiceProvider>
          </ConfirmModalProvider>
        </BrowserRouter>
      </Provider>
    </I18nProvider>
  </ErrorBoundary>,
  document.getElementById('react-root'),
);
