import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

import { getMode, getUser } from 'merchant/store';
import { getCustomURL } from '../../DocsLink';
import { sendDataToSalesForce } from 'common/utils/common-api';

let bannerText;

const cta2Text = 'T&C Apply';
const cta2Link = getCustomURL(
  'https://lp.razorpay.com/links/boost-june-21',
);

function _track(source, user) {
  const mode = getMode();

  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().success('merchant_dashboard.display_banner', {
        mode,
        banner_text: bannerText,
        source,
      }),
    );
  }

  function onClickCTA2() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta2', {
        mode,
        banner_text: bannerText,
        cta_value: cta2Text,
        link_url: cta2Link,
        source,
      }),
    );
  }

  return {
    onViewBanner,
    onClickCTA2,
  };
}

export default React.memo(({ productName }) => {
  const user = getUser();

  const track = _track(productName, user);

  track.onViewBanner();

  let productText = '';
  if (productName === 'payment-pages')
    productText = 'without a website using Payment Pages';
  else if (productName === 'payment-links')
    productText = 'instantly with Payment Links';
  else if (productName === 'payment-buttons')
    productText = 'with the click of a button through Payment Buttons';

  bannerText = `Start accepting payments ${productText} & win free* credits worth ₹50,000!`;

  if (user.isFirstUsageMerchantsExperimentEnabled)
    return null;

  return (
    <AnnouncementBanner
      title="Payments for ₹50k free"
      canBeClosed={true}
      theme="warning"
      bannerKey={`mtu-saver-launch-${user.current}`}
      className='mtu-saver-banner'
    >
      <span class="display-inline">{bannerText}</span> •
      <a class="btn btn-link" href={cta2Link} target="_blank" onClick={track.onClickCTA2}>
        {cta2Text}
      </a>{' '}
    </AnnouncementBanner>
  );
});
