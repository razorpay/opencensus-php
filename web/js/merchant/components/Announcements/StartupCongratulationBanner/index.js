import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const StartupCongratulationBanner = ({ user }) => {
  return (
    <AnnouncementBanner
      title="Congratulations!"
      canBeClosed
      theme="primary"
      bannerKey={`startup-congratulation-banner-${user?.current}`}
    >
      <span class="display-inline">
        Best wishes to Team {user.business_name} on your latest fund raise. Wishing you continued
        success from Razorpay.
      </span>
    </AnnouncementBanner>
  );
};

export default StartupCongratulationBanner;
