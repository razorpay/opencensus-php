import {
  PossibleStatuses,
  RZPFeatures,
  SubscriptionsStates,
} from 'rzp/utils/constants';

import Step from 'merchant/components/StepGuide/Step';
import QuickGuide, {
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';

import { getQuickGuideData } from './data';

const { done, locked, active, loading } = PossibleStatuses;

@QuickGuide({
  feature: RZPFeatures.SUBSCRIPTIONS,
  data_points: ['subscriptions', 'plans'],
})
export default class SubscriptionQuickGuide extends React.Component {
  getCloseBtn = isCompleted => {
    return (
      <QuickGuideCloseBtn
        isCompleted={isCompleted}
        onClick={this.props.onClickClose}
      />
    );
  };

  render() {
    const { planStatus, subscriptionStatus, paymentStatus } = getStatus(
      this.props
    );

    const CloseBtn = this.getCloseBtn(paymentStatus === done);

    let activeStep = 0;

    if (planStatus === done) {
      activeStep = 1;
    }
    if (subscriptionStatus === done || paymentStatus === done) {
      activeStep = 2;
    }

    return (
      <QuickStepGuide
        class="Subscriptions"
        title={Title}
        closeBtn={CloseBtn}
        activeStep={activeStep}
        status={paymentStatus}
      >
        <Step status={planStatus} {...getQuickGuideData.Plan(planStatus)} />

        <Step
          status={subscriptionStatus}
          {...getQuickGuideData.Subscription(subscriptionStatus)}
        />

        <Step
          status={paymentStatus}
          {...getQuickGuideData.Payment(paymentStatus)}
        />
      </QuickStepGuide>
    );
  }
}

const Title = <QuickGuideTitle />;

export const getSubscriptionQuickGuideIsClosed = props => {
  if (props.subscriptions.loading) return true;

  let isClosed = getQuickGuideIsClosedFromLocalStorage(
    RZPFeatures.SUBSCRIPTIONS
  );

  if (isClosed || props.subscriptions.items.length <= 2) {
    return isClosed;
  }

  let createCount = 0;

  props.subscriptions.items.forEach(subscription => {
    if (
      [
        SubscriptionsStates.ACTIVE,
        SubscriptionsStates.HALTED,
        SubscriptionsStates.PENDING,
        SubscriptionsStates.COMPLETED,
        SubscriptionsStates.AUTHENTICATED,
      ].includes(subscription.status)
    ) {
      createCount++;
    }

    if (createCount >= 2) {
      isClosed = true;

      return false;
    }
  });

  return isClosed;
};

const getStatus = ({ plans, subscriptions }) => {
  if (!subscriptions.items.length && !plans.items.length && plans.loading) {
    return {
      planStatus: loading,
      subscriptionStatus: loading,
      paymentStatus: loading,
    };
  }

  const planStatus = subscriptions.items.length
    ? done
    : plans.items.length ? done : active;

  if (!subscriptions.items.length && subscriptions.loading) {
    return {
      planStatus: planStatus,
      subscriptionStatus: loading,
      paymentStatus: loading,
    };
  }

  let subscriptionStatus = locked,
    paymentStatus = locked;

  if (planStatus === done) {
    if (subscriptions.items.length) {
      subscriptionStatus = done;
    } else {
      subscriptionStatus = active;
    }
  }

  if (subscriptionStatus === done) {
    if (subscriptions.items.length) {
      subscriptions.items.forEach(subscription => {
        if (
          [
            SubscriptionsStates.ACTIVE,
            SubscriptionsStates.PENDING,
            SubscriptionsStates.AUTHENTICATED,
            SubscriptionsStates.HALTED,
            SubscriptionsStates.COMPLETED,
          ].includes(subscription.status)
        ) {
          paymentStatus = done;

          return false;
        } else {
          paymentStatus = active;
        }
      });
    } else {
      paymentStatus = active;
    }
  }

  return {
    planStatus,
    subscriptionStatus,
    paymentStatus,
  };
};
