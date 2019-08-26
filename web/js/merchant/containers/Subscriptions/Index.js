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

import OnBoarding, {
  getIsAllowedResetSubscriptionBoarding,
} from './OnBoarding';
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
    this.initSubscriptions();
  }

  componentWillUnmount() {
    const { subscriptionProductOnBoarding } = this.props;

    if (subscriptionProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...subscriptionProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: false,
        isTour: false,
      });
    }
  }

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.subscriptions.loading !== this.props.subscriptions.loading ||
      nextProps.subscriptionProductOnBoarding.showOnboarding !==
        this.props.subscriptionProductOnBoarding.showOnboarding
    ) {
      this.initSubscriptions(nextProps);
    }
  }

  fetchDataForOnboarding = () => {
    if (this.props.user.isChargeAtWillEnabled) return;

    const { subscriptions, plans, location } = this.props;

    const isPlanRoute = location.pathname.includes('plan');

    if (subscriptions.items.length || plans.items.length) {
      return;
    }

    if (isPlanRoute) {
      this.props.fetchSubscriptions({ count: 25 });

      return;
    }

    this.props.fetchPlans({ count: 25 });
  };

  initSubscriptions = (props = this.props) => {
    if (props.user.isChargeAtWillEnabled) return;

    const { subscriptionProductOnBoarding } = props,
      { isSubscriptionsEnabled } = props.user;

    let showOnboarding = !isSubscriptionsEnabled;

    if (isSubscriptionsEnabled) {
      showOnboarding = getIsAllowedResetSubscriptionBoarding({
        plans: props.plans,
        subscriptions: props.subscriptions,
      });
    } else {
      this.props.handleProductQuickGuide({
        ...subscriptionProductOnBoarding,
        showOnboarding,
      });

      return;
    }

    let isQuickGuideOpen = false;

    if (subscriptionProductOnBoarding.isTour) {
      isQuickGuideOpen = true;
    } else {
      isQuickGuideOpen = !getSubscriptionQuickGuideIsClosed(props);
    }

    this.props.handleProductQuickGuide({
      ...subscriptionProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen,
    });
  };

  render() {
    const { subscriptionProductOnBoarding } = this.props;

    if (subscriptionProductOnBoarding.showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <div class={classList('Subscriptions-Container')}>
        <tabbed-container>
          {subscriptionProductOnBoarding.isQuickGuideOpen && <QuickGuide />}

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
