import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import Plans from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection/Plans';

const PlanSelection = ({ closeModal, missed_order_payment_link }) => {
  const plans = missed_order_payment_link?.plans?.data?.plans;
  const subscription = missed_order_payment_link.subscription?.data;
  return (
    <div className="plan-selection-container">
      <ModalHeader title="Re-Marketer" onCloseClick={closeModal} />
      <div className="content">
        {' '}
        More than 30% of the customers drop off due to failed payments. With Razorpay Re-Marketer:{' '}
        <ul>
          <li>You can revive missed orders</li>
          <li>
            Improve your conversion by retargeting your <br /> customers across any of their
            prefrerred channels
          </li>
          <li>Your customers can pick off where they were dropped</li>
        </ul>
        <div>
          <b>Pick a monthly Plan</b> (cancel anytime later)
        </div>
        <Plans plans={plans} />
        {subscription?.free_trial_eligible && (
          <div className="free-eligible">
            <div className="info-wrapper">
              <i class="i i-info-outline" />
            </div>
            <div>
              <b>Try 30 days for free.</b> After this, charges will be deducted from your settlement
              balance, every month
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default connect(
  (state) => ({
    missed_order_payment_link: state.config.missed_order_payment_link,
  }),
  null,
)(PlanSelection);
