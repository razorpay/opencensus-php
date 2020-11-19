import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

import { getMode, getUser } from 'merchant/store';

const bannerText =
  'Start accepting subscriptions from your consumers, right from your website or blog!';
const cardId = 'Subscription Button Launch';

const cta2Text = 'Learn More';
const cta2Link =
  'https://razorpay.com/docs/payment-button/subscription-buttons/?click=dshbrd-notif-sb';

const cta1Text = 'Try Now';
const cta1Link = '/subscription_buttons/new';

function _track(source) {
  const mode = getMode();

  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.subscriptionButtons().success('merchant_dashboard.display_banner', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        source,
      }),
    );
  }

  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.subscriptionButtons().initiated('merchant_dashboard.click_banner_cta1', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: cta1Text,
        link_url: cta1Link,
        source,
      }),
    );
  }

  function onClickCTA2() {
    window.rzpQ.push(
      window.rzpQ.subscriptionButtons().initiated('merchant_dashboard.click_banner_cta2', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: cta2Text,
        link_url: cta2Link,
        source,
      }),
    );
  }

  return {
    onViewBanner,
    onClickCTA1,
    onClickCTA2,
  };
}

export default React.memo(({ productName }) => {
  const track = _track(productName);

  track.onViewBanner();

  const user = getUser();

  return (
    <AnnouncementBanner
      title="Introducing Subscription Button"
      canBeClosed={true}
      theme="primary"
      bannerKey={`subscription-button-launch-${user.current}`}
    >
      <span class="display-inline">{bannerText}</span>
      <a class="btn btn-link" href={cta2Link} target="_blank" onClick={track.onClickCTA2}>
        {cta2Text}
      </a>{' '}
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
