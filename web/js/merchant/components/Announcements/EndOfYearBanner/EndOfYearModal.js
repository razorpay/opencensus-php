import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import React from 'react';
import { AsyncBtn } from 'common/new-ui/Button';

const EndOfYearModal = ({ save, user }) => {
  return (
    <>
      <div className="nitro-end-of-year-self-serve" id="nitro-end-of-year-self-serve">
        {user.isNitroIciciBrandedCampaignEnabled || user.isNitroIciciRemarketingCampaignEnabled ? (
          <img
            className="background-img"
            src="https://cdn.razorpay.com/static/assets/final-modal/EndOfYearIciciModal.png"
          />
        ) : (
          <img
            className="background-img"
            src="https://cdn.razorpay.com/static/assets/final-modal/XMasOffer.png"
          />
        )}
      </div>
      <div id="nitro-end-of-year-self-serve-footer">
        <p className="para">
          Your Business deserves a better bank. Take control of your finances with RazorpayX Current
          Account
        </p>
        <AsyncBtn.Primary className="btn" type="submit" onClick={save}>
          I am Interested ✨
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

export default compose(connect(null, { closeModal: closeModalProp }))(EndOfYearModal);
