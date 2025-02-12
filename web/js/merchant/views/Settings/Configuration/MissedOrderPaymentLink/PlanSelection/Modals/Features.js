import { connect } from 'react-redux';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import lazy from 'merchant/routes/LazyLoader';

// import Amount from 'common/ui/Amount';
import ModalHeader from 'common/ui/ModalHeader';
import { titleCase, humanize } from 'common/utils/rzp-utils';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { MOPL_PLANS } from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection/Constants/plans';
import Button from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Button';

const PlanConfirmation = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderPlanConfirmationModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection/Modals/PlanConfirmation'
  ),
);

const proceedToPlanConfirmation = (plan, openModal, closeModal) => {
  return openModal({
    size: 'large',
    component: (
      <SuspenseWithLoader>
        <PlanConfirmation
          flow="features"
          selectedPlan={plan}
          openModal={openModal}
          closeModal={closeModal}
        />
      </SuspenseWithLoader>
    ),
  });
};

const FeatureContent = ({ feature }) => {
  const featuresResult = [];
  Object.keys(feature).forEach((key) => {
    featuresResult.push(
      <div className="features-content" key={key}>
        <span className={feature[key] ? 'icon i-check success-tick' : 'icon i-close close-tick'} />{' '}
        {humanize(key)}
      </div>,
    );
  });
  return featuresResult;
};

const PlanContent = ({ plan, openModal, closeModal, selectPlanCb }) => {
  const features = plan.features;
  const channels = features.channels;
  const analytics = features.analytics;
  return (
    <div className="plan-container">
      <div id="missed-order-plan-title">{titleCase(plan.name)}</div>
      <div className="amount-wrapper">
        <span id="missed-order-amount-information">{plan.price_placeholder} per month</span>
        <div id="info-outline-icon-wrapper">
          <i className="i i-help-outline" />
        </div>
      </div>
      <div className="underline-blue" />
      <div className="sub-section">
        <div>
          <b>Channels</b>
        </div>
        <FeatureContent feature={channels} />
      </div>
      <div className="sub-section">
        <div>
          <b>Analytics</b>
        </div>
        <FeatureContent feature={analytics} />
      </div>
      <div className="select-btn-wrapper">
        <Button
          buttonText="Select Plan"
          onClick={() => selectPlanCb(plan, openModal, closeModal)}
        />
      </div>
    </div>
  );
};

const FeaturesModal = ({ missed_order_payment_link, openModal, closeModal, isPlanNew = true }) => {
  const plans = isPlanNew ? MOPL_PLANS : missed_order_payment_link?.plans?.data?.plans;

  return (
    <div className="missedorder-features-container">
      <ModalHeader title="Failed Payments Recovery" onCloseClick={closeModal} />
      <div className="content">
        <div>
          <b>Pick a monthly Plan</b> (change or cancel anytime later)
        </div>
        <div className="plans-wrapper">
          {(plans || []).map((plan) => (
            <PlanContent
              plan={plan}
              key={plan.id}
              openModal={openModal}
              closeModal={closeModal}
              selectPlanCb={proceedToPlanConfirmation}
            />
          ))}
        </div>
      </div>
    </div>
  );
};

export default connect(
  (state) => ({
    missed_order_payment_link: state.config.missed_order_payment_link,
  }),
  {
    openModal: openModalFn,
    closeModal: closeModalFn,
  },
)(FeaturesModal);
