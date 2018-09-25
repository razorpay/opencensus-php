import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import PaymentPagesList from './Pages/List';

import TestModeBanner from 'merchant/containers/TestModeBanner';
import Button from 'component/Button';

import { classList } from 'common/util';

@connect(state => {
  return {
    user: state.session.user,
  };
})
export default class PaymentPagesContainer extends Component {
  render() {
    const { user } = this.props;

    return (
      <tabbed-container>
        <header id="link-header">
          <NavLink exact to="/paymentpages">
            Payment Pages
            <span
              class="badge bg-success hidden-xs"
              style={{ marginLeft: '5px' }}
            >
              new
            </span>
          </NavLink>
        </header>

        <TestModeBanner />

        <content>
          <Switch>
            <Route path="/paymentpages" component={PaymentPagesList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
