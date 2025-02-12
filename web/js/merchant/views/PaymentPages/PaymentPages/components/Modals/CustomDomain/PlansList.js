import { useEffect, useState, Fragment } from 'react';

import { ModalContent } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';
import Alert from 'common/new-ui/Alert';
import PlanConfirmationModal from './PlanConfirmation';
import Spinner from 'common/ui/Spinner';

import { fetchCustomDomainAvailablePlans } from 'merchant/views/PaymentPages/PaymentPages/model';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

import { getFormattedNumber } from 'common/utils/rzp-utils';

const PlansListModal = ({ openModal, closeModal }) => {
  const [plans, setPlans] = useState([]);
  const [isLoading, setLoading] = useState(true);
  const [isError, setError] = useState(false);

  useEffect(() => {
    fetchCustomDomainAvailablePlans()
      .then((response) => {
        setPlans(response.data.plans);
      })
      .catch(() => {
        setError(true);
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  const handleSelectPlan = (plan) => {
    closeModal();

    openModal({
      size: 'medium',
      className: 'pp-custom-domain',
      component: (
        <PlanConfirmationModal openModal={openModal} closeModal={closeModal} planDetails={plan} />
      ),
    });

    track.settings.selectDomainPlan(plan);
  };

  const handleClose = () => {
    closeModal();

    track.settings.exitSelectDomainPlan();
  };

  let contentBody;
  if (isLoading) {
    contentBody = (
      <>
        <br />
        <div className="plan-card plan-card--option">
          <Spinner />
        </div>
      </>
    );
  } else if (isError) {
    contentBody = (
      <>
        <br />
        <div className="text-danger">Failed to fetch plans. Please try again later.</div>
      </>
    );
  } else {
    contentBody = plans.map((plan) => {
      return (
        <Fragment key={plan.id}>
          <br />
          <div className="plan-card plan-card--option">
            <div>
              <div className="highlight">
                <b>Pay for {plan.name}</b>
              </div>
              <div className="text-grey">
                <b>₹{getFormattedNumber(plan.metadata.plan_amount)}</b>{' '}
                {plan.metadata.per_month_amount !== plan.metadata.plan_amount && (
                  <span>&nbsp;(₹{getFormattedNumber(plan.metadata.per_month_amount)}/month)</span>
                )}
              </div>
              {!!plan.metadata.discount && (
                <div>
                  <span className="badge bg-success">SAVE {plan.metadata.discount}%</span>
                </div>
              )}
            </div>
            <span>
              <Button.Primary onClick={() => handleSelectPlan(plan)}>
                Select Plan <i className="i i-arrow-forward" />
              </Button.Primary>
            </span>
          </div>
        </Fragment>
      );
    });
  }

  return (
    <ModalContent>
      <div className="main-title">
        <div className="heading">
          <img src="https://cdn.razorpay.com/static/assets/globe.svg" alt="globe" width="20px" />
          Pick a plan to connect your domain
        </div>
        <i className="i i-close" onClick={handleClose} />
      </div>
      <main>
        <div>
          Build trust and establish your brand by connecting your domain to payment pages. You can
          always cancel your plan anytime later.
        </div>
        {contentBody}
        <br />
        <Alert.Warning iconBefore="i-info-outline">
          The charges towards your plan will be deducted <b>from your settlement balance</b>
        </Alert.Warning>
        <br />
      </main>
    </ModalContent>
  );
};

export default PlansListModal;
