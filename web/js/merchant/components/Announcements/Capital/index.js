import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { trackMarketingExperimentBanner } from '../ga';

export default ({ userId }) => {
  trackMarketingExperimentBanner('Capital', 'Appear');

  return (
    <AnnouncementBanner
      class="settlement-anc"
      theme="primary"
      title="Razorpay Capital"
      canBeClosed={true}
      bannerKey={`capital-banner-${userId}`}
      card_id="capital-banner"
    >
      Get loans up to Rs 10 Lakhs for your business and repay from your Razorpay settlements with
      ease. <span class="big-dot-separator" />
      <a
        href="https://razorpay.com/capital/?get-in-touch&utm_source=signup&utm_medium=banner&utm_campaign=businessloans_febs2"
        target="_blank"
        onClick={() => trackMarketingExperimentBanner('Capital', 'Click Link')}
        rel="noreferrer noopener"
      >
        I'm Interested
      </a>
    </AnnouncementBanner>
  );
};
