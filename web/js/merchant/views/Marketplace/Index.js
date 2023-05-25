import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch } from 'react-router-dom';

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
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import AccountsList from 'merchant/views/Marketplace/Accounts/List';
import PaymentsList from 'merchant/views/Marketplace/Payments/List';
import ReversalsList from 'merchant/views/Marketplace/Reversals/List';
import TransfersList from 'merchant/views/Marketplace/Transfers/List';
import BatchesList from 'merchant/views/Marketplace/Batch/List';

import OnBoarding, { getIsAllowedResetRouteBoarding } from './OnBoarding';
import QuickGuide, { getRouteQuickGuideIsClosed } from './QuickGuide';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import lazy from 'merchant/routes/LazyLoader';

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
    const { user, routeProductOnBoarding } = this.props;
    const { isQuickGuideOpen, showOnboarding } = routeProductOnBoarding;
    const { isOrgAxis } = user;
    if (showOnboarding && !isOrgAxis) {
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

        {isQuickGuideOpen ? <QuickGuide className="QuickGuide-v2" /> : null}

        <ErrorBoundary resetOnProps>
          <Switch>
            <Route path="/route/payments" render={ClonedPaymentsList} />
            <Route path="/route/transfers" component={TransfersList} />
            <Route path="/route/platformfee" component={PlatformFeeList} />
            <Route path="/route/reversals" component={ReversalsList} />
            <Route path="/route/accounts" component={AccountsList} />
            <Route path="/route/batchuploads" component={BatchesList} />
          </Switch>
        </ErrorBoundary>
      </div>
    );
  }
}

export default MarketplaceContainer;
