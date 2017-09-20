import React, { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { Route, Switch, NavLink } from 'react-router-dom';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import FeatureOnboarding from 'merchant/containers/FeatureOnboarding/OnBoarding';

import PaymentsList from 'merchant/containers/Marketplace/Payments/List';
import TransfersList from 'merchant/containers/Marketplace/Transfers/List';
import ReversalsList from 'merchant/containers/Marketplace/Reversals/List';
import AccountsList from 'merchant/containers/Marketplace/Accounts/List';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
    };
  },
  { updateFeatures, showNotification }
)
export default class MarketplaceContainer extends Component {
  enableFeature = () => {
    var data = {
      features: {
        marketplace: 1,
      },
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: 'Razorpay Route has been enabled!',
        });
        setTimeout(() => location.reload());
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    let featureEnabled = this.props.user.isMarketplaceEnabled;
    if (!featureEnabled) {
      const heading =
        'Automate your payment transfers for Marketplaces, Vendor, payouts, Regional splits, etc. and manage the complete payment cycle with Razorpay Route.';

      return (
        <FeatureOnboarding
          heading="Razorpay Route"
          description={heading}
          formType="marketplace"
          isTestMode={this.props.mode === 'test'}
          enableFeatureInTestMode={this.enableFeature}
        />
      );
    }

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
