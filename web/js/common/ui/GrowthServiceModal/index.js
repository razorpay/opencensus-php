import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose, bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import React, { useEffect } from 'react';
import rTracking from 'react-tracking';
import Loader from 'common/ui/Loader';
import { AsyncBtn } from 'common/new-ui/Button';
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
  isMobileResolution,
}) => {
  useEffect(() => {
    fetchGSModal({ template_id });
  }, []);
  const isEmptyOrNotMobile =
    Object.keys(gs_modals).length === 0 || (!gs_modals?.image?.mobile_url && isMobileAndTablet());
  const backgroundImgUrl = isMobileResolution
    ? gs_modals?.image?.mobile_url
    : gs_modals?.image?.url;
  const Description = ({ description = '', type }) => {
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
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        trackingID: gs_modals?.id || tracking_id,
        cta_text: gs_modals?.footer_data?.label,
        pageUrl: window.location.href,
      }),
    );
    return growthServiceCTAHandler(gs_modals?.offer_cta?.handler, history, tracking_id, tracking);
  };

  if (!loading) {
    if (isEmptyOrNotMobile) {
      closeModal();
    }
    return (
      <div className={isMobileResolution ? 'gs-container' : ''}>
        <button type="button" id="gs-btn-close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
        <div id="gs-modal-body">
          <img
            className={isMobileResolution ? 'background-img-mweb' : 'background-img-dweb'}
            src={backgroundImgUrl}
            alt={gs_modals?.image?.alt_text}
            data-testid="gs-modal-img"
          />
        </div>
        <div
          className={isMobileResolution ? 'gs-modal-footer-mobile' : 'gs-modal-footer'}
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
          <div data-testid="button-wrapper" className="btn-container">
            <AsyncBtn.Primary
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
            </AsyncBtn.Primary>
          </div>
        </div>
      </div>
    );
  }

  return (
    <>
      <button type="button" id="gs-btn-close" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div id="gs-modal-loader" data-testid="gs-modal-loader">
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
        isMobileResolution: state.app.isMobileResolution,
      };
    },
    (dispatch) => {
      return bindActionCreators(
        {
          fetchGSModal: fetchGSModalProp,
          closeModal: closeModalProp,
        },
        dispatch,
      );
    },
  ),
)(GrowthServiceModal);
