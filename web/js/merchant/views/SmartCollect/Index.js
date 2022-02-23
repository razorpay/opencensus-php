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
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import VirtualAccountsList from './VirtualAccounts/List';
import BlockOnBoarding from './BlockOnBoarding';

import ShowWhen from 'merchant/components/ShowWhen';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import BatchExpiryUpdate from './BatchExpiryUpdate/List';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchFeatureStatus } from 'merchant/reducers/config';

@connect(
  (state) => {
    return {
      user: state.session.user,
      VAProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.VA),
    };
  },
  {
    handleProductQuickGuide,
    showNotification,
    fetchFeatureStatus,
  },
)
export default class SmartCollectContainer extends React.Component {
  state = {
    isVaEditBulkMid: false,
  };

  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - Smart Collect',
    });
    // check va_edit_bulk MID feature
    this.props
      .fetchFeatureStatus(this.props.user.id, 'va_edit_bulk')
      .then((fetchFeatureStatusResp) => {
        if (fetchFeatureStatusResp.data.status) {
          this.setState({
            isVaEditBulkMid: true,
          });
        }
      })
      .catch((err) => {
        if (err) {
          this.props.showNotification({
            type: 'error',
            message: err.errors[0],
          });
        }
      });
  }

  render() {
    const { user } = this.props;
    const { isQuickGuideOpen, showOnboarding } = this.props.VAProductOnBoarding;

    if (user.isUnregisteredBusiness && !user.isVirtualAccountsEnabled) {
      return (
        <div class="SmartCollect-Container">
          <BlockOnBoarding />
        </div>
      );
    }

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    const className = this.props.location.pathname.includes('virtualaccounts') && 'active';

    return (
      <div class="SmartCollect-Container">
        <ShowWhen additionalCondition={(usr) => usr.isVAAccountOnSCMigration}>
          <div className="banner-container">
            <AnnouncementBanner
              class="rewards-anc"
              theme="danger"
              title="Virtual Account Expiring"
              card_id="sc-yes-bank-monotorium"
            >
              <span class="display-inline">
                As per new RBI guidelines, your Virtual Account details have been updated. Share the
                new account details with your customers
              </span>{' '}
            </AnnouncementBanner>
          </div>
        </ShowWhen>

        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="smart-collect-header">
            <NavLink to="/smartcollect/virtualaccounts" class={className}>
              Virtual Accounts
            </NavLink>
            <NavLink to="/smartcollect/payments">Payments</NavLink>
            {this.state.isVaEditBulkMid && (
              <NavLink to="/smartcollect/batchuploads">Batch Expiry Update</NavLink>
            )}
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
                <Route path="/smartcollect/batchuploads" component={BatchExpiryUpdate} />
              </Switch>
            </ErrorBoundary>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
