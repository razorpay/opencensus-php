import React from 'react';
import { Alert, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Outlet } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import BlockOnBoarding, {
  BlockCustomerFeeBearerOnboarding,
} from 'merchant/components/BlockOnBoarding';
import ShowWhen from 'merchant/components/ShowWhen';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchFeatureStatus } from 'merchant/reducers/config';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { updateVirtualAccountBulkEditStatus } from 'merchant/reducers/virtualaccounts';
import { showNotification } from 'merchant_common/reducers/notifications';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';

class SmartCollectContainer extends React.Component {
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
    const feeBearer = user.merchant.fee_bearer;
    const isCustomerFeeBearer = feeBearer === FEE_BEARER_TYPES.CUSTOMER;
    const isSmartCollectDisabled = !user.isVirtualAccountsEnabled;

    if (user.isUnregisteredBusiness && isSmartCollectDisabled) {
      return (
        <div className="SmartCollect-Container">
          <BlockOnBoarding
            title="Smart Collect"
            description="This feature is not supported for your business type."
          />
        </div>
      );
    }

    // in case of customer fee bearer if smart-collect is disabled for the user, he should not be able to access onboarding to turn on the smart-collect
    if (isCustomerFeeBearer && isSmartCollectDisabled) {
      return (
        <div className="SmartCollect-Container text-justified">
          <BlockCustomerFeeBearerOnboarding feature="Smart Collect" />
        </div>
      );
    }

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <div className="SmartCollect-Container">
        <div className="banner-container">
          <ShowWhen additionalCondition={(usr) => usr.isVAAccountOnSCMigration}>
            <AnnouncementBanner
              className="rewards-anc"
              theme="danger"
              title="Customer Identifier Expiring"
              card_id="sc-yes-bank-monotorium"
            >
              <span className="display-inline">
                As per new RBI guidelines, your Customer Identifier details have been updated. Share
                the new customer identifier details with your customers
              </span>{' '}
            </AnnouncementBanner>
          </ShowWhen>
        </div>
        {isQuickGuideOpen && <QuickGuide className="QuickGuide-v2" />}
        {isCustomerFeeBearer && (
          <Box padding="spacing.6" paddingBottom="spacing.0">
            <Alert
              emphasis="subtle"
              description="This product is not supported for merchants accepting payments as per the convenience fee model. Any payments accepted via QR will be auto refunded."
              title="Smart collect is not available for you"
              isDismissible={false}
              isFullWidth
              color="notice"
            />
          </Box>
        )}
        <ErrorBoundary resetOnProps>
          <Outlet />
        </ErrorBoundary>
      </div>
    );
  }
}

export default connect(
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
)(SmartCollectContainer);
