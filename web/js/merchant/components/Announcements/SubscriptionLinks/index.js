import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default function() {
  return (
    <AnnouncementBanner
      theme="primary"
      title="Introducing Subscription Links"
      canBeClosed
    >
      Create unique links for your Subscription plans and share them with users
      immediately via email and SMS
    </AnnouncementBanner>
  );
}
