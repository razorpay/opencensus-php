import React from 'react';
import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { matchPath, Navigate, Route, Routes } from 'react-router-dom';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { updateFeatures } from 'merchant/reducers/config';
import { fetchTransfers } from 'merchant/reducers/collection';
import { fetchAccounts } from 'merchant/reducers/marketplace/accounts';

import AccountsList from 'merchant/views/Marketplace/Accounts/List';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import BatchesList from 'merchant/views/Marketplace/Batch/List';
import { BlockCustomerFeeBearerOnboarding } from 'merchant/components/BlockOnBoarding';
import DocsLink from 'merchant/components/DocsLink';
import PaymentsList from 'merchant/views/Marketplace/Payments/List';
import ReversalsList from 'merchant/views/Marketplace/Reversals/List';
import TransfersList from 'merchant/views/Marketplace/Transfers/List';

import OnBoarding, { getIsAllowedResetRouteBoarding } from './OnBoarding';
import QuickGuide, { getRouteQuickGuideIsClosed } from './QuickGuide';
import Wrapper from './RouteWrapper';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import lazy from 'merchant/routes/LazyLoader';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';
import { Alert, Box } from '@razorpay/blade/components';
import PaymentsDetails from '../Transactions/v2/Payments/components/PaymentsDetails';
import { withRouter } from 'common/deprecated/withRouter';
import { isTransactionCleanupEnabled } from '../Transactions/v2/common/utils';

//lazy loads
const PlatformFeeList = lazy(() => import('merchant/views/Marketplace/PlatformFee/List'));

const ClonedPaymentsList = (props) => (
  <PaymentsList docUrl="https://razorpay.com/docs/route" {...props} />
);
@connect(
  (state) => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      transfers: state.transfers,
      accounts: state.accounts,
      routeProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.ROUTE),
    };
  },
  {
    ...ModalActions,
    fetchAccounts,
    updateFeatures,
    showNotification,
    fetchTransfers,
    handleProductQuickGuide,
  },
)
class MarketplaceContainer extends React.Component {
  componentDidMount() {
    this.initMarketPlace();
    this.fetchDataForMarketPlaceOnboarding();
  }

  componentDidUpdate(prevProps) {
    if (
      prevProps.transfers.loading !== this.props.transfers.loading ||
      prevProps.accounts.loading !== this.props.accounts.loading
    ) {
      this.initMarketPlace(this.props);
    }
  }

  componentWillUnmount() {
    const { routeProductOnBoarding } = this.props;

    if (routeProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...routeProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: false,
        isTour: false,
      });
    }
  }

  fetchDataForMarketPlaceOnboarding = () => {
    // eslint-disable-next-line no-shadow
    const { transfers, accounts, fetchAccounts, fetchTransfers } = this.props;

    if (!transfers.items.length) {
      fetchTransfers({ count: 25 });
    }

    if (!transfers.items.length && !accounts.accounts.length) {
      fetchAccounts({ count: 25 });
    }
  };

  initMarketPlace = (props = this.props) => {
    const { routeProductOnBoarding } = props;
    const { isMarketplaceEnabled } = props.user;

    if (routeProductOnBoarding.isTour) {
      return;
    }

    let showOnboarding = !isMarketplaceEnabled;

    if (isMarketplaceEnabled) {
      showOnboarding = getIsAllowedResetRouteBoarding({
        transfers: props.transfers,
        accounts: props.accounts,
      });
    }

    this.props.handleProductQuickGuide({
      ...routeProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getRouteQuickGuideIsClosed(props),
    });
  };

  render() {
    const { user, routeProductOnBoarding, location } = this.props;
    const { isQuickGuideOpen, showOnboarding } = routeProductOnBoarding;
    const { isOrgAxis, isMarketplaceEnabled } = user;

    const feeBearer = user.merchant.fee_bearer;
    const isCustomerFeeBearer = feeBearer === FEE_BEARER_TYPES.CUSTOMER;
    const isRoutesDisabled = !isMarketplaceEnabled;
    const isDetailsView = matchPath('/route/payments/:id', location.pathname);
    const isTransactionCleanup = isTransactionCleanupEnabled();
    const shouldShowBanners = isTransactionCleanup ? !isDetailsView : true;

    // in case of customer fee bearer if route is disabled for the user, he should not be able to access onboarding to turn on the ROUTE
    if (isCustomerFeeBearer && isRoutesDisabled) {
      return (
        <div className="text-justified">
          <BlockCustomerFeeBearerOnboarding feature="Route" />
        </div>
      );
    }

    if (showOnboarding && !isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <div className="Marketplace-Container">
        {shouldShowBanners && user.isDirectTransferEnabled && (
          <AnnouncementBanner
            title="Introducing Direct Transfers"
            theme="primary"
            canBeClosed={true}
            card_id="introducing-direct-transfers-banner"
          >
            <span className="support-tagline">
              Now start creating Direct Transfers to your linked accounts directly
            </span>
            <DocsLink
              url="https://razorpay.com/docs/payments/route/transfer-funds-to-linked-accounts/#direct-transfers"
              title="Learn more"
            />
          </AnnouncementBanner>
        )}
        {shouldShowBanners && isQuickGuideOpen ? <QuickGuide className="QuickGuide-v2" /> : null}
        {shouldShowBanners && isCustomerFeeBearer && (
          <Box padding="spacing.6" paddingBottom="spacing.0">
            <Alert
              emphasis="subtle"
              description="This product is not supported for merchants accepting payments as per the convenience fee model. Any payments accepted via QR will be auto refunded."
              title="Route is not available for you"
              isDismissible={false}
              isFullWidth
              color="notice"
            />
          </Box>
        )}
        <ErrorBoundary resetOnProps>
          <Routes>
            <Route path="*" element={<Navigate to="payments" replace />} />
            {isTransactionCleanup ? (
              <Route path="payments/*">
                <Route path=":id" element={<PaymentsDetails />} />
                <Route
                  path="*"
                  element={
                    <Wrapper>
                      <ClonedPaymentsList />
                    </Wrapper>
                  }
                />
              </Route>
            ) : (
              <Route
                path="payments/*"
                element={
                  <Wrapper>
                    <ClonedPaymentsList />
                  </Wrapper>
                }
              />
            )}
            <Route
              path="transfers/*"
              element={
                <Wrapper>
                  <TransfersList />
                </Wrapper>
              }
            />
            <Route
              path="platformfee/*"
              element={
                <Wrapper>
                  <PlatformFeeList />
                </Wrapper>
              }
            />
            <Route
              path="reversals/*"
              element={
                <Wrapper>
                  <ReversalsList />
                </Wrapper>
              }
            />
            <Route
              path="accounts/*"
              element={
                <Wrapper>
                  <AccountsList />
                </Wrapper>
              }
            />
            {!user.isOrgCurlec && (
              <Route
                path="batchuploads/*"
                element={
                  <Wrapper>
                    <BatchesList />
                  </Wrapper>
                }
              />
            )}
          </Routes>
        </ErrorBoundary>
      </div>
    );
  }
}

export default withRouter(MarketplaceContainer);
