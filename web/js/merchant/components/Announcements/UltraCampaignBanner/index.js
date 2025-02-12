import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { sendDataToSalesForce } from 'common/utils/common-api';
import { getMode, getUser } from 'merchant/store';

const bannerText =
  'Get exclusive access to RazorpayX Corporate Card with 0.4% cashback, lower FX fee and business-friendly dashboard';
const cardId = 'settlements-banner-JUL21-RXCC-ULTRA';

const cta1Text = 'Apply Now';
const cta1Link = '/capital/corporate-cards/';

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

    sendDataToSalesForce('ultra-campaign', user);
  }

  return {
    onViewBanner,
    onClickCTA1,
  };
}

export default React.memo(({ productName }) => {
  const user = getUser();
  const track = _track(productName, user);
  track.onViewBanner();

  return (
    <AnnouncementBanner
      title="Still Interested?"
      canBeClosed={true}
      theme="primary"
      bannerKey={`ultra-campaign-banner-${user.current}`}
      card_id={cardId}
    >
      <span className="display-inline">{bannerText}</span>{' '}
      <Link
        to={cta1Link}
        className="Button--secondary Button scheduled-btn-act btn-border"
        onClick={track.onClickCTA1}
      >
        {cta1Text}
      </Link>
    </AnnouncementBanner>
  );
});
