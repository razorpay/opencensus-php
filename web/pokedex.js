import { Component } from 'react';
import 'react-dates/initialize';
import { connect, Provider } from 'react-redux';
import { render } from 'react-dom';
import { MemoryRouter as Router } from 'react-router-dom';

import 'rzp/utils/polyfills';
import store from 'merchant/store';

import * as NotificationActions from 'rzp/modules/notifications';
import * as SessionActions from 'merchant/modules/session';
import User, { setFeatures } from 'merchant/models/User';
import { pokeConfig } from 'merchant/modules/pokedex';

import HomeNew from 'merchant/containers/Home/New';

pokeConfig.merchantId = window.rzp_user.id;

const RETRIES = "retries",
      SUCCESS_RATE = 'successRate';

const successRateMeta = {
  name: SUCCESS_RATE,
  title: 'Success Rate',
  grouping: [],
  isPercent: true,
  filters: [{
    name: RETRIES,
    values: [
      {
        text: "Without Retries",
        value: 0
      }, {
        text: "With Retries",
        value: 1
      }
    ]
  }],
  index: 'payments',
  groupByColumnName: "Success Rate",
  noGrouping: true,
  valueKey: 'success_rate',
  getCountQuery: function () {
    return {
      [this.name]: {
        agg_type: 'success_rate',
        details: {
          index: this.index
        }
      }
    }
  },
  getHistogramQuery: function (grouping, breakdown, filters={}) {

    let agg_type = 'success_rate';

    const aggDetails = {
      index: this.index,
      group_by: [`histogram_${breakdown}`]
    };

    Object.keys(filters, function (filterName) {
    
      const filterVal = filters[filterName];

      // if retries need to be included, change the index name
      if (filterName === RETRIES && !filterVal.value) {

        agg_type += "_with_retry";
      }
    });

    return {
      [`${this.name}Histogram`]: {
        agg_type,
        details: {
          index: this.index,
          group_by: [`histogram_${breakdown}`]
        }
      }
    };
  }
};

const tabsOrderMixin = tabsOrder => tabsOrder.push(SUCCESS_RATE),
      tabsMetaMixin = tabsMeta => tabsMeta[SUCCESS_RATE] = successRateMeta;

@connect(state => state.session, {
  ...SessionActions,
  ...NotificationActions,
})
class App extends Component {
  state = {
    isLoading: false
  }
  render() {
    let { user } = this.props;

    if (this.state.isLoading) {
      return null;
    }

    return <HomeNew tabsOrderMixin={tabsOrderMixin}
                    tabsMetaMixin={tabsMetaMixin}
                    isAdmin={true}/>
  }

  componentWillMount() {
    Promise.all([
      this.fetchUser().then(({ data }) => {
        let user = data;
        let role = user.userRole;
        return data;
      }),
    ]).then(response => {
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
  <Provider store={store}>
    <Router>
      <App />
    </Router>
  </Provider>,
  document.getElementById('react-root')
);
