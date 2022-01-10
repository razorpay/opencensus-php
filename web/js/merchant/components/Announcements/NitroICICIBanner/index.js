import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import React from 'react';
import { getMode, getUser } from 'merchant/store';
import { compose } from 'redux';
import { connect } from 'react-redux';
import {
  openModal as openModalProp,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import RazorpayXNitroAnnouncement from '../../../../../js/common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';

const bannerText =
  'Enjoy the benefits of ICICI Powered RazorpayX current account with a reduced pricing of 1.65%* on your payments 🎉';
let cardId = '';
let bannerTitle = '';

const cta1Text = 'Know More';

function _track(source, merchant_id) {
  const mode = getMode();
  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta1', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: cta1Text,
        source,
        merchant_id,
      }),
    );
  }

  return {
    onClickCTA1,
  };
}

const NitroICICIBanner = React.memo(({ productName, openModal, closeModal }) => {
  const user = getUser();
  const track = _track(productName, user.current);
  cardId = user.isNitroIciciBrandedCampaignEnabled
    ? 'OCT-NITRO-ICICIBranded'
    : user.isNitroIciciRemarketingCampaignEnabled
    ? 'OCT-NITRO-ICICIRemarketing'
    : 'OCT-NITRO-BaseCampaign';
  bannerTitle = user.isNitroIciciBrandedCampaignEnabled
    ? 'Get Reduced Pricing !'
    : user.isNitroIciciRemarketingCampaignEnabled
    ? 'Powered by ICICI !'
    : 'Get Reduced Pricing !';

  const handleCTA1Click = () => {
    openModal({
      component: <RazorpayXNitroAnnouncement hideModal={closeModal} />,
      className: 'RazorpayXNitroAnnouncement--Modal',
    });
    track.onClickCTA1();
  };

  return (
    <AnnouncementBanner
      title={bannerTitle}
      canBeClosed={true}
      theme="primary"
      bannerKey={`nitro-icici-branded-${user.current}`}
      card_id={cardId}
    >
      <span class="display-inline">{bannerText}</span>
      <a class="Button--secondary Button scheduled-btn-act btn-border" onClick={handleCTA1Click}>
        {cta1Text}
      </a>
    </AnnouncementBanner>
  );
});

export default compose(connect(null, { openModal: openModalProp, closeModal: fnCloseModal }))(
  NitroICICIBanner,
);
