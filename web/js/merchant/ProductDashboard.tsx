import React, { lazy, Suspense } from 'react';
import 'react-dates/initialize';
import { I18nProvider } from '@razorpay/i18nify-react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import 'common/utils/polyfills';
import { I18ServiceProvider } from '@federated/dashboards/payments/services/i18Service';
import { SpiltzServiceProvider } from '@federated/dashboards/payments/services/splitzService';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import store from './store';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
// import store from '@federated/dashboards/payments/store';
import '../../css/merchant.styl';
import '../../dashboard.font';
import '@razorpay/blade/fonts.css';
import { ShellZustandToReduxSyncProvider } from 'common/utils/store-sync';
import { DashboardLoader } from '@libs/shared-ui';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
  MutationFunction,
} from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { graphqlRequestQuery, graphqlRequestMutation } from '@federated/apps/shell/graphql';

const MainApp = lazy(() => import(/* webpackChunkName: "ProductDashboard" */ './containers/App'));

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      queryFn: graphqlRequestQuery,
    },
    mutations: {
      mutationFn: graphqlRequestMutation as MutationFunction<unknown, unknown>,
    },
  },
});

// TODO: seperate
const ProductDashboard = ({ children }: { children?: JSX.Element }) => {
  const checkViaBrowserRouter = (child) => {
    return Boolean(window.ONE_DASHBOARD) ? (
      child
    ) : (
      <BrowserRouter basename="/app">{child}</BrowserRouter>
    );
  };

  return (
    <ErrorBoundary resetOnProps>
      <BladeProvider themeTokens={bladeTheme}>
        <I18nProvider>
          <ThemeProvider theme={theme}>
            <Provider store={store}>
              <ShellZustandToReduxSyncProvider store={store}>
                <ReactQueryClientProvider client={queryClient}>
                  <Suspense fallback={<DashboardLoader />}>
                    {checkViaBrowserRouter(
                      <ConfirmModalProvider>
                        <SpiltzServiceProvider
                          customLoader={() => <DashboardLoader />}
                          dashboardType="merchant"
                        >
                          <I18ServiceProvider>
                            <Routes>
                              <Route path="*" element={<MainApp>{children}</MainApp>} />
                            </Routes>
                          </I18ServiceProvider>
                        </SpiltzServiceProvider>
                      </ConfirmModalProvider>,
                    )}
                  </Suspense>
                  {process.env.PUBLIC_ENV == 'development' ? (
                    <ReactQueryDevtools initialIsOpen={false} />
                  ) : null}
                </ReactQueryClientProvider>
              </ShellZustandToReduxSyncProvider>
            </Provider>
          </ThemeProvider>
        </I18nProvider>
      </BladeProvider>
    </ErrorBoundary>
  );
};

export default ProductDashboard;
