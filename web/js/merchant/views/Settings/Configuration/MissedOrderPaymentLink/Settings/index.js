import { connect } from 'react-redux';
import lazy from 'merchant/routes/LazyLoader';

import Time from 'common/ui/Time';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import Lock from 'assets/missed_order/lock.svg'; // remove with custom-domain is merged
import WhatsApp from 'assets/app-store/partner-logos/whatsapp.png';
import track from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/track';
import Info from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Info';
import Button from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Button';

const CancelFlow = lazy(() =>
  import(
    /* webpackChunkName: "MissedOrderCancelModal" */ 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/Cancel'
  ),
);

const ToolTip = () => (
  <Popover align="bottom" theme="dark" parentQuerySelector=".manage-settings-container">
    <PopoverBody>You cannot turn this channel off</PopoverBody>
  </Popover>
);

const Settings = ({ closeModal, openModal, missed_order_payment_link }) => {
  const subscription = missed_order_payment_link.subscription?.data;
  const effectiveEndDate = subscription.current_plan?.effective_end_date;
  const renewalDate = subscription.current_plan?.renewal_date;
  const thirtyDaysInUnixSeconds = 86400 * 30;
  const freeTrialActive = subscription.current_plan?.free_trial_active;
  const billingDate = freeTrialActive
    ? renewalDate + thirtyDaysInUnixSeconds
    : renewalDate || effectiveEndDate;
  const isProPlan = subscription.current_plan?.name === 'pro';
  const handelCancelFlow = () => {
    openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <CancelFlow closeModal={closeModal} isFreeTrial={freeTrialActive} />
        </SuspenseWithLoader>
      ),
    });
    track.settings.cancelPlan();
  };

  return (
    <div className="manage-settings-container">
      <div className="modal-header header-wrapper">
        <div className="heading-title">
          <i className="i i-settings-outline mr-8" />
          <h3 className="modal-title">Failed Payments Recovery Settings</h3>
        </div>
        {closeModal && (
          <div>
            {' '}
            <button type="button" className="close" onClick={closeModal}>
              <i className="i i-close" />
            </button>
          </div>
        )}
      </div>
      <div className="content">
        <div className="heading">Channels</div>
        <div className="flex channels-space">
          <div>
            <Checkbox title="SMS" size="medium" checked={true} disabled />
            <ToolTip />
          </div>
          <div>
            <Checkbox title="EMAIL" size="medium" checked={true} disabled />
            <ToolTip />
          </div>
          {isProPlan && (
            <div className="middle-align">
              <Checkbox title="Whatsapp" size="medium" checked={true} disabled />
              <img src={WhatsApp} width="20px" height="20px" alt="whatsapp" />
              <ToolTip />
            </div>
          )}
        </div>

        {!isProPlan && (
          <div id="pro-plan-lock-wrapper" className="pro-info-wrapper">
            <div className="img-wrapper">
              <img src={Lock} height="12px" width="12px" />
            </div>
            <div className="pro-plan-text">
              Get <img src={WhatsApp} width="16px" height="16px" alt="whatsapp" />
              WhatsApp on the <b>Pro plan</b>
            </div>
          </div>
        )}

        <div className="heading current-wrapper">Current Plan</div>
        <div className="info-wrapper-space">
          <Info
            isPopOverVisible
            billingDate={billingDate}
            plan={subscription.current_plan}
            trialDate={subscription.current_plan.renewal_date}
            isTrialVisible={effectiveEndDate ? false : freeTrialActive}
          />
        </div>
        {!effectiveEndDate && (
          <div className="cancel-wrapper">
            <Text size="xsmall" color="shade.980" weight="regular">
              Your plan will be active till{' '}
              <Time value={effectiveEndDate || renewalDate} format="DD MMM, YYYY" />
            </Text>
            <Text
              size="xsmall"
              color="negative.900"
              weight="regular"
              className="pointer"
              onClick={handelCancelFlow}
            >
              Cancel Plan
            </Text>
          </div>
        )}

        <div className="btn-wrapper">
          <Button buttonText="Close" btnClassName="btn-block" onClick={closeModal} hideIcon />
        </div>
      </div>
    </div>
  );
};

export default connect(
  (state) => ({
    missed_order_payment_link: state.config.missed_order_payment_link,
  }),
  null,
)(Settings);
