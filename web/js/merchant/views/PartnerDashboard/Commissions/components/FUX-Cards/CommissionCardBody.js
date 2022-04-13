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
              <div className="list-title">₹500 on Payments every time</div>
              <div className="list-subtext">
                when your referral dose a business of at least Rs 2000 using Razorpay products for 3
                consecutive months
              </div>
            </li>
            <li>
              <div className="list-title">₹1000 for Banking</div>
              <div className="list-subtext">
                Every time your referral uses Razorpay Current Account to make payouts for 3 months
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
          <div className="title-text">Rewards Unlimited</div>
          <div className="sub-text">
            Earn a fixed referral bonus on each successful referral of a sub-merchant.
          </div>
          <ul>
            <li>
              <div className="list-title">Earn 0.1% </div>
              <div className="list-subtext">
                of each successful payment by your referrals on Payment Products.
              </div>
            </li>
            <li>
              <div className="list-title">Earn ₹x on every y</div>
              <div className="list-subtext">
                successful payouts by your referrals on Banking Products.
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
