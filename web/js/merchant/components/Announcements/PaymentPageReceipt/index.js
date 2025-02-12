import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

export default function () {
  return (
    <AnnouncementBanner
      title="Introducing Payment Receipts"
      canBeClosed={true}
      card_id="introducing-payment-receipts-banner"
    >
      Send automated payment receipts for transactions on your Payment Pages.{' '}
      <span className="big-dot-separator" />
      <DocLink href="https://razorpay.com/docs/payment-pages/receipt-80g" target="_blank">
        Learn More
      </DocLink>
    </AnnouncementBanner>
  );
}
