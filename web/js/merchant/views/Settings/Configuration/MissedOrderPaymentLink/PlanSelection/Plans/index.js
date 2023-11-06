import { connect } from 'react-redux';

import Image from 'common/ui/Image';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import lazy from 'merchant/routes/LazyLoader';
import { titleCase } from 'common/utils/rzp-utils';
import WhatsAppImage from 'assets/missed_order/star.png';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import track from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/track';
import Button from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Button';

const FeaturesModal = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderFeaturesModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection/Modals/Features'
  ),
);

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
          selectedPlan={plan}
          flow="planSelection"
          openModal={openModal}
          closeModal={closeModal}
        />
      </SuspenseWithLoader>
    ),
  });
};

const Plan = ({ plan, viewAllFeaturesCb, selectPlanCb }) => {
  const isProPlan = plan.name === 'pro';

  const trackInfo = () => {
    track.plans.planDetails(plan.name);
  };

  return (
    <div className="plan-wrapper">
      <div className="plan-info">
        <div id="missed-order-plan-title">{titleCase(plan.name)}</div>
        <div className="amount-wrapper">
          <span id="missed-order-amount-information">{plan.price_placeholder} per month</span>
          <div id="info-outline-icon-wrapper" onMouseEnter={trackInfo}>
            <i className="i i-help-outline" />
            <Popover align="bottom" theme="dark" parentQuerySelector=".plan-selection-container">
              {' '}
              <PopoverBody>
                you will be charged to send retargeting message across SMS and Email{' '}
                {plan.name.toLowerCase() === 'pro' ? 'and WhatsApp' : ''}
              </PopoverBody>
            </Popover>
          </div>
        </div>
        {isProPlan ? (
          <div className="img-wrapper">
            <span className="img-frame">
              <Image src={WhatsAppImage} />
            </span>
            WhatsApp & Conversion insights
          </div>
        ) : (
          <div className="info-text pointer" onClick={() => viewAllFeaturesCb(plan)}>
            view all features <i className="i i-external-link" />
          </div>
        )}
      </div>
      <div className="select-plan-btn">
        <Button
          buttonText={plan.disabled ? 'Coming soon' : 'Select Plan'}
          disabled={plan.disabled}
          onClick={() => selectPlanCb(plan)}
        />
      </div>
    </div>
  );
};

const Plans = ({ plans, openModal, closeModal }) => {
  const viewAllFeaturesClick = (plan) => {
    openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <FeaturesModal closeModal={closeModal} plans={plans} />
        </SuspenseWithLoader>
      ),
    });
    track.plans.viewAllFeatures(plan.name);
  };

  const selectPlanClick = (plan) => {
    proceedToPlanConfirmation(plan, openModal, closeModal);
    track.plans.selectPlan(plan.name);
  };

  return plans?.map((plan) => (
    <Plan
      plan={plan}
      key={plan.id}
      selectPlanCb={selectPlanClick}
      viewAllFeaturesCb={viewAllFeaturesClick}
    />
  ));
};

export default connect(null, {
  openModal: openModalFn,
  closeModal: closeModalFn,
})(Plans);
