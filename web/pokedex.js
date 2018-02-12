import { Component } from 'react';
import 'react-dates/initialize';
import { connect, Provider } from 'react-redux';
import { render } from 'react-dom';
import { HashRouter as Router } from 'react-router-dom';

import 'rzp/utils/polyfills';
import store from 'merchant/store';

import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as SessionActions from 'merchant/modules/session';
import User, { setFeatures } from 'merchant/models/User';
import { pokeConfig } from 'merchant/modules/pokedex';

import ConfirmModalProvider from 'rzp/ui/ConfirmModal/ConfirmModalProvider';

import HomeNew from 'merchant/containers/Home/New';

pokeConfig.merchantId = window.rzp_user.id;

@connect(state => state.session, {
  ...ModalActions,
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
    return <HomeNew />
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
    <ConfirmModalProvider>
      <Router basename="/app">
        <App />
      </Router>
    </ConfirmModalProvider>
  </Provider>,
  document.getElementById('react-root')
);
