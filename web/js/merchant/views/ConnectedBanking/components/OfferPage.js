import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import XCAHeader from 'common/ui/NotificationsDropdown/Neostone/common/XCAHeader';
import RazorpayXNitroAnnouncement from 'common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import { sendDataToSalesForce } from 'common/utils/common-api';
import {
  updateUtmParams,
  utmCampaignMap,
  utmMediumMap,
  utmSourceMap,
} from 'merchant/helpers/x/updateUtmCookie';
import { headerDivider } from 'merchant/views/ConnectedBanking/data';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';

import Footer from './Footer';
import OfferPageContent from './OfferPageContent';

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
        updateUtmParams({
          utm_campaign: utmCampaignMap.ACCOUNT_LINKING,
          utm_source: utmSourceMap.PG,
          utm_medium: utmMediumMap.DASHBOARD,
        });
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

export default compose(
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
)(withRouter(OfferPage));
