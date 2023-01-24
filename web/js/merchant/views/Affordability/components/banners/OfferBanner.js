import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const OfferBanner = () => {
  return (
    <AnnouncementBanner
      className="widget-live-banner"
      title="🎉 Limited period offer"
      theme="purply"
    >
      <span className="display-inline">
        Start your free trial on Affordability Widget. No additional charges will be applied without
        your consent after the free trial.
      </span>
    </AnnouncementBanner>
  );
};

export default OfferBanner;
