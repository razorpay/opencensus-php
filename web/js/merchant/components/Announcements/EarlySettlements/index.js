import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { Link } from 'react-router-dom';
import { trackInstantSettlementsBanner } from '../ga';

export default ({ userId }) => {
  trackInstantSettlementsBanner('Appear');

  return (
    <AnnouncementBanner
      className="settlement-anc"
      theme="primary"
      title="Instant Settlements"
      canBeClosed={true}
      bannerKey={`instant-settlements-banner-${userId}`}
      card_id="instant-settlements-banner"
    >
      Get your payments settled within a few hours and never have a shortfall of working capital{' '}
      <span className="big-dot-separator" />
      <Link
        to="/settlements#requestearlyaccess"
        onClick={() => trackInstantSettlementsBanner('Click Link')}
      >
        Request Access
      </Link>
    </AnnouncementBanner>
  );
};
