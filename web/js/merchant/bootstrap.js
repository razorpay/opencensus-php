// eslint-disable-next-line import/extensions
import 'regenerator-runtime/runtime.js';
import React, { Suspense } from 'react';
import 'core-js/es/map';
import 'core-js/es/set';

import 'react-dates/initialize';
import { I18nProvider } from '@razorpay/i18nify-react';
import { render } from 'react-dom';
import { Provider } from 'react-redux';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { I18ServiceProvider } from 'shell/I18Context';
import 'common/utils/polyfills';
import { SpiltzServiceProvider } from 'shell/SpiltzServiceContext';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
} from '@tanstack/react-query';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';
import { ReactQueryDevtools } from "@tanstack/react-query-devtools";
import {
  graphqlRequestQuery,
  graphqlRequestMutation,
} from 'common/services/graphql/graphql-client';
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

window.addEventListener('load', () => {
  capturePrometheusMetric({
    name: Metrics.PAGE_VIEW,
    labels: { pathname: getPathForMetrics(window.location.pathname) },
  });
});

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      queryFn: graphqlRequestQuery,
    },
    mutations: {
      mutationFn: graphqlRequestMutation
    },
  },
});

render(
  <ErrorBoundary resetOnProps>
    <I18nProvider>
      <Provider store={store}>
        <BrowserRouter basename="/app">
          <ReactQueryClientProvider client={queryClient}>
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
            {process.env.PUBLIC_ENV == 'development' ? (
              <ReactQueryDevtools initialIsOpen={false} />
            ) : null}
          </ReactQueryClientProvider>
        </BrowserRouter>
      </Provider>
    </I18nProvider>
  </ErrorBoundary>,
  document.getElementById('react-root'),
);
