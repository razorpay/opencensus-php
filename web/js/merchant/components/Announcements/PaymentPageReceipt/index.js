import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default function() {
  return (
    <AnnouncementBanner title="Introducing Payment Receipts" canBeClosed={true}>
      Send automated payment receipts for transactions on your Payment Pages.{' '}
      <span class="big-dot-separator" />
      <a
        href="https://razorpay.com/docs/payment-pages/receipt-80g"
        target="_blank"
      >
        Learn More
      </a>
    </AnnouncementBanner>
  );
}
