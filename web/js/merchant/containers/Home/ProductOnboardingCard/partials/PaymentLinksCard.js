import React from 'react';
import PaymentLinksImage from 'assets/product-led-onboarding/payment-links.svg';

const PaymentLinksCard = ({ goToPaymentLinks }) => (
  <div className="plo-card" data-testid="payment-links-card">
    <div className="wrapper">
      <div className="content">
        <div>
          <div className="header">Payment Links</div>
          <div className="description">
            Create an instant, one-time, custom link to share with a customer
          </div>
        </div>
        <div>
          <img className="card-illustration" src={PaymentLinksImage} alt="" />
        </div>
      </div>
      <div className="btn-container">
        <button type="button" className="btn btn-outline" onClick={goToPaymentLinks}>
          Create payment link
        </button>
      </div>
    </div>
  </div>
);

export default PaymentLinksCard;
