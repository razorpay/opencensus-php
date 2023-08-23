import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { AsyncBtn } from 'common/new-ui/Button';
import { sendDataToSalesForce } from 'common/utils/common-api';
import { getUser } from 'merchant/store';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';
import './NitroPgPricing.styl';

const NitroImage = {
  true: {
    image:
      'https://cdn.razorpay.com/static/assets/growth-assets/mweb-exclusive-offer/nitro-pg-pricing.svg',
    alt: 'Nitro PG Pricing',
  },
  false: {
    image:
      'https://cdn.razorpay.com/static/assets/growth-assets/mweb-exclusive-offer/current-account-mweb.svg',
    alt: 'Current Account with RazorpayX',
  },
};

//TODO: remove "nitroCampaignId" function from this file when exclusive offer for m-web support is enable for nitro-card segment which is already have support in Desktop
export const nitroCampaignId = () => {
  return {
    campaign: 'nitro',
    target_metric: 'MTU',
  };
};
const NitroPgPricing = ({ showNotification }): JSX.Element => {
  const [isToolTip, setIsToolTip] = useState(false);
  const [isNitro, setIsNitro] = useState(true);
  const user = getUser();

  const triggerApiCall = (): any => {
    if (!isNitro) {
      window.open('https://razorpay.com/docs/razorpayx/current-account/');
      return;
    }

    // eslint-disable-next-line consistent-return
    return sendDataToSalesForce(
      {
        Campaign_ID: nitroCampaignId()?.campaign,
        product_name: 'Current_Account',
      },
      user,
    )
      .then(() => {
        setIsToolTip(false);
        setIsNitro(false);
      })
      .catch((_) => {
        showNotification({
          type: 'error',
          message: 'An error occurred in connecting to the server',
          hidePrevious: true,
        });
      });
  };
  return (
    <div className="nitroPgPricing" id="nitroPgPricing">
      {isToolTip && (
        <div className="tooltip-ui">
          <img
            src="https://cdn.razorpay.com/static/assets/growth-assets/mweb-exclusive-offer/nitro-tooltip.svg"
            alt="Nitro Campaign tooltip"
          />
        </div>
      )}
      <div className="header-icons">
        <img
          src="https://cdn.razorpay.com/static/assets/neostone-exclusive-offer/xBanking.png"
          alt="Nitro Campaign XBanking Logo"
        />
        {isNitro && (
          <img
            src="https://cdn.razorpay.com/static/assets/neostone-exclusive-offer/toolTipAlert.svg"
            alt="Nitro Campaign tooltip"
            onClick={() => setIsToolTip(!isToolTip)}
            className="nitro-tooltip"
          />
        )}
      </div>
      <img
        src={NitroImage[`${isNitro}`]?.image}
        alt={NitroImage[`${isNitro}`]?.alt}
        className={`nitro-image ${!isNitro ? 'addMarginNitro' : ''}`}
      />
      <div className="cta-wrapper">
        <AsyncBtn.Primary className="btn" onClick={triggerApiCall}>
          {isNitro ? 'I am Interested ✨' : 'View Documents Required'}
        </AsyncBtn.Primary>
      </div>
    </div>
  );
};

export default compose(
  connect(null, {
    showNotification: showNotificationProp,
  }),
)(NitroPgPricing);
