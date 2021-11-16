import React from 'react';
import { trackReferral } from 'merchant/views/PartnerDashboard/ga';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { mediaWindowUrl } from './SocialShare';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default function SocialShareGroup({ referralUrl, tracking, product, partnerID }) {
  const trackUserEvent = (eventName, properties = {}) => {
    tracking.trackEvent(
      window.rzpQ.onbr().clicked(eventName, {
        partnerID,
        ...properties,
      }),
    );
  };

  const shareReferralOn = (platform) => {
    mediaWindowUrl({
      type: platform,
      url: referralUrl,
      title: 'Sign up on Razorpay!',
      description:
        "Start using a wide range of Razorpay's payment solutions and unlock growth for your business with just a few clicks. Go live in less than 10 minutes.",
    });
    if (product === PRODUCT_TYPE.X) {
      trackUserEvent('partnerships.submerchant.referral.x.social');
      trackUserEvent('partnerships.submerchant.referral.product_group.social', {
        productGroup: 'X',
        socialMedia: platform,
      });
    }
    if (product === PRODUCT_TYPE.PG) {
      trackUserEvent('partnerships.submerchant.referral.social');
      analyticsTrack({
        objectName: 'Social Share Referral Link',
        actionName: 'clicked',
        screen: 'affiliate accounts',
        properties: {
          location: 'submerchant list',
          socialMedia: platform,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
        toCleverTap: true,
      });
      trackUserEvent('partnerships.submerchant.referral.product_group.social', {
        productGroup: 'Payments',
        socialMedia: platform,
      });
    }
  };

  const handleCopyLink = () => {
    if (product === PRODUCT_TYPE.PG) {
      fireAnalyticsEvents({
        fbData: 'partner_copy_link',
        liData: 1764844,
      });
      trackReferral();
      trackUserEvent('partnerships.submerchant.referral.copy');
      analyticsTrack({
        objectName: 'Copy Referal Link',
        actionName: 'clicked',
        screen: 'affiliate accounts',
        properties: {
          location: 'submerchant list',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
        toCleverTap: true,
      });
      trackUserEvent('partnerships.submerchant.referral.product_group.copy', {
        productGroup: 'Payments',
      });
    }
    if (product === PRODUCT_TYPE.X) {
      trackUserEvent('partnerships.submerchant.referral.x.copy');
      trackUserEvent('partnerships.submerchant.referral.product_group.copy', {
        productGroup: 'X',
      });
    }
  };

  return (
    <div>
      <div class="input-group" style={{ marginTop: '8px' }}>
        <CustomClipboard value={referralUrl}>
          <input
            class="form-control input"
            value={referralUrl}
            style={{ minWidth: '200px' }}
            readOnly
          />
          <button
            class="btn btn-primary"
            style={{
              width: '100px',
              borderRadius: '0px 2px 2px 0px',
            }}
            onClick={handleCopyLink}
          >
            Copy Link
          </button>
        </CustomClipboard>
      </div>
      <div class="social-share-btn-grp">
        <div>
          <strong>
            <p>Or Share Via</p>
          </strong>
        </div>
        <img src="/img/social-media/fb.png" onClick={() => shareReferralOn('fb')} />
        <img src="/img/social-media/twitter.png" onClick={() => shareReferralOn('twitter')} />
        <img src="/img/social-media/whatsapp.png" onClick={() => shareReferralOn('whatsapp')} />
      </div>
    </div>
  );
}
