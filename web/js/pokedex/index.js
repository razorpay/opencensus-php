__webpack_public_path__ = (window.cdnDashboardUrl || '') + `/dist/`;
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import axios from 'axios';
import { Component } from 'react';
import moment from 'moment';
import 'react-dates/initialize';
import { connect, Provider } from 'react-redux';
import { render } from 'react-dom';
import { MemoryRouter as Router } from 'react-router-dom';
import store from 'merchant/store';

import 'common/utils/polyfills';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import * as SessionActions from 'merchant/reducers/session';
import User, { setFeatures } from 'merchant/models/User';
import { pokeConfig } from 'merchant/reducers/pokedex';
import { fetch } from 'merchant/reducers/pokedex';
import { tabsOrder, tabsMeta } from 'merchant/containers/Home/KeyMetrics/data';
import { getQuery as getPaymentMethodsQuery } from 'merchant/containers/Home/PaymentMethods/data';
import Home from 'merchant/containers/Home/Index';

import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';

import css from '../../css/merchant.styl';
import fontconfig from '../../dashboard.font';

import {
  SUCCESS_RATE,
  PAYMENT_METHODS,
  successRateMeta,
  populatePaymentMethods,
  populateSourceFilters,
  paymentMethodFilterVals,
  sourceFilterVals,
} from 'pokedex/utils/data';

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
    let { user } = this.props;

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
        let user = data;
        let role = user.userRole;
        return data;
      }),
    ]).then((response) => {
      let user = new User(response[0]);
      user.features = setFeatures([]);

      this.props.updateSession({ user });

      let $splash = document.getElementById('splash');
      if ($splash) {
        $splash.parentElement.removeChild($splash);
      }

      this.setState({ isLoading: false });
    });
  }

  fetchUser() {
    let user = new User(window.rzp_user);
    if (user) {
      delete window.rzp_user;
      this.props.updateSession({ user });
      return Promise.resolve({ data: user });
    } else {
      return this.props.fetchUser();
    }
  }
}

render(
  <ThemeProvider theme={theme}>
    <Provider store={store}>
      <Router>
        <App />
      </Router>
    </Provider>
  </ThemeProvider>,
  document.getElementById('react-root')
);
