import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

export default React.memo(() => {
  return (
    <AnnouncementBanner
      title="Boost your International Sales with PayPal"
      canBeClosed={true}
      theme="warning"
    >
      <span class="display-inline">
        Get up to 20% higher success rates on International payments with PayPal wallet. Click here
        to know more
      </span>
      <DocLink
        class="btn btn-link"
        href="https://razorpay.com/docs/payment-gateway/payment-methods/paypal/"
        target="_blank"
      >
        Learn More
      </DocLink>
    </AnnouncementBanner>
  );
});
