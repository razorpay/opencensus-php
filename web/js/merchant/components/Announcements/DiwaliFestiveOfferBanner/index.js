import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { Link } from 'react-router-dom';
import React from 'react';
import { getMode, getUser } from 'merchant/store';

const title = 'Avail our Diwali offer! 🪔';
const bannerText =
  'Accept ts before 31st October and get 2 lakh free credits to your Razorpay account!';
const cta1Text = 'Avail our offer 🎉';
const cta1Link = 'https://bit.ly/2ZtaJLC';
const bannerId = 'festive-offer-oct-2021';

function _track(source) {
  const mode = getMode();

  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().success('merchant_dashboard.display_banner', {
        mode,
        banner_text: bannerText,
        card_id: bannerId,
        source,
      }),
    );
  }

  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta1', {
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
      bannerKey={`diwali-festive-offer-banner-${user?.current}`}
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
