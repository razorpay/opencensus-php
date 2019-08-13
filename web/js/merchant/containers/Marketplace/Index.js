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

import OnBoarding, { isAllowedResetRouteBoarding } from './OnBoarding';
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

    if (this.props.transfers.items.length > 0) {
      return;
    }

    this.props.fetchTransfers({ count: 2 });

    if (!this.props.accounts.accounts.length) {
      this.props.fetchAccounts({ count: 1 });
    }
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.transfers.loading !== this.props.transfers.loading) {
      this.initMarketPlace(nextProps);
    }
  }

  componentWillUnmount() {
    const { routeProductOnBoarding } = this.props;

    if (routeProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...routeProductOnBoarding,
        isTour: false,
      });
    }
  }

  initMarketPlace = (props = this.props) => {
    let showOnboarding = !props.user.isMarketplaceEnabled;

    if (!showOnboarding) {
      const merchant = props.user.merchants[props.user.current];

      showOnboarding = isAllowedResetRouteBoarding({
        merchantId: merchant.id,
        mode: props.mode,
        transfers: props.transfers,
        accounts: props.accounts,
      });
    }

    let isQuickGuideClosed = getRouteQuickGuideIsClosed(props);

    let routeProductOnBoarding = {
      ...props.routeProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: props.routeProductOnBoarding.isTour
        ? true
        : !isQuickGuideClosed,
    };

    this.props.handleProductQuickGuide(routeProductOnBoarding);
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
