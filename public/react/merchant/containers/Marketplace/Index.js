import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import * as ModalActions from 'rzp/modules/modals';

import PaymentsList from 'merchant/containers/Marketplace/Payments/List';
import TransfersList from 'merchant/containers/Marketplace/Transfers/List';
import ReversalsList from 'merchant/containers/Marketplace/Reversals/List';
import AccountsList from 'merchant/containers/Marketplace/Accounts/List';

import FeatureOnboardingModal from 'merchant/containers/FeatureOnboardingModal';

@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  { ...ModalActions }
)
export default class MarketplaceContainer extends Component {
  componentWillMount() {
    if (this.props.user.isMarketplaceEnabled === true) {
      this.props.openModal({
        size: 'small',
        component: <FeatureOnboardingModal feature="marketplace" />,
      });
    }
  }

  render() {
    return (
      <tabbed-container>
        <header id="marketplace-header">
          <NavLink to="/route/payments">Payments</NavLink>
          <NavLink to="/route/transfers">Transfers</NavLink>
          <NavLink to="/route/reversals">Reversals</NavLink>
          <NavLink to="/route/accounts">Accounts</NavLink>
        </header>
        <TestModeBanner />
        <content>
          <Switch>
            <Route path="/route/payments" component={PaymentsList} />
            <Route path="/route/transfers" component={TransfersList} />
            <Route path="/route/reversals" component={ReversalsList} />
            <Route path="/route/accounts" component={AccountsList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
