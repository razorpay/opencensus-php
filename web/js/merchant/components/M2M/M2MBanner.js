import React, { useEffect } from 'react';
import loadFriendBuy from './m2mFriendBuy';
import { connect } from 'react-redux';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import * as EventActions from 'merchant/reducers/trackEvents';

const M2MBanner = (props) => {
  const {
    referralAmount,
    referralAmountCurrency,
    maxAllowedReferrals,
    userId,
    userEmail,
    userName,
    trackEvents,
  } = props;

  useEffect(() => {
    window.friendbuyAPI.push([
      'track',
      'customer',
      {
        id: userId,
        email: userEmail,
        name: userName,
      },
    ]);
    loadFriendBuy();
  }, []);

  const onReferClick = () => {
    trackEvents({
      objectName: 'Refer Now',
      actionName: 'clicked',
      screen: 'home page',
    });
  };

  const maxReferralAmount = getFormattedAmountNew(
    maxAllowedReferrals * referralAmount,
    true,
    referralAmountCurrency,
  );
  return (
    <div className="refferal-banner">
      <div className="banner-illustration">
        <img src="https://cdn.razorpay.com/m2m/m2m_banner.svg" />
      </div>
      <div className="banner-info">
        <div className="desktop-view">
          <div className="heading">
            Refer and earn up to {maxReferralAmount} worth transaction credits
          </div>
          <div className="desc">
            Know someone who needs to setup online payments? Refer up to {maxAllowedReferrals}{' '}
            friends and earn {getFormattedAmountNew(referralAmount, true, referralAmountCurrency)}{' '}
            transcation credits for each successsful referral
          </div>
        </div>
        <div className="mobile-view">
          <div className="heading">Refer and earn {maxReferralAmount} credits</div>
          <div className="desc">
            Refer up to {maxAllowedReferrals} friends and earn{' '}
            {getFormattedAmountNew(referralAmount, true, referralAmountCurrency)} transcation
            credits for each successsful referral
          </div>
        </div>
        <div className="mobile-view action-cta">
          <div className="Button Button--transparent" id="ReferralBanner" onClick={onReferClick}>
            Refer Now {'>'}
          </div>
        </div>
      </div>
      <div className="action">
        <div className="Button Button--primary" id="ReferralBanner" onClick={onReferClick}>
          Refer Now
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    referralAmount: state.merchantReferral.data.referral_amount,
    referralAmountCurrency: state.merchantReferral.data.referral_amount_currency,
    maxAllowedReferrals: state.merchantReferral.data.max_allowed_referrals,
    userId: state.session.user.current,
    userEmail: state.session.user.email,
    userName: state.session.user.name,
  };
};

export default connect(mapStateToProps, { ...EventActions })(M2MBanner);
