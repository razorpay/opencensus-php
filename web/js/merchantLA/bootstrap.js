import 'regenerator-runtime/runtime.js'; // eslint-disable-line
import 'core-js/es/map';
import 'core-js/es/set';
import 'react-dates/initialize';
import 'common/utils/polyfills';
import { DashboardLoader } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS } from '@libs/shared-types';

import { initSentry } from 'common/utils/observability';
import React, { lazy, Suspense } from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render } from 'react-dom';
import { Provider } from 'react-redux';
import { BrowserRouter as Router } from 'react-router-dom';
import { I18ServiceProvider } from '@federated/dashboards/payments/services/i18Service';
import { SpiltzServiceProvider } from '@federated/dashboards/payments/services/splitzService';

import store from 'merchantLA/store';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';

import '../../css/merchant-la.styl';
import '../../dashboard.font';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { getPathForMetrics, capturePrometheusMetric, Metrics } from '@libs/shared-utils';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';
import '@razorpay/blade/fonts.css';
import { createGlobalStyle } from 'styled-components';
import { ShellZustandToReduxSyncProvider } from 'common/utils/store-sync';
import errorService from '@razorpay/universe-cli/errorService';

const LADashboard = lazy(() =>
  import(/* webpackChunkName: "LADashboard" */ 'merchantLA/containers/App'),
);

const GlobalStyles = createGlobalStyle`
  body {
    font-family: ${(props) => props.theme.typography.fonts.family.text}
  }

  h1, h2, h3, h4, h5, h6 {
    font-family: ${(props) => props.theme.typography.fonts.family.heading};
  }
`;

try {
  initSentry?.('la-dashboard');

  redirectToAppRoute?.('/app');

  capturePrometheusMetric?.({
    name: Metrics?.PAGE_VIEW,
    labels: { pathname: getPathForMetrics?.(window?.location?.pathname) },
  });

  const App = () => {
    return (
      <Provider store={store}>
        <BladeProvider themeTokens={bladeTheme}>
          <ShellZustandToReduxSyncProvider store={store}>
            <GlobalStyles />
            <ConfirmModalProvider>
              <Router basename="/app">
                <SpiltzServiceProvider
                  customLoader={() => <DashboardLoader loaderType="wrt-viewport" />}
                  dashboardType="linkedAccount"
                >
                  <ErrorBoundary>
                    <I18ServiceProvider>
                      <Suspense fallback={<DashboardLoader loaderType="wrt-viewport" />}>
                        <LADashboard />
                      </Suspense>
                    </I18ServiceProvider>
                  </ErrorBoundary>
                </SpiltzServiceProvider>
              </Router>
            </ConfirmModalProvider>
          </ShellZustandToReduxSyncProvider>
        </BladeProvider>
      </Provider>
    );
  };

  render(<App />, document.getElementById('react-root'));
} catch (error) {
  errorService?.captureError?.(error, {
    rank: DASHBOARD_PRIORITY_RANKS?.P0,
    tags: {
      type: 'P0: Critical Incident',
      app: 'la-dashboard',
    },
  });

  // Temp Fall Back Mechanism (Till New Arch Is At 100%)
  if (
    !Boolean(
      document?.cookie
        ?.split?.('; ')
        ?.some?.((cookie) => cookie?.startsWith?.('dashboard_legacy=')),
    )
  ) {
    document.cookie = `dashboard_legacy=true; path=/;`;
    window.location.reload?.(true);
  }
}
