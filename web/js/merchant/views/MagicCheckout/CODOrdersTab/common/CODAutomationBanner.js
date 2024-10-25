import { Link } from 'react-router-dom';
import { connect } from 'react-redux';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import Button from 'common/new-ui/Button';

import {
  AUTOMATION_BANNER_SUBHEADING,
  AUTOMATION_TAB_LINK,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const CODAutomationBanner = ({ user }) => {
  if (!user.isMagicCODOrderAutomationEnabled) {
    return null;
  }

  return (
    <AnnouncementBanner
      title="Automate action on COD order"
      theme="primary"
      card_id="automate-magic-cod-order"
      className="automate-magic-cod-order-banner"
    >
      {AUTOMATION_BANNER_SUBHEADING}
      <Link to={AUTOMATION_TAB_LINK}>
        <Button.Primary type="button" className="btn btn-primary automate-cta">
          Automate now
        </Button.Primary>
      </Link>
    </AnnouncementBanner>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(CODAutomationBanner);
