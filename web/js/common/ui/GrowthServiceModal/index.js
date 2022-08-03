import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import React, { useEffect } from 'react';
import rTracking from 'react-tracking';
import Loader from 'common/ui/Loader';
import './GSModalStyle.styl';
import { fetchGSModal as fetchGSModalProp } from 'merchant/reducers/growthService';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import growthServiceCTAHandler from 'merchant/models/GrowthService/growthServiceCTAHandler';

const GrowthServiceModal = ({
  tracking,
  loading,
  gs_modals,
  closeModal,
  fetchGSModal,
  template_id,
  history,
  tracking_id,
}) => {
  useEffect(() => {
    fetchGSModal({ template_id });
  }, []);
  const isEmptyOrNotMobile =
    Object.keys(gs_modals).length === 0 || (!gs_modals?.image?.mobile_url && isMobileAndTablet());
  const backgroundImgUrl = isMobileAndTablet()
    ? gs_modals?.image?.mobile_url
    : gs_modals?.image?.url;
  const Description = ({ description, type }) => {
    switch (type) {
      case 'bold':
        return <strong>{description}</strong>;
      case 'italics':
        return <i>{description}</i>;
      case 'normal':
      default:
        return <span>{description}</span>;
    }
  };

  const defaultColors = {
    defaultBackgroundColor: '#040B38',
    defaultFooterTextColor: '#FFFFFF',
    defaultCTABackroundColor: '#FC6D0B',
    defaultCTATextColor: '#FFFFFF',
  };

  const buttonHandler = () => {
    growthServiceCTAHandler(gs_modals?.offer_cta?.handler, history, tracking_id);
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        id: gs_modals?.id ? gs_modals?.id : tracking_id,
        cta_text: gs_modals?.footer_data?.label,
        pageUrl: window.location.href,
      }),
    );
  };

  if (!loading) {
    if (isEmptyOrNotMobile) {
      closeModal();
    }
    return (
      <div className={isMobileAndTablet() ? 'gs-container' : ''}>
        <button type="button" id="gsBtnClose" onClick={closeModal}>
          <i className="i i-close" />
        </button>
        <div id="gsModalBody">
          <img className="background-img" src={backgroundImgUrl} alt={gs_modals?.image?.alt_text} />
        </div>
        <div
          className={isMobileAndTablet() ? 'gs-modal-footer-mobile' : 'gs-modal-footer'}
          style={{
            background: gs_modals?.footer_data?.background_color
              ? gs_modals?.footer_data?.background_color
              : defaultColors.defaultBackgroundColor,
          }}
        >
          <div className="para-container">
            <p
              className="para"
              style={{
                color: gs_modals?.footer_data?.footer_text_color
                  ? gs_modals?.footer_data?.footer_text_color
                  : defaultColors.defaultFooterTextColor,
              }}
            >
              <Description
                description={gs_modals?.footer_data?.label}
                type={gs_modals?.footer_data?.style}
              />
            </p>
          </div>
          <div className="btn-container">
            <button
              className="btn"
              type="submit"
              onClick={buttonHandler}
              style={{
                background: gs_modals?.offer_cta?.cta_background_color
                  ? gs_modals?.offer_cta?.cta_background_color
                  : defaultColors.defaultCTABackroundColor,
                color: gs_modals?.offer_cta?.cta_font_color
                  ? gs_modals?.offer_cta?.cta_font_color
                  : defaultColors.defaultCTATextColor,
              }}
            >
              <Description
                description={gs_modals?.offer_cta?.label}
                type={gs_modals?.offer_cta?.style}
              />
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <>
      <button type="button" id="gsBtnClose" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div id="gsModalLoader">
        <Loader />;
      </div>
    </>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('growthServiceModal')),
  withRouter,
  connect(
    (state) => {
      return {
        ...state?.growthService?.gs_modals,
      };
    },
    {
      fetchGSModal: fetchGSModalProp,
      closeModal: closeModalProp,
    },
  ),
)(GrowthServiceModal);
