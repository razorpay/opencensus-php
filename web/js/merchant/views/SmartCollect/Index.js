import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import TestModeBanner from 'merchant/components/TestModeBanner';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';
import PaymentsList from './Payments/List';
import VirtualAccountsList from './VirtualAccounts/List';
import BlockOnBoarding from './BlockOnBoarding';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';

@connect(
  (state) => {
    return {
      user: state.session.user,
      VAProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.VA),
    };
  },
  {
    handleProductQuickGuide,
  },
)
export default class SmartCollectContainer extends React.Component {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - Smart Collect',
    });
  }

  render() {
    const { user } = this.props;
    if (user.isUnregisteredBusiness && !user.isVirtualAccountsEnabled) {
      return (
        <div class="SmartCollect-Container">
          <BlockOnBoarding />
        </div>
      );
    }

    const { isQuickGuideOpen, showOnboarding } = this.props.VAProductOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    const className = this.props.location.pathname.includes('virtualaccounts') && 'active';

    return (
      <div class="SmartCollect-Container">
        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="smart-collect-header">
            <NavLink to="/smartcollect/virtualaccounts" class={className}>
              Virtual Accounts
            </NavLink>
            <NavLink to="/smartcollect/payments">Payments</NavLink>
          </header>

          <TestModeBanner />

          <content>
            <ErrorBoundary resetOnProps>
              <Switch>
                <Route
                  path={['/smartcollect/virtualaccounts', '/virtualaccounts']}
                  component={VirtualAccountsList}
                />
                <Route path="/smartcollect/payments" component={PaymentsList} />
              </Switch>
            </ErrorBoundary>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
