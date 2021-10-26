import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { Link } from 'react-router-dom';
import React from 'react';
import { getMode, getUser } from 'merchant/store';

const title = 'Cross Border Payments';
const bannerText = 'Increase your revenue streams with international payments!';
const cta1Text = 'Accept international payments';
const cta1Link =
  '/payment-methods?utm_source=Dashboard+banner&utm_campaign=Existing+domestic+merchants&utm_id=International+Payments';
const bannerId = 'Sep21-CrossBorder-Activation';

function _track(source) {
  const mode = getMode();

  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.paymentButtons().success('merchant_dashboard.display_banner', {
        mode,
        banner_text: bannerText,
        card_id: bannerId,
        source,
      }),
    );
  }

  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.paymentButtons().initiated('merchant_dashboard.click_banner_cta1', {
        mode,
        banner_text: bannerText,
        card_id: bannerId,
        cta_value: cta1Text,
        link_url: cta1Link,
        source,
      }),
    );
  }

  return {
    onViewBanner,
    onClickCTA1,
  };
}

export default React.memo(({ productName }) => {
  const track = _track(productName);
  track.onViewBanner();
  const user = getUser();

  return (
    <AnnouncementBanner
      title={title}
      canBeClosed
      theme="primary"
      bannerKey={`cross-border-payment-banner-${user?.current}`}
      card_id={bannerId}
    >
      <span class="display-inline">{bannerText}</span>
      <Link
        to={cta1Link}
        class="Button--secondary Button scheduled-btn-act btn-border"
        target="_blank"
        rel="noreferrer"
        onClick={track.onClickCTA1}
      >
        {cta1Text}
      </Link>
    </AnnouncementBanner>
  );
});
