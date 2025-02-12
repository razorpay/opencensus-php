import { ModalContent } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';
import DomainAddress from './DomainAddress';
import PlansListModal from './PlansList';

import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

import { getFormattedNumber } from 'common/utils/rzp-utils';

const PlanConfirmationModal = ({ openModal, closeModal, planDetails }) => {
  const handleContinue = () => {
    closeModal();

    openModal({
      size: 'medium',
      className: 'pp-custom-domain',
      component: (
        <DomainAddress openModal={openModal} closeModal={closeModal} planDetails={planDetails} />
      ),
    });

    track.settings.confirmDomainPlan(planDetails);
  };

  const handleGoBack = () => {
    closeModal();

    openModal({
      size: 'medium',
      className: 'pp-custom-domain',
      component: <PlansListModal openModal={openModal} closeModal={closeModal} />,
    });

    track.settings.goBackConfirmDomainPlan();
  };

  const handleClose = () => {
    closeModal();

    track.settings.exitConfirmDomainPlan();
  };

  return (
    <ModalContent>
      <div className="main-title">
        <div className="heading">
          <img src="https://cdn.razorpay.com/static/assets/globe.svg" alt="globe" width="20px" />
          Confirm your plan
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
          <div className="highlight">
            <b>Total price</b>
            <b>
              {!!planDetails.metadata.discount && (
                <span className="badge bg-success">SAVE {planDetails.metadata.discount}%</span>
              )}{' '}
              <span>&nbsp; ₹{getFormattedNumber(planDetails.metadata.plan_amount)}</span>
            </b>
          </div>
        </div>
        <br />
        <div>
          These charges will be deducted from your settlement balance after you connect your domain.
        </div>
        <br />
        <div className="help-text">
          You can cancel your plan at anytime. By paying, you also agree to the{' '}
          <a
            href="https://razorpay.com/payments/terms/vas"
            target="_blank"
            rel="noopener noreferrer"
          >
            terms and conditions
          </a>
          .{' '}
        </div>
        <br />
      </main>
      <footer>
        <Button.Transparent className="Cancel-btn" type="button" onClick={handleGoBack}>
          Go back
        </Button.Transparent>

        <Button.Primary className="Save-btn" type="submit" onClick={handleContinue}>
          Continue
        </Button.Primary>
      </footer>
    </ModalContent>
  );
};

export default PlanConfirmationModal;
