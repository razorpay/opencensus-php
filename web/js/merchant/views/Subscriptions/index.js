import { connect } from 'react-redux';
import { Switch, NavLink, Route } from 'react-router-dom';

import { classList } from 'common/utils/rzp-utils';

import { RZPFeatures } from 'merchant/helpers/data';

import Popover, { PopoverBody } from 'common/ui/Popover';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import { fetchPlans } from 'merchant/reducers/plans';
import { fetchSubscriptions } from 'merchant/reducers/subscriptions';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import PlansList from 'merchant/views/Subscriptions/Plans/List';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import SubscriptionsList from 'merchant/views/Subscriptions/Subscriptions/List';

import TokensList from 'merchant/views/Subscriptions/Tokens/List';
import RegistrationLinksList from 'merchant/views/Subscriptions/RegistrationLinks/List';
import HostedEmanadateBatches from 'merchant/views/Subscriptions/Batch/List';
import RecurringPayments from 'merchant/views/Subscriptions/RecurringPayments/List';
import OnBoarding, {
  getIsAllowedResetSubscriptionBoarding,
} from 'merchant/views/Subscriptions/OnBoarding';
import QuickGuide, {
  getSubscriptionQuickGuideIsClosed,
} from 'merchant/views/Subscriptions/QuickGuide';

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
    this.fetchDataForOnboarding();
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
      nextProps.subscriptions.loading != this.props.subscriptions.loading
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
    if (
      props.user.isChargeAtWillEnabled ||
      props.subscriptionProductOnBoarding.isTour
    ) {
      return;
    }

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

    this.props.handleProductQuickGuide({
      ...subscriptionProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getSubscriptionQuickGuideIsClosed(props),
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
              <ShowWhen additionalCondition={user => user.isAuthLinkTokenAndPaymentsEnabled}>
                <NavLink to="/recurring_payments">Payments</NavLink>
                <NavLink to="/tokens">Tokens</NavLink>
              </ShowWhen>
              <NavLink to="/registration_links">
                Registration Links{' '}
                <span>
                  <i class="i i-info-circle" />
                  <Popover theme="dark">
                    <PopoverBody>
                      Authorization links are now called Registration links
                    </PopoverBody>
                  </Popover>
                </span>
              </NavLink>
              <ShowWhen additionalCondition={user => user.isAuthLinkBatchUploadEnabled}>
                <NavLink exact to="/subscriptions/batchuploads">
                  Batch Upload
                </NavLink>
              </ShowWhen>
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
              <ShowWhenRoute
                path="/plans"
                component={ClonedPlanList}
                additionalCondition={user => !user.isChargeAtWillEnabled}
              />

              <Route path="/recurring_payments" component={RecurringPayments} />
              <Route path="/tokens" component={TokensList} />
              <Route
                path="/registration_links"
                component={RegistrationLinksList}
              />
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
