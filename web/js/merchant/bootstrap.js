import React, { Suspense, lazy } from 'react';
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import 'react-dates/initialize';
import errorService from '@razorpay/universe-cli/errorService';
import { DASHBOARD_PRIORITY_RANKS } from '@libs/shared-types';
import { DashboardLoader } from '@libs/shared-ui';
import { getPathForMetrics, capturePrometheusMetric, Metrics } from '@libs/shared-utils';
import { initSentry } from 'common/utils/observability';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';
import { render } from 'react-dom';
import { bladeTheme } from '@razorpay/blade/tokens';
import { BladeProvider } from '@razorpay/blade/components';

const ProductDashboard = lazy(() =>
  import(/* webpackChunkName: "ProductDashboard" */ './ProductDashboard'),
);

try {
  initSentry?.('payments-dashboard');

  (async () => {
    if (window?.localStorage?.referrer === 'chrome-extension') {
      await import(/* webpackChunkName: "extension" */ './extension-entry');
    }
  })();

  redirectToAppRoute?.('/app');

  window.addEventListener('load', () => {
    capturePrometheusMetric?.({
      name: Metrics?.PAGE_VIEW,
      labels: { pathname: getPathForMetrics?.(window?.location?.pathname) },
    });
  });


  const App = () => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <Suspense fallback={<DashboardLoader loaderType="wrt-viewport" />}>
          <ProductDashboard />
        </Suspense>
      </BladeProvider>
    );
  };

  render(<App />, document.getElementById('react-root'));
} catch (error) {
  errorService?.captureError?.(error, {
    rank: DASHBOARD_PRIORITY_RANKS?.P0,
    tags: {
      type: 'P0: Critical Incident',
      app: 'payments-dashboard',
    },
  });
}
