/* eslint-disable */
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import 'react-dates/initialize';
import 'common/utils/polyfills';
import '../../css/merchant.styl';
import '../../dashboard.font';

import { initSentry } from 'common/utils/observability';

import React, { Component, Suspense, lazy } from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import axios from 'axios';
import moment from 'moment';
import { render } from 'react-dom';
import { connect, Provider } from 'react-redux';
import { MemoryRouter as Router } from 'react-router-dom';
import { ThemeProvider } from 'styled-components';

import { SplitzRoutesBasedService } from '@libs/web-nexus/common/splitz/components/SplitzRoutesBasedService';
import store from '@dashboards/payments/store';
import * as NotificationActions from '@libs/web-nexus/merchant/reducers/notifications';
import * as SessionActions from '@dashboards/payments/reducers/session';
import User, { setFeatures } from '@dashboards/payments/models/User';
import { pokeConfig } from '@dashboards/payments/reducers/pokedex';
import { tabsMeta } from '@dashboards/payments/containers/Home/KeyMetrics/data';
import { getQuery as getPaymentMethodsQuery } from 'merchant/containers/Home/PaymentMethods/data';
import { AppProvider } from '@libs/web-nexus/common/context/App';
import { I18ServiceProvider } from '@libs/web-nexus/common/i18/I18ServiceProvider';
import { SpiltzServiceProvider } from '@libs/web-nexus/common/splitz/context/SplitzContextProvider';
import {
  SUCCESS_RATE,
  PAYMENT_METHODS,
  successRateMeta,
  populatePaymentMethods,
  populateSourceFilters,
  paymentMethodFilterVals,
  sourceFilterVals,
} from '@dashboards/pokedex/utils/data';
import { DashboardLoader, ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import errorService from '@razorpay/universe-cli/errorService';
import { compose } from 'redux';

initSentry('pokedex-dashboard');

const Home = lazy(() =>
  import(/* webpackChunkName: "PokedexHome" */ '@dashboards/payments/containers/Home/Index'),
);

try {
  window.RZP = window.RZP || {};

  const merchantId = (pokeConfig.merchantId = window.rzp_user.id);

  class App extends Component {
    constructor(props) {
      super(props);

      this.state = {
        isLoading: true,
        tabsMeta: { ...tabsMeta, [SUCCESS_RATE]: successRateMeta },
        selectedPaymentMethod: paymentMethodFilterVals[0],
        selectedSource: sourceFilterVals[0],
      };

      this.onFirstTxnDate = this.onFirstTxnDate.bind(this);
      this.onFilterChange = this.onFilterChange.bind(this);
    }

    analyticsFetch(query) {
      return axios({
        url: `/admin/api/live_${merchantId}/merchant/analytics`,
        method: 'post',
        data: query,
      }).then((data) => data.data);
    }

    onFirstTxnDate(firstTxnDate) {
      firstTxnDate = firstTxnDate || 0;

      const query = getPaymentMethodsQuery({
        startTime: firstTxnDate,
        endTime: moment().unix(),
      });

      delete query.filters.default[0].authorized_at;

      this.analyticsFetch(query)
        .then((resp) => {
          if (!resp.data || !resp.data.agg) {
            return;
          }

          populatePaymentMethods(resp.data.agg.result);

          this.setState({
            tabsMeta: { ...this.state.tabsMeta },
          });
        })
        .catch((e) => {
          console.error('Unable to populate payment methods and sources', e);
        });
    }

    onFilterChange(selectedFilter) {
      if (selectedFilter.filterName !== PAYMENT_METHODS) {
        return;
      }

      populateSourceFilters(selectedFilter);

      this.setState({
        tabsMeta: { ...this.state.tabsMeta },
      });
    }

    render() {
      const { tabsOrder, tabsMeta } = this.state;
      if (this.state.isLoading) {
        return <DashboardLoader loaderType="wrt-viewport" />;
      }
      return (
        <Suspense fallback={<DashboardLoader loaderType="wrt-viewport" />}>
          <Home
            tabsOrder={tabsOrder}
            tabsMeta={tabsMeta}
            onFirstTxnDate={this.onFirstTxnDate}
            onFilterChange={this.onFilterChange}
            analyticsFetch={this.analyticsFetch}
            isAdmin={true}
          />
        </Suspense>
      );
    }

    UNSAFE_componentWillMount() {
      Promise.all([
        this.fetchUser().then(({ data }) => {
          return data;
        }),
      ]).then((response) => {
        const user = new User(response[0]);
        user.features = setFeatures([]);

        this.props.updateSession({ user });

        const $splash = document.getElementById('splash');
        if ($splash) {
          $splash.parentElement.removeChild($splash);
        }

        this.setState({ isLoading: false });
      });
    }

    fetchUser() {
      const user = new User(window.rzp_user);
      if (user) {
        delete window.rzp_user;
        this.props.updateSession({ user });
        return Promise.resolve({ data: user });
      } else {
        return this.props.fetchUser();
      }
    }
  }

  const PokedexMain = compose(
    connect((state) => state.session, {
      ...SessionActions,
      ...NotificationActions,
    }),
  )(App);

  const PokedexApp = () => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <ThemeProvider theme={theme}>
          <Provider store={store}>
            <AppProvider>
              <Router>
                <ErrorBoundary
                  rank={DASHBOARD_PRIORITY_RANKS.P0}
                  team={DASHBOARD_TEAMS.PG_DASHBOARD}
                  resetOnProps
                >
                  <SpiltzServiceProvider
                    dashboardType="pokedex"
                    customLoader={() => <DashboardLoader loaderType="wrt-viewport" />}
                  >
                    <SplitzRoutesBasedService
                      customLoader={() => <DashboardLoader loaderType="wrt-viewport" />}
                    >
                      <I18ServiceProvider>
                        <PokedexMain />
                      </I18ServiceProvider>
                    </SplitzRoutesBasedService>
                  </SpiltzServiceProvider>
                </ErrorBoundary>
              </Router>
            </AppProvider>
          </Provider>
        </ThemeProvider>
      </BladeProvider>
    );
  };

  render(<PokedexApp />, document.getElementById('react-root'));
} catch (error) {
  errorService?.captureError?.(error, {
    rank: DASHBOARD_PRIORITY_RANKS?.P0,
    tags: {
      type: 'P0: Critical Incident',
      app: 'pokedex-dashboard',
    },
  });
}
