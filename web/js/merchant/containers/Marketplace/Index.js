import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'rzp/utils/constants';

import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';
import { updateFeatures } from 'merchant/modules/config';
import { fetchTransfers } from 'merchant/modules/collection';
import { fetchAccounts } from 'merchant/modules/marketplace/accounts';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import AccountsList from 'merchant/containers/Marketplace/Accounts/List';
import PaymentsList from 'merchant/containers/Marketplace/Payments/List';
import ReversalsList from 'merchant/containers/Marketplace/Reversals/List';
import TransfersList from 'merchant/containers/Marketplace/Transfers/List';

import OnBoarding, { getIsAllowedResetRouteBoarding } from './OnBoarding';
import QuickGuide, { getRouteQuickGuideIsClosed } from './QuickGuide';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      transfers: state.transfers,
      accounts: state.accounts,
      routeProductOnBoarding: getCurrentProductOnBoardingDetails(
        state,
        RZPFeatures.ROUTE
      ),
    };
  },
  {
    ...ModalActions,
    fetchAccounts,
    updateFeatures,
    showNotification,
    fetchTransfers,
    handleProductQuickGuide,
  }
)
export default class MarketplaceContainer extends React.Component {
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
    const { transfers, accounts, fetchAccounts, fetchTransfers } = this.props;

    if (!transfers.items.length) {
      fetchTransfers({ count: 25 });
    }

    if (!transfers.items.length && !accounts.accounts.length) {
      fetchAccounts({ count: 25 });
    }
  };

  initMarketPlace = (props = this.props) => {
    const { routeProductOnBoarding } = props,
      { isMarketplaceEnabled } = props.user;

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
    const {
      isQuickGuideOpen,
      showOnboarding,
    } = this.props.routeProductOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <div class="Marketplace-Container">
        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="marketplace-header">
            <NavLink to="/route/payments">Payments</NavLink>
            <NavLink to="/route/transfers">Transfers</NavLink>
            <NavLink to="/route/reversals">Reversals</NavLink>
            <NavLink to="/route/accounts">Accounts</NavLink>
          </header>

          <TestModeBanner />

          <content>
            <Switch>
              <Route path="/route/payments" render={ClonedPaymentsList} />
              <Route path="/route/transfers" component={TransfersList} />
              <Route path="/route/reversals" component={ReversalsList} />
              <Route path="/route/accounts" component={AccountsList} />
            </Switch>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

const ClonedPaymentsList = props => (
  <PaymentsList docUrl="https://razorpay.com/docs/route" {...props} />
);
