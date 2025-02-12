import React, { useEffect, useState } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import {
  fetchInsights,
  fetchMOPLPlans,
  fetchFeatureStatus,
  fetchMerchantMOPLSubscription,
} from 'merchant/reducers/config';

import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';
import lazy from 'merchant/routes/LazyLoader';
import TextHighlighter from 'common/ui/TextHighlighter';
import { getCurrentMonth } from 'common/utils/date-utils';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import IconSave from 'assets/missed_order/icon-save.svg';
import track from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/track';
import Button from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { Box } from '@razorpay/blade/components';
import FailedPaymentsRetryCalculator from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/components/FailedPaymentsRetry';
import 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/missedorder.styl';

const PlanSelection = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderPlanSelectionModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection'
  ),
);

const ViewInsight = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderViewInsightModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/Insights'
  ),
);

const Settings = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderSettingsModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/Settings'
  ),
);

const TIME_FORMAT = 'DD MMM, YYYY';

const GetInsightAmount = (props) => {
  const { amount = 0 } = props;
  return (
    <>
      <b>
        You have saved <Amount value={amount} currency="INR" />
      </b>{' '}
      by reviving failed order
    </>
  );
};

const MissedOrderPaymentLink = ({
  openModal,
  closeModal,
  tracking,
  fetchInsights,
  fetchMOPLPlans,
  missed_order_payment_link,
  fetchMerchantMOPLSubscription,
}) => {
  const plans = missed_order_payment_link.plans;
  const subscription = missed_order_payment_link.subscription?.data;
  const isProPlan = subscription.current_plan?.name === 'pro';
  const insights = missed_order_payment_link.insights.data;
  const activeSubscription =
    subscription.current_plan !== undefined && Object.keys(subscription.current_plan).length > 0;
  const isInsightsAvailable = !(Object.keys(insights).length === 0);
  const [isFetchPlansloading, setFetchPlansloading] = useState(false);
  const renewalDate = subscription.current_plan?.renewal_date;
  const freeTrialActive = subscription.current_plan?.free_trial_active;
  const effectiveEndDate = subscription.current_plan?.effective_end_date;

  useEffect(() => {
    track.init(tracking.trackEvent);
  }, [tracking]);

  useEffect(() => {
    // Info : we don't need to load data again and again as backend data updates in 24 hours.
    if (missed_order_payment_link.subscription.loading) {
      fetchMerchantMOPLSubscription();
    }

    if (missed_order_payment_link.insights.loading) {
      fetchInsights(getCurrentMonth() + 1);
    }
  }, [
    fetchInsights,
    fetchMerchantMOPLSubscription,
    missed_order_payment_link.insights.loading,
    missed_order_payment_link.subscription.loading,
  ]);

  const planSelection = async () => {
    setFetchPlansloading(true);

    if (plans.loading) {
      await fetchMOPLPlans();
    }
    setFetchPlansloading(false);
    openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <PlanSelection closeModal={closeModal} />
        </SuspenseWithLoader>
      ),
    });
    track.getStarted();
    triggerHotjarRecording('Failed_Payments_Recovery');
  };

  const viewInsight = () => {
    openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <ViewInsight closeModal={closeModal} insights={insights} isProPlan={isProPlan} />
        </SuspenseWithLoader>
      ),
    });
    track.viewInsights();
  };

  const manageSettings = () => {
    openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <Settings closeModal={closeModal} openModal={openModal} />
        </SuspenseWithLoader>
      ),
    });
    track.manageSettings();
  };

  return (
    <Box display="flex" flexDirection="column" gap="spacing.6">
      <div className="missed-order-wrapper">
        <div className="panel panel-default">
          <div className="panel-heading manage-wrapper">
            <span className="title">
              <TextHighlighter>Failed Payments Recovery</TextHighlighter>
              {activeSubscription && (
                <>
                  <td className="active-wrapper">
                    <span
                      className={`active-content ${
                        effectiveEndDate ? 'inactive-color' : 'active-color'
                      }`}
                    >
                      {effectiveEndDate ? 'INACTIVE' : 'ACTIVE'}
                    </span>
                  </td>
                  <li
                    className={`date-wrapper ${
                      freeTrialActive && !effectiveEndDate ? 'free-bill-date' : 'billing-date'
                    }`}
                  >
                    {freeTrialActive && !effectiveEndDate ? (
                      <span>
                        <span>Free till</span>&nbsp;
                        <Time value={renewalDate} format={TIME_FORMAT} />
                      </span>
                    ) : (
                      <span>
                        <span>
                          {effectiveEndDate
                            ? freeTrialActive
                              ? 'Trial ends on'
                              : 'Final bill On'
                            : 'Next billing On'}
                        </span>
                        &nbsp;
                        <Time value={effectiveEndDate || renewalDate} format={TIME_FORMAT} />
                      </span>
                    )}
                  </li>
                </>
              )}
            </span>
            {activeSubscription && (
              <div className="manage-btn" onClick={manageSettings}>
                <i className="i i-settings icon-wrapper" />
                Manage
              </div>
            )}
          </div>

          <div className="panel-body">
            <form className="form-horizontal form-btn-align">
              <div>
                <div>
                  Revive failed orders by automatically retargeting customers who have not <br />
                  completed the payment process
                </div>
                {activeSubscription && (
                  <div className="insight-description-wrapper">
                    <div className="insight-highlight-textarea">
                      <img className="insight-image-wrapper" src={IconSave} />
                      <span>
                        {isInsightsAvailable ? (
                          <GetInsightAmount amount={insights?.revived_amount} />
                        ) : (
                          'No failed orders have been revived yet'
                        )}
                      </span>
                    </div>{' '}
                    {isInsightsAvailable && (
                      <div className="manage-btn" onClick={viewInsight}>
                        View Insights
                      </div>
                    )}
                  </div>
                )}
              </div>
              {!activeSubscription && (
                <div>
                  <Button
                    hideIcon
                    loading={isFetchPlansloading}
                    buttonText="Get Started"
                    btnClassName="get-started-btn"
                    pendingState="Get Started"
                    onClick={() => planSelection()}
                  />
                  <p className="charge-info">- Charges apply -</p>
                </div>
              )}
            </form>
          </div>
        </div>
      </div>
      {!activeSubscription || (activeSubscription && effectiveEndDate) ? (
        <FailedPaymentsRetryCalculator />
      ) : null}
    </Box>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('MissedOrderPaymentLink')),
  connect(
    (state) => ({
      user: state.session.user,
      missed_order_payment_link: state.config.missed_order_payment_link,
    }),
    {
      fetchInsights,
      fetchMOPLPlans,
      fetchFeatureStatus,
      openModal: openModalFn,
      closeModal: closeModalFn,
      fetchMerchantMOPLSubscription,
    },
  ),
)(MissedOrderPaymentLink);
