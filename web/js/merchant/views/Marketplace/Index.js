import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { updateFeatures } from 'merchant/reducers/config';
import { fetchTransfers } from 'merchant/reducers/collection';
import { fetchAccounts } from 'merchant/reducers/marketplace/accounts';

import DocsLink from 'merchant/components/DocsLink';
import TestModeBanner from 'merchant/components/TestModeBanner';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import AccountsList from 'merchant/views/Marketplace/Accounts/List';
import PaymentsList from 'merchant/views/Marketplace/Payments/List';
import ReversalsList from 'merchant/views/Marketplace/Reversals/List';
import TransfersList from 'merchant/views/Marketplace/Transfers/List';
import BatchesList from 'merchant/views/Marketplace/Batch/List';
import ShowWhen from 'merchant/components/ShowWhen';

import OnBoarding, { getIsAllowedResetRouteBoarding } from './OnBoarding';
import QuickGuide, { getRouteQuickGuideIsClosed } from './QuickGuide';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';

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

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.transfers.loading !== this.props.transfers.loading ||
      nextProps.accounts.loading !== this.props.accounts.loading
    ) {
      this.initMarketPlace(nextProps);
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
    const { user } = this.props;
    const { isQuickGuideOpen, showOnboarding } = this.props.routeProductOnBoarding;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <div class="Marketplace-Container">
        {this.props.user.isDirectTransferEnabled && (
          <AnnouncementBanner
            title="Introducing Direct Transfers"
            theme="primary"
            canBeClosed={true}
            card_id="introducing-direct-transfers-banner"
          >
            <span className="support-tagline">
              Now start creating Direct Transfers to your linked accounts directly
            </span>
            <DocsLink url="https://razorpay.com/docs/route/dashboard/" title="Learn more" />
          </AnnouncementBanner>
        )}

        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="marketplace-header">
            <NavLink to="/route/payments">Payments</NavLink>
            <NavLink to="/route/transfers">Transfers</NavLink>
            <NavLink to="/route/reversals">Reversals</NavLink>
            <NavLink to="/route/accounts">Accounts</NavLink>
            <ShowWhen additionalCondition={(_user) => _user.isRouteBatchUploadEnabled}>
              <NavLink to="/route/batchuploads">
                Batch Upload <span class="badge bg-success">NEW</span>
              </NavLink>
            </ShowWhen>
          </header>

          <TestModeBanner />

          <content>
            <ErrorBoundary resetOnProps>
              <Switch>
                <Route path="/route/payments" render={ClonedPaymentsList} />
                <Route path="/route/transfers" component={TransfersList} />
                <Route path="/route/reversals" component={ReversalsList} />
                <Route path="/route/accounts" component={AccountsList} />
                <Route path="/route/batchuploads" component={BatchesList} />
              </Switch>
            </ErrorBoundary>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

export default MarketplaceContainer;
