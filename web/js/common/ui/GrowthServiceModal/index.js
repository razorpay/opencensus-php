import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import React, { useEffect, useState } from 'react';
import rTracking from 'react-tracking';
import Loader from 'common/ui/Loader';
import { SubmissionSuccessfull } from '../NotificationsDropdown/RazorpayXNitroAnnouncement';
import { sendDataToSalesForce } from '../../utils/common-api';
import './modalStyle.styl';
import { fetchGSModal as fetchGSModalProp } from 'merchant/reducers/growthService';

const GrowthServiceModal = ({
  tracking,
  loading,
  gs_modals,
  user,
  closeModal,
  fetchGSModal,
  history,
  template_id,
}) => {
  useEffect(() => {
    fetchGSModal({ template_id });
  }, []);
  const [activeView, setActiveView] = useState('detail-view');
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

  const trackCTAClickAndOpenUrl = (id, label, url) => {
    const isExternal = /^http(s)?:\/\//.test(url);
    if (isExternal) {
      window.open(url, '_blank');
    } else {
      history.push(url);
    }
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        cta_text: label,
        pageUrl: window.location.href,
        Campaign_ID: id,
      }),
    );
  };

  const trackCTAClickAndSave = (id, label) => {
    sendDataToSalesForce(
      {
        Campaign_ID: id,
        product_name: gs_modals?.product_name,
      },
      user,
    ).then((resp) => {
      const { success } = resp;
      if (success) {
        setActiveView('submission-success-view');
      }
    });
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        cta_text: label,
        pageUrl: window.location.href,
        Campaign_ID: id,
      }),
    );
  };

  const buttonHandler = () => {
    if (gs_modals?.offer_cta?.url !== undefined) {
      trackCTAClickAndOpenUrl(
        gs_modals?.id,
        gs_modals?.offer_cta?.label,
        gs_modals?.offer_cta?.url,
      );
    } else {
      trackCTAClickAndSave(gs_modals?.id, gs_modals?.offer_cta?.label);
    }
  };

  if (!loading) {
    if (Object.keys(gs_modals).length === 0) {
      closeModal();
    } else {
      if (activeView === 'detail-view') {
        return (
          <>
            <button type="button" id="gsBtnClose" onClick={closeModal}>
              <i className="i i-close" />
            </button>
            <div id="gsModalBody">
              <img
                className="background-img"
                src={gs_modals?.image?.url}
                alt={gs_modals?.image?.alt_text}
              />
            </div>
            <div id="gsModalDivider" />
            <div id="gsModalFooter" style={{ background: gs_modals?.offer?.background_color }}>
              <div className="para-container">
                <p
                  className="para"
                  style={{
                    color: gs_modals?.offer?.footer_text_color,
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
                    background: gs_modals?.offer?.cta_background_color,
                    color: gs_modals?.offer?.cta_font_color,
                  }}
                >
                  <Description
                    description={gs_modals?.offer_cta?.label}
                    type={gs_modals?.offer_cta?.style}
                  />
                </button>
              </div>
            </div>
          </>
        );
      }
      return <SubmissionSuccessfull handleClose={closeModal} />;
    }
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
        ...state.session.user,
        ...state.growthService.gs_modals,
      };
    },
    {
      fetchGSModal: fetchGSModalProp,
      closeModal: closeModalProp,
    },
  ),
)(GrowthServiceModal);
