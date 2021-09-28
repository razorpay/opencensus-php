import React from 'react';
import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { sendDataToSalesForce } from 'common/utils/common-api';
import { getMode, getUser } from 'merchant/store';

const bannerText =
  'Do you struggle to manage cash flow prior to your customer payments? Razorpay has got you covered! Enable Cash Advance Facility.';
const cardId = 'SEP21-ULTRALOC-BANNER';

const cta1Text = 'Apply Now';
const cta1Link = '/capital/cash-advance/';

function _track(source, user) {
  const mode = getMode();

  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta1', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: cta1Text,
        link_url: cta1Link,
        source,
      }),
    );

    sendDataToSalesForce('ultra-campaign-p2-cash-advance', user);
  }

  return {
    onClickCTA1,
  };
}

export default React.memo(({ productName }) => {
  const user = getUser();
  const track = _track(productName, user);

  return (
    <AnnouncementBanner
      title="Still Interested?"
      canBeClosed={true}
      theme="primary"
      bannerKey={`ultra-p2-campaign-banner-${user.current}`}
      card_id={cardId}
    >
      <span class="display-inline">{bannerText}</span>{' '}
      <Link
        to={cta1Link}
        class="Button--secondary Button scheduled-btn-act btn-border"
        onClick={track.onClickCTA1}
      >
        {cta1Text}
      </Link>
    </AnnouncementBanner>
  );
});
