import React from 'react';
import { trackCTAClick } from 'merchant/containers/Home/ProductOnboardingCard/events';
import PaymentButtonsImage from 'assets/product-led-onboarding/payment-buttons.svg';

const PaymentButtonsCard = ({ history, product }) => {
  const goToPaymentButtons = () => {
    trackCTAClick('PB', { product });
    history.push(`/paymentbuttons/new?redirect=/dashboard`);
  };
  return (
    <div className="plo-card" data-testid="payment-buttons-card">
      <div className="wrapper">
        <div className="content">
          <div>
            <div className="header">Payment Buttons</div>
            <div className="description">
              Add a quick checkout button for one-time and recurring payments on your website
            </div>
          </div>
          <div>
            <img className="card-illustration" src={PaymentButtonsImage} alt="" />
          </div>
        </div>
        <div>
          <div>5 minute integration</div>
          <div className="btn-container">
            <button type="button" className="btn btn-outline" onClick={goToPaymentButtons}>
              Create Payment Button
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentButtonsCard;
