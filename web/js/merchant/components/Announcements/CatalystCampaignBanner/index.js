import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

import { getMode, getUser } from 'merchant/store';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import { Link } from 'react-router-dom';
import PLFeaturesModal from './PLFeaturesModal';

const bannerText =
  'Boost your eCommerce sales by 40%* with Payment Links 🚀';
const cardId = 'JUL21-PG-PL-Cross-selling';

const cta2Text = 'Try Now';
const cta2Link = 'paymentlinks/new';

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

  function onClickCTA2() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta2', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: cta2Text,
        link_url: cta2Link,
        source,
        merchant_id,
      }),
    );
  }

  return {
    onViewBanner,
    onClickCTA1,
    onClickCTA2,
  };
}

const CatalystCampaignBanner = React.memo(({ productName, openModal }) => {
  const user = getUser();
  const track = _track(productName, user.current);

  track.onViewBanner();

  const handleCTA1Click = () => {
    openModal({
      component: <PLFeaturesModal />,
      className: 'PL_Catalyst_Banner--Modal',
    });
    track.onClickCTA1();
  };

  return (
    <AnnouncementBanner
      title="Grow with Payment Links"
      canBeClosed={true}
      theme="primary"
      bannerKey={`payment-links-cross-sell-${user.current}`}
      card_id={cardId}
    >
      <span class="display-inline">{bannerText}</span>
      <a class="Button--secondary Button scheduled-btn-act btn-border" onClick={handleCTA1Click}>
        {cta1Text}
      </a>
      <Link to={cta2Link} class="Button--primary Button scheduled-btn-act btn-border" onClick={track.onClickCTA2}>
        {cta2Text}
      </Link>
    </AnnouncementBanner>
  );
});

export default compose(connect(null, { openModal }))(CatalystCampaignBanner);
