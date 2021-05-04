import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(function ({ bannerKey }) {
  return (
    <AnnouncementBanner
      title="Introducing Analytics Pixel"
      canBeClosed={true}
      theme="warning"
      bannerKey={bannerKey}
    >
      You can now use the Facebook & Google Analytics pixel to track your visitors' actions.{' '}
      <a
        href="https://razorpay.com/docs/payment-pages/advanced-options/plugins-add-ons/"
        target="_blank"
      >
        <b>Learn More</b>
      </a>
    </AnnouncementBanner>
  );
});
