import React, { useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';
import XCAHeader from '../../../../common/ui/NotificationsDropdown/Neostone/common/XCAHeader';
import RazorpayXNitroAnnouncement from '../../../../common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import Footer from './Footer';
import OfferPageContent from './OfferPageContent';
import { headerDivider } from '../data';
import { sendDataToSalesForce } from '../../../../common/utils/common-api';

const OfferPage = ({
  offerDetails,
  history,
  openModal,
  closeModal,
  setShowState,
  tracking,
  user,
}) => {
  const { header, content = {}, footer = {} } = offerDetails;

  const openRazorpayXNitroModal = () => {
    tracking?.trackEvent?.(
      window.rzpQ?.merchantActions?.().clicked?.('nitro_icici_cb.new_ca_interested'),
    );
    openModal({
      component: <RazorpayXNitroAnnouncement hideModal={closeModal} />,
      className: 'RazorpayXNitroAnnouncement--Modal',
    });
  };

  useEffect(() => {
    if (content?.ctaButton) {
      content.ctaButton.onCTAClick = () => {
        setShowState('iframe');
        sendDataToSalesForce('connected-banking-icici', user);
        tracking?.trackEvent?.(
          window.rzpQ?.merchantActions?.().clicked?.('nitro_icici_cb.interested'),
        );
      };
    }
  }, []);

  return (
    <div className="offer-page">
      <XCAHeader
        XCAHeaderText={header?.text}
        imageArr={header?.image}
        handleClose={history?.goBack}
      />
      <div className="headerBottomBorder" />
      <OfferPageContent {...content} />
      <div className="content-divider">
        <img src={headerDivider?.src} alt={headerDivider?.alt} />
      </div>
      <Footer {...footer} onCTAClick={openRazorpayXNitroModal} />
    </div>
  );
};

export default withRouter(
  compose(
    rTracking(() => window.rzpQ.component('ConnectedBanking')),
    connect(
      (state) => ({
        user: state.session.user,
      }),
      {
        openModal: fnOpenModal,
        closeModal: fnCloseModal,
      },
    ),
  )(OfferPage),
);
