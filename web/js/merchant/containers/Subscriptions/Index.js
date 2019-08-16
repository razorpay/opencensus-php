import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { classList } from 'common/util';

import { RZPFeatures } from 'rzp/utils/constants';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import { fetchPlans } from 'merchant/modules/plans';
import { fetchSubscriptions } from 'merchant/modules/subscriptions';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import PlansList from 'merchant/containers/Plans/List';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import SubscriptionsList from 'merchant/containers/Subscriptions/List';

import TokensList from './Tokens/List';
import AuthLinksList from './AuthLinks/List';
import HostedEmanadateBatches from './Batch/List';
import RecurringPayments from './RecurringPayments/List';

import OnBoarding, { isAllowedResetSubscriptionBoarding } from './OnBoarding';
import QuickGuide, { getSubscriptionQuickGuideIsClosed } from './QuickGuide';

@connect(
  state => ({
    mode: state.session.mode,
    user: state.session.user,
    plans: state.plans,
    subscriptions: state.subscriptions,
    subscriptionProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.SUBSCRIPTIONS
    ),
  }),
  {
    fetchPlans,
    fetchSubscriptions,
    handleProductQuickGuide,
  }
)
export default class SubscriptionsController extends React.Component {
  componentDidMount() {
    if (this.props.user.isChargeAtWillEnabled) return;

    this.initSubscriptions();

    if (this.props.subscriptions.items.length) return;

    if (
      !this.props.plans.items.loading &&
      !this.props.plans.items.length &&
      !this.props.location.pathname.includes('plan')
    ) {
      this.props.fetchPlans({ count: 25 });
    }
  }

  componentWillUnmount() {
    if (this.props.user.isChargeAtWillEnabled) return;

    const { subscriptionProductOnBoarding } = this.props;

    if (
      subscriptionProductOnBoarding.isTour &&
      !this.props.user.isChargeAtWillEnabled
    ) {
      this.props.handleProductQuickGuide({
        ...subscriptionProductOnBoarding,
        isTour: false,
      });
    }
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.user.isChargeAtWillEnabled) return;

    if (nextProps.subscriptions.loading !== this.props.subscriptions.loading) {
      this.initSubscriptions(nextProps);
    }
  }

  initSubscriptions = (props = this.props) => {
    if (props.user.isChargeAtWillEnabled) return;

    const {
      isSubscriptionsEnabled,
      isChargeAtWillEnabled,
      merchants,
    } = props.user;

    const merchant = merchants[props.user.current];

    let showOnboarding = !isSubscriptionsEnabled && !isChargeAtWillEnabled;

    if (!showOnboarding) {
      showOnboarding = isAllowedResetSubscriptionBoarding({
        merchantId: merchant.id,
        mode: props.mode,
        plans: props.plans,
        subscriptions: props.subscriptions,
      });
    }

    let isQuickGuideClosed = getSubscriptionQuickGuideIsClosed(props);

    let subscriptionProductOnBoarding = {
      ...props.subscriptionProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: props.subscriptionProductOnBoarding.isTour
        ? true
        : !isQuickGuideClosed,
    };

    this.props.handleProductQuickGuide(subscriptionProductOnBoarding);
  };

  render() {
    if (!this.props.user.isChargeAtWillEnabled) {
      if (this.props.subscriptionProductOnBoarding.showOnboarding) {
        return <OnBoarding />;
      }
    }

    return (
      <div class={classList('Subscriptions-Container')}>
        <tabbed-container>
          {this.props.subscriptionProductOnBoarding.isQuickGuideOpen && (
            <QuickGuide />
          )}

          <header id="subscriptions-header">
            <ShowWhen additionalCondition={user => !user.isChargeAtWillEnabled}>
              <NavLink exact to="/subscriptions">
                Subscriptions
              </NavLink>
              <NavLink to="/plans">Plans</NavLink>
            </ShowWhen>

            <ShowWhen additionalCondition={user => user.isChargeAtWillEnabled}>
              <NavLink to="/recurring_payments">Payments</NavLink>
              <NavLink to="/tokens">Tokens</NavLink>
              <NavLink to="/authlinks">Authorization Links</NavLink>
              <NavLink exact to="/subscriptions/batchuploads">
                Batch Upload
              </NavLink>
            </ShowWhen>
          </header>

          <TestModeBanner />

          <content>
            <Switch>
              <ShowWhenRoute
                path="/subscriptions/batchuploads"
                component={HostedEmanadateBatches}
                additionalCondition={user => user.isChargeAtWillEnabled}
              />
              <ShowWhenRoute
                path="/subscriptions"
                component={SubscriptionsList}
                additionalCondition={user => !user.isChargeAtWillEnabled}
              />
              <Route path="/plans" component={ClonedPlanList} />
              <Route path="/tokens" component={TokensList} />
              <Route path="/recurring_payments" component={RecurringPayments} />
              <Route path="/authlinks" component={AuthLinksList} />
            </Switch>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

const ClonedPlanList = props => (
  <PlansList docUrl="https://razorpay.com/docs/subscriptions/" {...props} />
);
