import React from 'react';
import { RZPFeatures, PossibleStatuses } from 'merchant/helpers/data';

import QuickGuide, {
  setQuickGuideIsClosedInLocalStorage,
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideStep,
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';

import { getQuickGuideData } from './data';

const { done, locked, active, loading } = PossibleStatuses;

const SubscriptionsStates = {
  ACTIVE: 'active',
  CREATED: 'created',
  PENDING: 'pending',
  AUTHENTICATED: 'authenticated',
  HALTED: 'halted',
  EXPIRED: 'expired',
  COMPLETED: 'completed',
};
const Title = <QuickGuideTitle />;

@QuickGuide({
  feature: RZPFeatures.SUBSCRIPTIONS,
  data_points: ['subscriptions', 'plans'],
})
export default class SubscriptionQuickGuide extends React.Component {
  getCloseBtn = (isCompleted) => {
    return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={this.props.onClickClose} />;
  };

  render() {
    const { planStatus, subscriptionStatus, paymentStatus } = getStatus(this.props);

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
        className="Subscriptions"
        title={Title}
        closeBtn={CloseBtn}
        activeStep={activeStep}
        status={paymentStatus}
      >
        <QuickGuideStep
          status={planStatus}
          step="Plan"
          feature={RZPFeatures.SUBSCRIPTIONS}
          {...getQuickGuideData.plan(planStatus)}
        />

        <QuickGuideStep
          status={subscriptionStatus}
          step="Subscription"
          feature={RZPFeatures.SUBSCRIPTIONS}
          {...getQuickGuideData.subscription(subscriptionStatus)}
        />

        <QuickGuideStep
          status={paymentStatus}
          step="Payments"
          feature={RZPFeatures.SUBSCRIPTIONS}
          {...getQuickGuideData.payment(paymentStatus)}
        />
      </QuickStepGuide>
    );
  }
}

export const getSubscriptionQuickGuideIsClosed = (props) => {
  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.SUBSCRIPTIONS);

  if (isClosed || props.subscriptions.items.length <= 2) {
    return isClosed;
  }

  let createCount = 0;

  props.subscriptions.items.forEach((subscription) => {
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

      setQuickGuideIsClosedInLocalStorage(RZPFeatures.SUBSCRIPTIONS, true);
    }
  });

  return isClosed;
};

function getStatus({ plans, subscriptions }) {
  let planStatus = loading;
  let subscriptionStatus = loading;
  let paymentStatus = loading;

  if (subscriptions.loading && plans.loading) {
    return {
      planStatus,
      subscriptionStatus,
      paymentStatus,
    };
  }

  if (subscriptions.items.length || plans.items.length) {
    planStatus = done;
  } else {
    planStatus = active;
  }

  if (subscriptions.loading) {
    return {
      planStatus,
      subscriptionStatus,
      paymentStatus,
    };
  }

  subscriptionStatus = locked;
  paymentStatus = locked;

  if (subscriptions.items.length) {
    subscriptionStatus = done;
    paymentStatus = active;
  } else if (planStatus === done) {
    subscriptionStatus = active;
  }

  // Check paymentStatus
  if (subscriptionStatus === done) {
    subscriptions.items.forEach((subscription) => {
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
      }
    });
  }

  return {
    planStatus,
    subscriptionStatus,
    paymentStatus,
  };
}
