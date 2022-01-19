import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import React from 'react';
import { AsyncBtn } from 'common/new-ui/Button';

const NitroICICINewSegmentModal = ({ user, save }) => {
  let background_color_footer = '#050d1f';
  let cta_background_color = 'linear-gradient(108.69deg, #FFC13E -96.94%, #FF650F 100%)';
  let divider_color = '#f98400';
  if (user.isNewNitroICICIBrandedCampaignEnabled || user.isNewNitroICICIBaseCampaignEnabled) {
    background_color_footer = '#050d1f';
    cta_background_color = 'linear-gradient(108.69deg, #FFC13E -96.94%, #FF650F 100%)';
    divider_color = '#f98400';
  } else {
    background_color_footer = '#1d2542';
    cta_background_color = 'linear-gradient(277.95deg, #0E80F7 -79.73%, #00D0FF 157.95%)';
    divider_color = '#216ad9';
  }
  return (
    <>
      <div className="nitro-end-of-year-self-serve" id="nitro-end-of-year-self-serve">
        {user.isNewNitroICICIBrandedCampaignEnabled ? (
          <img
            className="background-img"
            src="https://cdn.razorpay.com/static/assets/final-modal/NitroNewICICIBranded.png"
          />
        ) : user.isNewNitroICICIPlusCardOfferCampaignEnabled ? (
          <img
            className="background-img"
            src="https://cdn.razorpay.com/static/assets/final-modal/NitroICICIBrandedPlusCard.png"
          />
        ) : (
          <img
            className="background-img"
            src="https://cdn.razorpay.com/static/assets/final-modal/NitroNewICICIBase.png"
          />
        )}
      </div>
      <div
        className="nitro-end-of-year-self-serve-divider"
        id="nitro-end-of-year-self-serve-divider"
        style={{ backgroundColor: divider_color }}
      />
      <div
        id="nitro-end-of-year-self-serve-footer"
        style={{ backgroundColor: background_color_footer }}
      >
        <p className="para">
          Your Business deserves a better bank. Take control of your finances with RazorpayX Current
          Account
        </p>
        <AsyncBtn.Primary
          className="btn"
          type="submit"
          style={{ background: cta_background_color }}
          onClick={save}
        >
          I am Interested ✨
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

export default compose(connect(null, { closeModal: closeModalProp }))(NitroICICINewSegmentModal);
