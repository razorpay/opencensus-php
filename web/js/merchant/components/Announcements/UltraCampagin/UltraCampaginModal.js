import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import React from 'react';
import { AsyncBtn } from 'common/new-ui/Button';

const UltraCampaginModal = ({ user, save }) => {
  let background_color_footer = '#060C1E';
  let cta_background_color =
    'linear-gradient(104.22deg, #F16E21 6.49%, #D3512B 52.27%, #B12A31 100%)';
  let divider_color = 'linear-gradient(108.13deg, #B12A31 24.67%, #D14F2B 51.58%, #F16D22 75.33%)';
  if (user.isUCCapitalCardsOnlyCampaignEnabled) {
    background_color_footer = '#060C1E';
    cta_background_color =
      'linear-gradient(104.22deg, #F16E21 6.49%, #D3512B 52.27%, #B12A31 100%)';
    divider_color = '#FFFFFF';
  } else {
    background_color_footer = '#060C1E';
    cta_background_color = 'linear-gradient(104.22deg, #4574DF 6.49%, #2856C0 100%)';
    divider_color = '#58D7A3';
  }
  return (
    <>
      <div className="nitro-end-of-year-self-serve" id="nitro-end-of-year-self-serve">
        {user.isUCCapitalCardsOnlyCampaignEnabled ? (
          <img
            className="background-img"
            src="https://cdn.razorpay.com/static/assets/final-modal/UltraCampaginCorporateCards.png"
          />
        ) : (
          <img
            className="background-img"
            src="https://cdn.razorpay.com/static/assets/final-modal/UltraCampaginCashAdvance.png"
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
          {user.isUCCapitalCardsOnlyCampaignEnabled
            ? 'Exclusively available for consistent Razorpay users now!'
            : 'No joining fee and first withdrawal at 0%* to get you started!'}
        </p>
        <AsyncBtn.Primary
          className="btn"
          type="submit"
          style={{ background: cta_background_color }}
          onClick={save}
        >
          Apply Now ✨
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

export default compose(connect(null, { closeModal: closeModalProp }))(UltraCampaginModal);
