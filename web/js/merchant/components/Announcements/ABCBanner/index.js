import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const ABCBanner = ({ user }) => {
  return (
    <AnnouncementBanner
      title="Congratulations!"
      canBeClosed
      theme="primary"
      bannerKey={`abc-banner-${user?.current}`}
    >
      <span class="display-inline">
        Best wishes to Team Aditya Birla Sun Life Mutual Fund for a successful IPO launch! Wishing
        you continued success from Razorpay.
      </span>
    </AnnouncementBanner>
  );
};

export default ABCBanner;
