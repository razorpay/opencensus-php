import React from 'react';
import { benefits, currentAccountStatuses } from './data';

const Secondary = ({ caAccount, pillType, pillText, content, hasAppliedCa }) => {
  return (
    <div className="ca-secondary-card">
      {!hasAppliedCa ? (
        <>
          <div className="head">A better settlement account:</div>
          <div className="benefits-list">
            {benefits.map((benefit) => (
              <div className="benefit">
                <img src="/img/charge-list.svg" className="img-list" alt="thunder" />
                <div className="info">{benefit}</div>
              </div>
            ))}
          </div>
        </>
      ) : (
        <>
          <div className="head">Track your application status:</div>
          {getStatusView({ pillType, pillText, content })}
        </>
      )}
      {caAccount &&
      (caAccount.status === currentAccountStatuses.activated ||
        caAccount.status === currentAccountStatuses.unserviceable) ? (
        <div className="highlight-info success">
          <img src="/img/green-tick.svg" className="img-info" alt="info" />
          <div className="info">
            Congrats! You can keep enjoying Neo pricing with a 1.7% transaction fees and its other
            benefits along with the added advantages of your newly opened current account
          </div>
        </div>
      ) : (
        <div className="highlight-info">
          <img src="/img/info-circle.svg" className="img-info" alt="info" />
          <div className="info">
            A RazorpayX current account is required for neo pricing plan. You will be reverted to
            classic pricing and 2% transaction fee in case you fail to open the account
          </div>
        </div>
      )}
      <img src="/img/razorpayX_Bg.svg" className="img-bg" alt="info" />
    </div>
  );
};

const getStatusView = ({ pillType, pillText, content }) => {
  return (
    <div className="status-container">
      <img src="/img/charge-list.svg" alt="thunder" />
      <div className="status">{content}</div>
      <div className={`ca-pill ${pillType}`}>{pillText}</div>
    </div>
  );
};

export default Secondary;
