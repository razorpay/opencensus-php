import React from 'react';
import ImgTopBg from 'assets/onboarding/top_bg.png';
import OverviewImage from 'assets/product-recommendation/overview.svg';

const QuickActionsCard = ({
  paymentHandleData,
  showCustomizeModal,
  goToPaymentLinks,
  onCopyHandle,
  isCTADisabled,
  isHandleCopied,
}) => {
  const ShareLink = () => (
    <div className="plo-card" data-testid="quick-action-share-card">
      <p className="card-description">Share your link with anyone and collect a payment</p>
      <div className="cta-container">
        <div className="handle-link">
          <span>{paymentHandleData.paymentHandleUrl}</span>
        </div>
        <div className="button-container">
          <button
            type="button"
            className="btn btn-outline ph-btn"
            onClick={onCopyHandle}
            disabled={isCTADisabled}
          >
            {isHandleCopied ? 'Copied' : `${navigator.share ? 'Share' : 'Copy'} link`}
          </button>
          <button
            type="button"
            className="btn btn-outline ph-btn"
            disabled={isCTADisabled}
            onClick={showCustomizeModal}
          >
            Customize
          </button>
        </div>
      </div>
    </div>
  );

  const CreateLink = () => (
    <div className="plo-card" data-testid="quick-action-links-card">
      <p className="card-description">
        Create an instant, one-time, custom link to share with a customer
      </p>
      <div className="cta-container">
        <button
          type="button"
          className="btn btn-outline payment-link-cta"
          onClick={goToPaymentLinks}
        >
          Create payment link
        </button>
      </div>
    </div>
  );

  return (
    <div className="product-onboarding-card quick-options product-led-onboarding">
      <div className="illustration-top">
        <img src={ImgTopBg} alt="Top" />
      </div>
      <div className="illustration-right">
        <img className="bg-right" src={OverviewImage} alt="" />
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
