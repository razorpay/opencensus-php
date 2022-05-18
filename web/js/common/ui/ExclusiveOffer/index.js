import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import React, { useState } from 'react';
import rTracking from 'react-tracking';
import Loader from 'common/ui/Loader';
import { SubmissionSuccessfull } from '../NotificationsDropdown/RazorpayXNitroAnnouncement';
import { sendDataToSalesForce } from '../../utils/common-api';

const ExclusiveOffer = ({ tracking, loading, exclusive_offers, user, closeModal, history }) => {
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
        id,
      }),
    );
  };

  const trackCTAClickAndSave = (id, label) => {
    sendDataToSalesForce(
      {
        id,
        product_name: exclusive_offers?.product_name,
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
        id,
      }),
    );
  };

  if (!loading) {
    if (Object.keys(exclusive_offers).length > 0 && activeView === 'detail-view') {
      return (
        <>
          <button
            type="button"
            className="btn-close-modal-exclusive-offer"
            id="btn-close-modal-exclusive-offer"
            onClick={closeModal}
          >
            <i class="i i-close" />
          </button>
          <div className="exclusive-offer-modal-self-serve" id="exclusive-offer-modal-self-serve">
            <img
              className="background-img"
              src={exclusive_offers?.image?.url}
              alt={exclusive_offers?.image?.alt_text}
            />
          </div>
          <div
            className="exclusive-offer-modal-self-serve-divider"
            id="exclusive-offer-modal-self-serve-divider"
          />
          <div
            id="exclusive-offer-modal-self-serve-footer"
            style={{ background: exclusive_offers?.offer?.background_color }}
          >
            <div className="para-container">
              <p className="para">
                <Description
                  description={exclusive_offers?.footer_data?.label}
                  type={exclusive_offers?.footer_data?.style}
                />
              </p>
            </div>
            <div className="btn-container">
              <button
                className="btn"
                type="submit"
                onClick={() =>
                  exclusive_offers?.offer_cta?.url !== undefined
                    ? trackCTAClickAndOpenUrl(
                        exclusive_offers?.id,
                        exclusive_offers?.offer_cta?.label,
                        exclusive_offers?.offer_cta?.url,
                      )
                    : trackCTAClickAndSave(exclusive_offers?.id, exclusive_offers?.offer_cta?.label)
                }
                style={{
                  background: exclusive_offers?.offer?.cta_background_color,
                  color: exclusive_offers?.offer?.cta_font_color,
                }}
              >
                <Description
                  description={exclusive_offers?.offer_cta?.label}
                  type={exclusive_offers?.offer_cta?.style}
                />
              </button>
            </div>
          </div>
        </>
      );
    }
    return <SubmissionSuccessfull handleClose={closeModal} />;
  }
  return (
    <>
      <button
        type="button"
        className="btn-close-modal-exclusive-offer"
        id="btn-close-modal-exclusive-offer"
        onClick={closeModal}
      >
        <i class="i i-close" />
      </button>
      <div
        className="exclusive-offer-loader-modal-self-serve"
        id="exclusive-offer-loader-modal-self-serve"
      >
        <Loader />;
      </div>
    </>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('ExclusiveOffer')),
  withRouter,
  connect(
    (state) => {
      return {
        ...state.session.user,
        ...state.growthService.exclusive_offers,
      };
    },
    {
      closeModal: closeModalProp,
    },
  ),
)(ExclusiveOffer);
