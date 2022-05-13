import React from 'react';
import './Card.styl';

const CommissionCardBody = () => {
  const assetBase = `${window.cdnBaseUrl}/static/assets/partner-dashboard/fux-cards/commission-guide`;
  const bonusIcon = `${assetBase}/accelerate-bonus.svg`;
  const rewardIcon = `${assetBase}/unlimited-reward.svg`;
  return (
    <div className="card-wrapper">
      <div className="card-body">
        <div className="text-content">
          <div className="title-text"> Accelerated Bonuses</div>
          <div className="sub-text">Boost your earnings with active referrals</div>{' '}
          <ul>
            <li>
              <div className="list-title">Get ₹500 every time</div>
              <div className="list-subtext">
                when your referral dose a business of at least ₹2000 using Razorpay products for 3
                consecutive months
              </div>
            </li>
            <li>
              <div className="list-title">
                <a
                  href="https://razorpay.com/docs/partners/commissions/settlement-process"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Know More
                </a>
              </div>
            </li>
          </ul>
        </div>
        <div className="card-image bonus-image">
          <img src={bonusIcon} alt="accelerate bonus" />
        </div>
      </div>
      <div className="card-body">
        <div className="text-content">
          <div className="title-text">Unlimited Rewards</div>
          <div className="sub-text">Uncapped commissions at an unbeatable rate</div>
          <ul>
            <li>
              <div className="list-title">Get 0.1% </div>
              <div className="list-subtext">
                of your referral&apos;s transaction value as commission. Receive automated payout to
                your bank account each month
              </div>
            </li>
            <li>
              <div className="list-title">
                <a
                  href="https://razorpay.com/docs/partners/commissions"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Know More
                </a>
              </div>
            </li>
          </ul>
        </div>
        <div className="card-image rewards-image">
          <img src={rewardIcon} alt="unlimited reward" />
        </div>
      </div>
    </div>
  );
};

export default CommissionCardBody;
