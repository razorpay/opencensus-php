import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

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
import { updateVirtualAccountBulkEditStatus } from 'merchant/reducers/virtualaccounts';

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
    updateVirtualAccountBulkEditStatus,
  },
)
export default class SmartCollectContainer extends React.Component {
  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - Smart Collect',
    });
    // check va_edit_bulk MID feature - common API for all sub routes. This updates a flag in the reducer
    this.props
      .fetchFeatureStatus(this.props.user.id, 'va_edit_bulk')
      .then((fetchFeatureStatusResp) => {
        if (fetchFeatureStatusResp.data.status) {
          this.props.updateVirtualAccountBulkEditStatus(true);
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

    return (
      <div class="SmartCollect-Container">
        <div className="banner-container">
          <ShowWhen additionalCondition={(usr) => usr.isVAAccountOnSCMigration}>
            <AnnouncementBanner
              class="rewards-anc"
              theme="danger"
              title="Customer Identifier Expiring"
              card_id="sc-yes-bank-monotorium"
            >
              <span class="display-inline">
                As per new RBI guidelines, your Customer Identifier details have been updated. Share
                the new customer identifier details with your customers
              </span>{' '}
            </AnnouncementBanner>
          </ShowWhen>
        </div>

        {isQuickGuideOpen && <QuickGuide className="QuickGuide-v2" />}

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
      </div>
    );
  }
}
