import React, { useEffect } from 'react';
import loadFriendBuy from './m2mFriendBuy';
import { connect } from 'react-redux';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import * as EventActions from 'merchant/reducers/trackEvents';

const M2MBanner = (props) => {
  const {
    referralAmount,
    referralAmountCurrency,
    userId,
    userEmail,
    userName,
    trackEvents,
  } = props;

  useEffect(() => {
    trackEvents({
      objectName: 'Referral Widget',
      actionName: 'Viewed',
      screen: 'home page',
      toCleverTap: true,
    });
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
      toCleverTap: true,
    });
  };

  return (
    <div className="refferal-banner">
      <div className="banner-illustration">
        <img src="https://cdn.razorpay.com/m2m/m2m_banner.svg" />
      </div>
      <div className="banner-info">
        <div className="desktop-view">
          <div className="heading">Help a fellow entrepreneur grow using Razorpay</div>
          <div className="desc">
            Know someone who needs to set up online payments? Recieve{' '}
            {getFormattedAmountNew(referralAmount, true, referralAmountCurrency)} in collections -
            100% FREE* per successful referral
          </div>
        </div>
        <div className="mobile-view">
          <div className="heading">Help a fellow entrepreneur grow using Razorpay</div>
          <div className="desc">
            Receive {getFormattedAmountNew(referralAmount, true, referralAmountCurrency)} in
            collections - 100% FREE* per successful referral
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
    userName: state.session.user.contact_name,
  };
};

export default connect(mapStateToProps, { ...EventActions })(M2MBanner);
