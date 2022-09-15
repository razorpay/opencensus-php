import React from 'react';
import ImgTopBg from 'assets/onboarding/top_bg.png';

const QuickActionsCard = () => {
  const ShareLink = () => (
    <div className="card">
      <p className="card-description">Share your link with anyone and collect a payment</p>
      <div className="cta-container">
        <div className="handle-link">https://razorpay.me/@abcassociates</div>
        <button type="button" className="btn btn-outline">
          Copy link
        </button>
        <button type="button" className="btn btn-outline">
          Customize
        </button>
      </div>
    </div>
  );

  const CreateLink = () => (
    <div className="card">
      <p className="card-description">
        Create an instant, one-time, custom link to share with a customer
      </p>
      <div className="cta-container">
        <button type="button" className="btn btn-outline no-margin">
          Create payment link
        </button>
      </div>
    </div>
  );

  return (
    <div className="product-onboarding-card quick-options">
      <div className="illustration-top">
        <img src={ImgTopBg} alt="Top" />
      </div>
      <div className="illustration-right">
        <img
          className="bg-right"
          src="https://cdn.razorpay.com/static/assets/product-recommendation/overview.svg"
        />
      </div>
      <p className="title">Quick actions</p>
      <div className="card-container">
        <ShareLink />
        <CreateLink />
      </div>
    </div>
  );
};

export default QuickActionsCard;
