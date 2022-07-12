import React, { useEffect } from 'react';
import loadFriendBuy from './m2mFriendBuy';
import { connect } from 'react-redux';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import * as EventActions from 'merchant/reducers/trackEvents';

const M2MBanner = (props) => {
  const { referralAmount, referralAmountCurrency, user, trackEvents } = props;

  const merchantID = user.current || user.merchant.id;
  const userEmail = user.email;
  const userName = user.contact_name;

  useEffect(() => {
    trackEvents({
      objectName: 'Referral Widget',
      actionName: 'Viewed',
      screen: 'home page',
      toCleverTap: true,
    });
    window.trackhubs?.({
      name: 'update_property',
      data: {
        referral_widget_viewed: true,
      },
    });

    window.friendbuyAPI.push([
      'track',
      'customer',
      {
        id: merchantID,
        email: userEmail,
        name: userName,
      },
    ]);

    window.friendbuyAPI.push([
      'subscribe',
      'emailShareSuccess',
      (payload) => {
        trackEvents({
          objectName: 'Email Share',
          actionName: 'Success',
          screen: 'home page',
          toCleverTap: true,
          properties: {
            ...payload,
          },
        });
      },
    ]);
    window.trackhubs?.({
      name: 'update_property',
      data: {
        email_share_success: true,
      },
    });
    window.friendbuyAPI.push([
      'subscribe',
      'widgetActionTriggered',
      (payload) => {
        trackEvents({
          objectName: 'Referral Widget Action',
          actionName: 'Trigerred',
          screen: 'home page',
          toCleverTap: true,
          properties: {
            ...payload,
          },
        });
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
            Know someone who needs to set up online payments? Receive{' '}
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
    user: state.session.user,
  };
};

export default connect(mapStateToProps, { ...EventActions })(M2MBanner);
