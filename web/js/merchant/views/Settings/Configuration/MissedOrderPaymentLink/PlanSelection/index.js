import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import Plans from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/PlanSelection/Plans';
import { MOPL_PLANS } from './Constants/plans';

const PlanSelection = ({ closeModal, missed_order_payment_link, isPlanNew = true }) => {
  const plans = isPlanNew ? MOPL_PLANS : missed_order_payment_link?.plans?.data?.plans;
  const subscription = missed_order_payment_link.subscription?.data;
  return (
    <div className="plan-selection-container">
      <ModalHeader title="Failed Payments Recovery" onCloseClick={closeModal} />
      <div className="content">
        {' '}
        More than 30% of the customers drop off due to failed payments. With Failed Payments
        Recovery:{' '}
        <ul>
          <li>Grow your revenue by upto 10%</li>
          <li>Recover up to 20% of failed payments</li>
          <li>Get insights on conversions & recovered revenue</li>
        </ul>
        <div>
          <b>Pick a monthly Plan</b> (cancel anytime later)
        </div>
        <Plans plans={plans} />
        {subscription?.free_trial_eligible && (
          <div className="free-eligible">
            <div className="info-wrapper">
              <i className="i i-info-outline" />
            </div>
            <div>
              <b>Try 30 days for free.</b> After this, charges will be deducted every month
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
