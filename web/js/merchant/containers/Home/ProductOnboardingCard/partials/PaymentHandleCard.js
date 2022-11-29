import React from 'react';
import PaymentHandleImage from 'assets/product-led-onboarding/payment-handle.svg';

const PaymentHandleCard = ({
  paymentHandleData,
  showCustomizeModal,
  onCopyHandle,
  isCTADisabled,
  isHandleCopied,
}) => {
  return (
    <div className="plo-card" data-testid="payment-handle-card">
      <div className="wrapper">
        <div className="content">
          <div>
            <div className="header">Razorpay.me</div>
            <div className="description">Share your link with anyone and collect a payment</div>
          </div>
          <div>
            <img className="card-illustration" src={PaymentHandleImage} alt="" />
          </div>
        </div>
        <div>
          <div className="handle-link">
            <span>{paymentHandleData.paymentHandleUrl}</span>
          </div>
          <div className="btn-container multiple-btn">
            <button
              type="button"
              className="btn btn-outline btn-block"
              disabled={isCTADisabled}
              onClick={onCopyHandle}
            >
              {isHandleCopied ? 'Copied' : `${navigator.share ? 'Share' : 'Copy'} link`}
            </button>
            <button
              type="button"
              disabled={isCTADisabled}
              className="btn btn-outline btn-block"
              onClick={showCustomizeModal}
            >
              Customize
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentHandleCard;
