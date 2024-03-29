/* eslint-disable */
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import { Component } from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import axios from 'axios';
import moment from 'moment';
import 'react-dates/initialize';
import { render } from 'react-dom';
import { connect, Provider } from 'react-redux';
import { MemoryRouter as Router } from 'react-router-dom';
import { SpiltzServiceProvider } from 'shell/SpiltzServiceContext';

import { SplitzRoutesBasedService } from 'common/splitz/components/SplitzRoutesBasedService';
import store from 'merchant/store';
import 'common/utils/polyfills';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import { FullPageLoader } from 'common/components/Loader';
import * as SessionActions from 'merchant/reducers/session';
import User, { setFeatures } from 'merchant/models/User';
import { pokeConfig } from 'merchant/reducers/pokedex';
import { tabsMeta } from 'merchant/containers/Home/KeyMetrics/data';
import { getQuery as getPaymentMethodsQuery } from 'merchant/containers/Home/PaymentMethods/data';
import Home from 'merchant/containers/Home/Index';
import { AppProvider } from 'common/context/App';
import { ThemeProvider } from 'styled-components';

import {
  SUCCESS_RATE,
  PAYMENT_METHODS,
  successRateMeta,
  populatePaymentMethods,
  populateSourceFilters,
  paymentMethodFilterVals,
  sourceFilterVals,
} from 'pokedex/utils/data';

import css from '../../css/merchant.styl';
import fontconfig from '../../dashboard.font';
import { I18ServiceProvider } from 'shell/I18Context';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

window.RZP = window.RZP || {};

const merchantId = (pokeConfig.merchantId = window.rzp_user.id);

@connect((state) => state.session, {
  ...SessionActions,
  ...NotificationActions,
})
class App extends Component {
  constructor(props) {
    super(props);

    this.state = {
      isLoading: false,
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
    const { user } = this.props;
    const { tabsOrder, tabsMeta } = this.state;
    if (this.state.isLoading) {
      return null;
    }
    return (
      <Home
        tabsOrder={tabsOrder}
        tabsMeta={tabsMeta}
        onFirstTxnDate={this.onFirstTxnDate}
        onFilterChange={this.onFilterChange}
        analyticsFetch={this.analyticsFetch}
        isAdmin={true}
      />
    );
  }

  UNSAFE_componentWillMount() {
    Promise.all([
      this.fetchUser().then(({ data }) => {
        const user = data;
        const role = user.userRole;
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

// TODO: Refactor all providers into one.
render(
  <BladeProvider themeTokens={bladeTheme}>
    <ThemeProvider theme={theme}>
      <Provider store={store}>
        <AppProvider>
          <Router>
            <ErrorBoundary rank={Ranks.P0} team={Teams.PG_DASHBOARD} resetOnProps>
              <SpiltzServiceProvider
                dashboardType="pokedex"
                customLoader={() => <FullPageLoader />}
              >
                <SplitzRoutesBasedService customLoader={() => <FullPageLoader />}>
                  <I18ServiceProvider>
                    <App />
                  </I18ServiceProvider>
                </SplitzRoutesBasedService>
              </SpiltzServiceProvider>
            </ErrorBoundary>
          </Router>
        </AppProvider>
      </Provider>
    </ThemeProvider>
  </BladeProvider>,
  document.getElementById('react-root'),
);
