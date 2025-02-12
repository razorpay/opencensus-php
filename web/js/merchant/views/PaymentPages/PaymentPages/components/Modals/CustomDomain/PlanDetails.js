import { ModalContent } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';

import { getFormattedNumber } from 'common/utils/rzp-utils';

const PlanDetailsModal = ({ planDetails, closeModal }) => {
  const handleClose = () => {
    closeModal();
  };

  return (
    <ModalContent>
      <div className="main-title">
        <div className="heading">
          <img src="https://cdn.razorpay.com/static/assets/globe.svg" alt="globe" width="20px" />
          Custom domain - plan details
        </div>
        <i className="i i-close" onClick={handleClose} />
      </div>
      <main>
        <div className="plan-card plan-card--selected">
          <div>
            <span>{planDetails.name}</span>
            {planDetails.metadata.per_month_amount !== planDetails.metadata.plan_amount && (
              <span>₹{getFormattedNumber(planDetails.metadata.per_month_amount)}/month</span>
            )}
          </div>
          <div>
            <span>Plan renews on</span>
            <span>{planDetails.next_billing_at}</span>
          </div>
          <div>
            <span>Total price on renewal</span>
            <span>
              <span>&nbsp; ₹{getFormattedNumber(planDetails.metadata.plan_amount)}</span>
            </span>
          </div>
        </div>
        <br />
        <div>
          When your plan renews, these charges will be deducted from your settlement balance.
        </div>
        <br />
        <br />
        <br />
      </main>
      <footer>
        <Button.Primary className="Save-btn" type="submit" onClick={handleClose}>
          Okay, got it
        </Button.Primary>
      </footer>
    </ModalContent>
  );
};

export default PlanDetailsModal;
