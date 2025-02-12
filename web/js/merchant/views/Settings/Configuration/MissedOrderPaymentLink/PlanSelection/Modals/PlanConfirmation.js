import { useState } from 'react';
import { connect } from 'react-redux';
import lazy from 'merchant/routes/LazyLoader';
import { missedOrderPlanActivation, fetchMerchantMOPLSubscription } from 'merchant/reducers/config';

import Button from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import track from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/track';
import Info from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Info';
import ContinueButton from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Button';

const PLAN_SUCCESS = 'PLAN_SUCCESS';
const PLAN_CONFIRMATION = 'PLAN_CONFIRMATION';

const PlanSelection = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderPlanSelectionModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection'
  ),
);

const FeaturesModal = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderFeaturesModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection/Modals/Features'
  ),
);

const Header = ({ closeModal, planStatus }) => {
  return planStatus === PLAN_CONFIRMATION ? (
    <ModalHeader title="Confirm your plan" onCloseClick={closeModal} />
  ) : (
    <div className="modal-header header-wrapper">
      <i className="i i-done text-success icon-wrapper" />
      <h3 className="modal-title">You have activated Failed Payments Recovery</h3>
      {closeModal && (
        <button type="button" className="close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
      )}
    </div>
  );
};

const Footer = ({
  planStatus,
  confirmPlan,
  closeModal,
  handleGoBack,
  freeTrialActive,
  selectConditionCb,
  confirmPlanLoading,
  fetchSubscriptionLoading,
  setFetchSubscriptionLoading,
  fetchMerchantMOPLSubscription,
}) => {
  switch (planStatus) {
    case PLAN_CONFIRMATION:
      return (
        <>
          <div className="terms-conditions">
            You can cancel your plan anytime. By paying, you also agree to the{' '}
            <a
              target="_blank"
              rel="noreferrer noopener"
              href="https://razorpay.com/payments/terms/vas/"
              onClick={selectConditionCb}
            >
              terms and conditions.
            </a>
          </div>
          <div className="btn-wrapper">
            <Button.Transparent type="button" className="btn btn-primary" onClick={handleGoBack}>
              Go Back
            </Button.Transparent>
            <ContinueButton
              hideIcon={confirmPlanLoading}
              loading={confirmPlanLoading}
              pendingState="Continue"
              buttonText="Continue"
              onClick={confirmPlan}
            />
          </div>
        </>
      );
    case PLAN_SUCCESS:
      return (
        <>
          {freeTrialActive && (
            <div className="alert-info-wrapper">
              <b>Enjoy your trial for the next 30 days.</b> <br />
              After this, charges will be deducted every month
            </div>
          )}
          <div className="btn-wrapper">
            <ContinueButton
              hideIcon
              onClick={() => {
                setFetchSubscriptionLoading(true);
                return fetchMerchantMOPLSubscription().finally(() => {
                  closeModal();
                });
              }}
              loading={fetchSubscriptionLoading}
              pendingState="Okay, got it"
              buttonText="Okay, got it"
              btnClassName="btn-block"
            />
          </div>
        </>
      );
    default:
      return null;
  }
};

const PlanConfirmation = ({
  flow,
  openModal,
  closeModal,
  selectedPlan,
  missed_order_payment_link,
  fetchMerchantMOPLSubscription,
}) => {
  const today = new Date() / 1000;
  const thirtyDaysInUnixSeconds = 86400 * 30;
  const [confirmPlanLoading, setConfirmPlanLoading] = useState(false);
  const [fetchSubscriptionLoading, setFetchSubscriptionLoading] = useState(false);
  const oneMonthBillingPeriod = Number(+today) + thirtyDaysInUnixSeconds;
  const [planStatus, setPlanStaus] = useState(PLAN_CONFIRMATION);
  const subscription = missed_order_payment_link.subscription?.data;
  const freeTrialEligible = false || subscription?.free_trial_eligible;

  const confirmPlan = () => {
    setConfirmPlanLoading(true);
    return missedOrderPlanActivation(selectedPlan.id).then(() => {
      setPlanStaus(PLAN_SUCCESS);
      track.planSelection.confirmPlan(selectedPlan.name);
    });
  };

  const handleGoBack = () => {
    closeModal();
    const PreviousComponent = flow === 'planSelection' ? PlanSelection : FeaturesModal;
    openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <PreviousComponent closeModal={closeModal} />
        </SuspenseWithLoader>
      ),
    });
    track.planSelection.goBack(selectedPlan.name);
  };

  const selectTermsCondition = () => {
    track.planSelection.termsAndConditions(selectedPlan.name);
  };

  return (
    <div className="plan-confirmation-container">
      <Header closeModal={closeModal} planStatus={planStatus} />
      <div className="content">
        <Info
          plan={selectedPlan}
          trialDate={oneMonthBillingPeriod}
          isTrialVisible={freeTrialEligible}
          billingDate={
            freeTrialEligible
              ? oneMonthBillingPeriod + thirtyDaysInUnixSeconds
              : oneMonthBillingPeriod
          }
        />
        <div className="plan-conditions">
          {planStatus === PLAN_CONFIRMATION
            ? `After your trial period ends, the charges will be deducted from your settlement balance,
          every month.`
            : `Failed orders will be revived by automatically retargeting customers who have not completed the payment process.`}
        </div>
        <Footer
          closeModal={closeModal}
          planStatus={planStatus}
          confirmPlan={confirmPlan}
          handleGoBack={handleGoBack}
          freeTrialActive={freeTrialEligible}
          confirmPlanLoading={confirmPlanLoading}
          selectConditionCb={selectTermsCondition}
          fetchSubscriptionLoading={fetchSubscriptionLoading}
          setFetchSubscriptionLoading={setFetchSubscriptionLoading}
          fetchMerchantMOPLSubscription={fetchMerchantMOPLSubscription}
        />
      </div>
    </div>
  );
};

export default connect(
  (state) => ({
    missed_order_payment_link: state.config.missed_order_payment_link,
  }),
  {
    fetchMerchantMOPLSubscription,
  },
)(PlanConfirmation);
