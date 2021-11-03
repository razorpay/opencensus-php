import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import React from 'react';
import { getMode, getUser } from 'merchant/store';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { openModal as openModalProp } from 'merchant_common/reducers/modals';
import RazorpayXNitroAnnouncement from '../../../../../js/common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';

const bannerText =
  'This festive season enjoy the benefits of RazorpayX current account along with a reduced pricing of 1.65% on payments 🎉';
const cardId = 'OCT-NITRO-FESTIVEBONANZA';

const cta1Text = 'Know More';

function _track(source, merchant_id) {
  const mode = getMode();

  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().success('merchant_dashboard.display_banner', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        source,
        merchant_id,
      }),
    );
  }

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
    onViewBanner,
    onClickCTA1,
  };
}

const CatalystCampaignBanner = React.memo(({ productName, openModal }) => {
  const user = getUser();
  const track = _track(productName, user.current);

  track.onViewBanner();

  const handleCTA1Click = () => {
    openModal({
      component: <RazorpayXNitroAnnouncement />,
      className: 'RazorpayXNitroAnnouncement--Modal',
    });
    track.onClickCTA1();
  };

  return (
    <AnnouncementBanner
      title="Festive Bonanza!"
      canBeClosed={true}
      theme="primary"
      bannerKey={`nitro-festive-bonanza-${user.current}`}
      card_id={cardId}
    >
      <span class="display-inline">{bannerText}</span>
      <a class="Button--secondary Button scheduled-btn-act btn-border" onClick={handleCTA1Click}>
        {cta1Text}
      </a>
    </AnnouncementBanner>
  );
});

export default compose(connect(null, { openModal: openModalProp }))(CatalystCampaignBanner);
