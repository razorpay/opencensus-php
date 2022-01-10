import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';
import { getMode } from 'merchant/store';

const bannerText =
  'Start accepting subscriptions from your consumers, right from your website or blog!';
const cardId = 'Subscription Button Launch';

const ctaText = 'LEARN MORE';
const ctaLink =
  'https://razorpay.com/docs/payment-button/subscription-buttons/?click=dshbrd-notif-sb';

function _track(source) {
  const mode = getMode();

  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().success('merchant_dashboard.display_banner', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        source,
      }),
    );
  }

  function onClickCTA() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta2', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: ctaText,
        link_url: ctaLink,
        source,
      }),
    );
  }

  return {
    onViewBanner,
    onClickCTA,
  };
}

export default React.memo(({ productName }) => {
  const track = _track(productName);

  track.onViewBanner();

  return (
    <AnnouncementBanner
      title="Introducing Subscription Button"
      theme="primary"
      fullPage
      card_id="introducing-subscription-button-banner"
      class="hidden-xs"
    >
      <span class="display-inline m-r">{bannerText}</span>
      <DocLink
        class="Button--primary Button Button--narrow m-l"
        href={ctaLink}
        target="_blank"
        onClick={track.onClickCTA}
      >
        <b>
          {ctaText} <i class="i i-external-link" />
        </b>
      </DocLink>{' '}
    </AnnouncementBanner>
  );
});
