import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const bannerText = 'Accept payments on your website, in less than 5 minutes.';
const cardId = 'Payment Button Launch';

const cta2Text = 'Learn More';
const cta2Link = 'https://razorpay.com/payment-buttons?click=dshbrd-notif-pb';

const cta1Text = 'Try Now';
const cta1Link = '/paymentbuttons/new';

function _track(source) {
  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.now().paymentButtons().success('merchant_dashboard.display_banner', {
        banner_text: bannerText,
        card_id: cardId,
        source,
      }),
    );
  }

  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.now().paymentButtons().initiated('merchant_dashboard.click_banner_cta1', {
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
      window.rzpQ.now().paymentButtons().initiated('merchant_dashboard.click_banner_cta2', {
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

export default function ({ productName }) {
  const track = _track(productName);

  track.onViewBanner();

  return (
    <AnnouncementBanner title="Introducing Payment Button" canBeClosed={true} theme="warning">
      <span class="display-inline">{bannerText}</span>
      <a class="btn btn-link" href={cta2Link} target="_blank" onClick={_track.onClickCTA2}>
        {cta2Text}
      </a>{' '}
      <Link
        to={cta1Link}
        class="Button--secondary Button scheduled-btn-act btn-border"
        onClick={_track.onClickCTA1}
      >
        {cta1Text}
      </Link>
    </AnnouncementBanner>
  );
}
