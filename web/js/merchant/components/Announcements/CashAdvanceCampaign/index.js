import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

import { getMode, getUser } from 'merchant/store';
import { sendDataToSalesForce } from 'common/utils/common-api';

const bannerText = 'Get additional cash from Razorpay in 10 seconds whenever you need it!';
const cardId = 'Subscription Button Launch';

const cta2Text = 'Learn More';
const cta2Link = 'https://razorpay.com/capital/cash-advance';

const cta1Text = 'Enable Cash Advance';
const cta1Link = '/capital/cash-advance ';

function _track(source, user) {
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

  function onClickCTA1() {
    sendDataToSalesForce('LOC-Cross-sell-V1', user);

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
  }

  function onClickCTA2() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta2', {
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
  const user = getUser();

  const track = _track(productName, user);

  track.onViewBanner();

  return (
    <AnnouncementBanner
      title="Introducing Cash Advance"
      canBeClosed={true}
      theme="primary"
      bannerKey={`cash-advance-launch-${user.current}`}
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
