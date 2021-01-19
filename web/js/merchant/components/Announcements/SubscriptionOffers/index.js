import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { getUser } from 'merchant/store';

export default React.memo(({ productName }) => {
  const user = getUser();

  return (
    <AnnouncementBanner
      title="Introducing Offers on Subscription"
      canBeClosed={true}
      theme="primary"
      bannerKey={`subscription-button-launch-${user.current}`}
    >
      <span class="display-inline">
        You can now extend discounts to your customers on subscription plans.
      </span>
      <a class="btn btn-link" href="https://razorpay.com/docs/subscriptions/offers" target="_blank">
        Know more
      </a>
    </AnnouncementBanner>
  );
});
