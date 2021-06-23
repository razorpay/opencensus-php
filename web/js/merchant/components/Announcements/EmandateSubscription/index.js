import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import DocsLink from 'merchant/components/DocsLink';

export default function () {
  return (
    <AnnouncementBanner theme="primary" title="New payment method on Subscriptions." canBeClosed>
      <div style={{ paddingLeft: '1rem', paddingTop: '0.5rem' }}>
        You can now on-board new customers via E-mandate/ Bank accounts. This new payment method is
        enabled on your checkout.Try it today! To know more
        <DocsLink
          url="https://razorpay.com/docs/subscriptions/faqs/#emandate"
          title="Click here"
        />
      </div>
    </AnnouncementBanner>
  );
}
