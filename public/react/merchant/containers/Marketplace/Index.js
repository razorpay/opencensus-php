import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import * as ModalActions from 'rzp/modules/modals';
import HeaderAction from 'rzp/ui/HeaderAction';
import { updateFeatures } from 'merchant/modules/config';
import AsyncButton from 'react-async-button';

import PaymentsList from 'merchant/containers/Marketplace/Payments/List';
import TransfersList from 'merchant/containers/Marketplace/Transfers/List';
import ReversalsList from 'merchant/containers/Marketplace/Reversals/List';
import AccountsList from 'merchant/containers/Marketplace/Accounts/List';

import FeatureOnboardingModal from 'merchant/containers/FeatureOnboardingModal';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
    };
  },
  { ...ModalActions, updateFeatures }
)
export default class MarketplaceContainer extends Component {
  componentWillMount() {
    if (
      this.props.mode === 'live' &&
      this.props.user.isMarketplaceEnabled === false
    ) {
      this.props.openModal({
        size: 'large',
        component: <FeatureOnboardingModal feature="virtual_accounts" />,
      });
    }
  }

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
      })
      .catch(err => {
        this.props.showNotification({
          type: 'danger',
          message: err.errors,
        });
      });
  };

  render() {
    if (
      this.props.user.isMarketplaceEnabled === false &&
      this.props.mode === 'test'
    ) {
      return (
        <tabbed-container>
          <header>
            <NavLink to="/reports">Razorpay Route</NavLink>
            <HeaderAction>
              <div class="btn-toolbar pull-right">
                <a
                  class="btn btn-link"
                  href="https://razorpay.com/docs/route"
                  target="_blank"
                >
                  Razorpay Route Documentation &nbsp;
                  <i class="icon icon-external-link" />
                </a>
              </div>
            </HeaderAction>
          </header>
          <content>
            <div class="content-wrapper content-sm">
              <div class="col-md-8">Try out Razorpay Route in test mode.</div>
              <div class="col-md-4">
                <AsyncButton
                  class="btn btn-default pull-right"
                  text="Enable Razorpay Route"
                  pendingText="Enabling..."
                  onClick={this.enableFeature}
                />
              </div>
            </div>
          </content>
        </tabbed-container>
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
