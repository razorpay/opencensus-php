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

import OnBoarding from './OnBoarding';
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
    const isQuickGuideClosed = getRouteQuickGuideIsClosed(this.props);

    if (!isQuickGuideClosed) {
      this.props.handleProductQuickGuide({
        feature: RZPFeatures.ROUTE,
        isQuickGuide: true,
        isTour: false,
      });
    }

    if (this.props.transfers.items.length > 0) {
      return;
    }

    this.props.fetchTransfers({ count: 2 });

    if (!this.props.accounts.accounts.length) {
      this.props.fetchAccounts({ count: 1 });
    }
  }

  componentWillReceiveProps() {
    if (!this.props.routeProductOnBoarding.isQuickGuide) {
      const isQuickGuideClosed = getRouteQuickGuideIsClosed(this.props);

      if (!isQuickGuideClosed) {
        this.props.handleProductQuickGuide({
          feature: FEATURE,
          isQuickGuide: true,
          isTour: false,
        });
      }
    }
  }

  componentWillUnmount() {
    const { routeProductOnBoarding } = this.props;

    if (routeProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...routeProductOnBoarding,
        isQuickGuide: false,
        isTour: false,
      });
    }
  }

  render() {
    if (!this.props.user.isMarketplaceEnabled) {
      return <OnBoarding />;
    }

    const { isQuickGuide, isTour } = this.props.routeProductOnBoarding;

    const isQuickGuideOpen = isQuickGuide || isTour;

    return (
      <div>
        {isQuickGuideOpen && <QuickGuide />}

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
